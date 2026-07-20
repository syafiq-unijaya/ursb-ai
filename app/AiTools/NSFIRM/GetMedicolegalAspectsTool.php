<?php
namespace App\AiTools\NSFIRM;

use App\Models\NSFIRM\MediocolegalAspect;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class GetMedicolegalAspectsTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_medicolegal_aspects';
    }

    public function description(): string
    {
        return 'List the medicolegal record attached to forensic cases in the NSFIRM system: whether the case is a '
            . 'medicolegal case, its police report number, report date and police station, and the details of the '
            . 'person who found the body (name, contact, relationship to the deceased, and address). '
            . 'One row per case. Use this to answer questions about police reports, reporting stations, or who '
            . 'discovered the deceased. Use "include" to pull the parent case or the resolved finder address '
            . 'references under each row\'s "relations" key.';
    }

    /**
     * Allow-listed relationships the AI may request via the "include" argument.
     * "fk" lists the base-table columns that must be selected for the relation to resolve.
     *
     * @return array<string, array{load: array<int, string>, relation: string, fk: array<int, string>}>
     */
    protected function allowedIncludes(): array
    {
        return [
            'case' => ['load' => ['caseRegistration.status:id,status_name', 'caseRegistration.sourceHospital:id,name,facilityCode,state_code'], 'relation' => 'caseRegistration', 'fk' => ['case_id']],
            'finder_relationship' => ['load' => ['finderRelationship:id,code,name'], 'relation' => 'finderRelationship', 'fk' => ['finder_relationship_code']],
            'finder_identification_type' => ['load' => ['finderIdentificationType:id,code,name'], 'relation' => 'finderIdentificationType', 'fk' => ['finder_identification_type']],
            'finder_city' => ['load' => ['finderCity:id,code,name'], 'relation' => 'finderCity', 'fk' => ['finder_city_code']],
            'finder_state' => ['load' => ['finderState:id,code,name'], 'relation' => 'finderState', 'fk' => ['finder_state_code']],
        ];
    }

    /**
     * Columns always returned. Foreign keys needed by an "include" are added on demand.
     *
     * @var array<int, string>
     */
    protected array $baseColumns = [
        'id', 'case_id', 'medicolegal_case', 'report_number', 'report_date', 'police_station_name',
        'body_found_by_someone', 'finder_name', 'finder_contact', 'finder_id_number', 'finder_address',
        'finder_relationship_code', 'other_finder_relationship',
    ];

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'case_id' => ['type' => 'string', 'description' => 'Filter by the case id the medicolegal record belongs to.'],
                'medicolegal_case' => ['type' => 'integer', 'description' => 'Filter by the medicolegal case flag.'],
                'report_number' => ['type' => 'string', 'description' => 'Filter by police report number (partial match).'],
                'police_station_name' => ['type' => 'string', 'description' => 'Filter by police station name (partial match, e.g. "Seremban").'],
                'report_date' => ['type' => 'string', 'description' => 'Filter by exact police report date (YYYY-MM-DD).'],
                'report_date_from' => ['type' => 'string', 'description' => 'Police report date on/after this date (YYYY-MM-DD).'],
                'report_date_to' => ['type' => 'string', 'description' => 'Police report date on/before this date (YYYY-MM-DD).'],
                'finder_name' => ['type' => 'string', 'description' => 'Filter by the name of the person who found the body (partial match).'],
                'finder_relationship' => ['type' => 'string', 'description' => 'Filter by the finder\'s relationship to the deceased, by name (partial match, e.g. "Spouse", "Neighbour").'],
                'finder_relationship_code' => ['type' => 'string', 'description' => 'Filter by the finder\'s relationship code (ref_relationship).'],
                'finder_state' => ['type' => 'string', 'description' => 'Filter by the finder\'s state name (partial match).'],
                'finder_state_code' => ['type' => 'string', 'description' => 'Filter by the finder\'s state code (ref_state).'],
                'has_police_report' => ['type' => 'boolean', 'description' => 'Only rows that do (true) or do not (false) carry a police report number.'],
                'include' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'enum' => array_keys($this->allowedIncludes())],
                    'description' => 'Related data to eager-load, returned under each row\'s "relations" key. '
                    . 'Options: case (parent case registration with status and source hospital), '
                    . 'finder_relationship, finder_identification_type, finder_city, finder_state.',
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
        $query = MediocolegalAspect::query();

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

        if (isset($arguments['case_id'])) {
            $query->where('case_id', $arguments['case_id']);
        }
        if (isset($arguments['medicolegal_case'])) {
            $query->where('medicolegal_case', $arguments['medicolegal_case']);
        }
        if (isset($arguments['report_number'])) {
            $query->where('report_number', 'like', '%' . $arguments['report_number'] . '%');
        }
        if (isset($arguments['police_station_name'])) {
            $query->where('police_station_name', 'like', '%' . $arguments['police_station_name'] . '%');
        }
        if (isset($arguments['report_date'])) {
            $query->whereDate('report_date', $arguments['report_date']);
        }
        if (isset($arguments['report_date_from'])) {
            $query->whereDate('report_date', '>=', $arguments['report_date_from']);
        }
        if (isset($arguments['report_date_to'])) {
            $query->whereDate('report_date', '<=', $arguments['report_date_to']);
        }
        if (isset($arguments['finder_name'])) {
            $query->where('finder_name', 'like', '%' . $arguments['finder_name'] . '%');
        }
        if (isset($arguments['finder_relationship_code'])) {
            $query->where('finder_relationship_code', $arguments['finder_relationship_code']);
        }
        if (isset($arguments['finder_relationship'])) {
            $query->whereHas('finderRelationship', fn($q) => $q->where('name', 'like', '%' . $arguments['finder_relationship'] . '%'));
        }
        if (isset($arguments['finder_state_code'])) {
            $query->where('finder_state_code', $arguments['finder_state_code']);
        }
        if (isset($arguments['finder_state'])) {
            $query->whereHas('finderState', fn($q) => $q->where('name', 'like', '%' . $arguments['finder_state'] . '%'));
        }
        if (isset($arguments['has_police_report'])) {
            $arguments['has_police_report']
            ? $query->whereNotNull('report_number')->where('report_number', '!=', '')
            : $query->where(fn($q) => $q->whereNull('report_number')->orWhere('report_number', ''));
        }

        $limit = min(max((int) ($arguments['limit'] ?? 1000), 1), 1000);

        $rows = $query->limit($limit)
            ->get($columns)
            ->map(function (MediocolegalAspect $row) use ($includes) {
                $data = $row->attributesToArray();

                return $this->attachIncludes($data, $row, $includes);
            });

        return [
            'count' => $rows->count(),
            'medicolegal_aspects' => $rows->all(),
        ];
    }

    /**
     * Resolve the caller's requested includes against the allow-list.
     *
     * @return array<string, array{load: array<int, string>, relation: string}>
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
     * @param  array<string, array{load: array<int, string>, relation: string}>  $includes
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
