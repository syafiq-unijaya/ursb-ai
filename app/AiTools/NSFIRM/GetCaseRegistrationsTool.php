<?php
namespace App\AiTools\NSFIRM;

use App\Models\NSFIRM\CaseRegistration;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class GetCaseRegistrationsTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_case_registrations';
    }

    public function description(): string
    {
        return 'Look up forensic case registrations from the NSFIRM system. '
            . 'Filter by case id, case type/number, workflow status, source hospital id, or hospital name. '
            . 'Returns each case with its resolved workflow status name, source hospital name, and registration dates. '
            . 'Use "include" to also pull related data (assigned doctor, source hospital with state, '
            . 'deceased records, injuries) nested under each row\'s "relations" key.';
    }

    /**
     * Allow-listed relationships the AI may request via the "include" argument.
     * key = name exposed to the model, load = eager-load path(s), relation = accessor,
     * fk = base-table columns that must be selected for the relation to resolve.
     *
     * @return array<string, array{load: array<int, string>, relation: string, fk: array<int, string>}>
     */
    protected function allowedIncludes(): array
    {
        return [
            'specific_doctor' => ['load' => ['specificDoctor:id,name,email'], 'relation' => 'specificDoctor', 'fk' => ['specific_doctor']],
            'source_hospital' => ['load' => ['sourceHospital.state:id,code,name'], 'relation' => 'sourceHospital', 'fk' => ['source_hospital_id']],
            'deceased_information' => ['load' => ['deceasedInformation'], 'relation' => 'deceasedInformation', 'fk' => ['case_id']],
            'type_of_injuries' => ['load' => ['typeOfInjuries.injury:id,code,name,short_name'], 'relation' => 'typeOfInjuries', 'fk' => ['case_id']],
        ];
    }

    /**
     * Columns always returned. Foreign keys needed by an "include" are added on demand.
     *
     * @var array<int, string>
     */
    protected array $baseColumns = [
        'case_id', 'case_type', 'case_number', 'status_id', 'source_hospital_id',
        'register_by', 'verify_by', 'submitted_by', 'verification_date',
        'submitted_date', 'date_register', 'court_ruling',
    ];

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'case_id' => ['type' => 'string', 'description' => 'Exact or partial case id (e.g. "202600000001").'],
                'case_type' => ['type' => 'integer', 'description' => 'Filter by case type code.'],
                'case_number' => ['type' => 'integer', 'description' => 'Filter by case number.'],
                'status_id' => ['type' => 'integer', 'description' => 'Filter by workflow status id (see ref_status).'],
                'status' => ['type' => 'string', 'description' => 'Filter by workflow status name (partial match, e.g. "Draft", "Verified").'],
                'source_hospital_id' => ['type' => 'string', 'description' => 'Filter by source hospital id / facility code (e.g. "11-05060009").'],
                'hospital_name' => ['type' => 'string', 'description' => 'Filter by source hospital name (partial match, e.g. "Seremban").'],
                'court_ruling' => ['type' => 'integer', 'description' => 'Filter by court ruling flag.'],
                'isSMRP' => ['type' => 'boolean', 'description' => 'Filter cases flagged as SMRP.'],
                'include' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'enum' => array_keys($this->allowedIncludes())],
                    'description' => 'Related data to eager-load, returned under each row\'s "relations" key. '
                    . 'Options: specific_doctor (assigned doctor), source_hospital (source hospital with its state), '
                    . 'deceased_information (deceased records on the case), '
                    . 'type_of_injuries (recorded injuries with their names).',
                ],
                'limit' => ['type' => 'integer', 'description' => 'Max rows to return (default 1000, max 1000).'],
            ],
            'required' => [],
        ];
    }

    public function authorize(?Request $request = null): bool
    {
        // TODO: tighten this to your needs. Defaults to authenticated users only.
        return $request?->user() !== null;
    }

    public function handle(array $arguments): mixed
    {
        $query = CaseRegistration::query()->with([
            'status:id,status_name',
            'sourceHospital:id,name,facilityCode,state_code',
        ]);

        // Requested includes: eager-load them AND make sure their foreign keys are selected,
        // otherwise the belongsTo relation cannot resolve and comes back null.
        $columns = $this->baseColumns;
        $includes = $this->resolveIncludes($arguments);
        foreach ($includes as $cfg) {
            $query->with($cfg['load']);
            foreach ($cfg['fk'] as $col) {
                $columns[] = $col;
            }
        }
        $columns = array_values(array_unique($columns));

        // TODO: scope this query to the current user if cases are owned per user.
        if (isset($arguments['case_id'])) {
            $query->where('case_id', 'like', '%' . $arguments['case_id'] . '%');
        }
        if (isset($arguments['case_type'])) {
            $query->where('case_type', $arguments['case_type']);
        }
        if (isset($arguments['case_number'])) {
            $query->where('case_number', $arguments['case_number']);
        }
        if (isset($arguments['status_id'])) {
            $query->where('status_id', $arguments['status_id']);
        }
        if (isset($arguments['status'])) {
            $query->whereHas('status', fn($q) => $q->where('status_name', 'like', '%' . $arguments['status'] . '%'));
        }
        if (isset($arguments['source_hospital_id'])) {
            $query->where('source_hospital_id', $arguments['source_hospital_id']);
        }
        if (isset($arguments['hospital_name'])) {
            $query->whereHas('sourceHospital', function ($q) use ($arguments) {
                $q->where('name', 'like', '%' . $arguments['hospital_name'] . '%');
            });
        }
        if (isset($arguments['court_ruling'])) {
            $query->where('court_ruling', $arguments['court_ruling']);
        }
        if (isset($arguments['isSMRP'])) {
            $query->where('isSMRP', (bool) $arguments['isSMRP']);
        }

        $limit = min(max((int) ($arguments['limit'] ?? 1000), 1), 1000);

        $rows = $query->latest('date_register')
            ->limit($limit)
            ->get($columns)
            ->map(function (CaseRegistration $case) use ($includes) {
                $data = $case->attributesToArray();
                $data['status_name'] = $case->status?->status_name;
                $data['source_hospital_name'] = $case->sourceHospital?->name;
                $data = $this->attachIncludes($data, $case, $includes);

                return $data;
            });

        return [
            'count' => $rows->count(),
            'case_registrations' => $rows->all(),
        ];
    }

    /**
     * Resolve the caller's requested includes against the allow-list.
     *
     * @return array<string, array{load: array<int, string>, relation: string, fk: array<int, string>}>
     */
    protected function resolveIncludes(array $arguments): array
    {
        $requested = $arguments['include'] ?? [];
        if (!is_array($requested)) {
            return [];
        }

        $allowed = $this->allowedIncludes();

        return array_intersect_key($allowed, array_flip(array_filter($requested, 'is_string')));
    }

    /**
     * Attach the loaded includes to a serialized row under "relations".
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, array{load: array<int, string>, relation: string, fk: array<int, string>}>  $includes
     * @return array<string, mixed>
     */
    protected function attachIncludes(array $data, Model $model, array $includes): array
    {
        foreach ($includes as $key => $cfg) {
            $related = $model->{$cfg['relation']};
            $data['relations'][$key] = $related?->toArray();
        }

        return $data;
    }
}
