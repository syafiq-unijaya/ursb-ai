<?php
namespace App\AiTools\NSFIRM;

use App\Models\NSFIRM\CaseTypeOfInjury;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class GetCaseTypeOfInjuriesTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_case_type_of_injuries';
    }

    public function description(): string
    {
        return 'List the types of injury recorded against forensic cases in the NSFIRM system. '
            . 'Filter by case id or injury code. Returns each row with the resolved injury name. '
            . 'Use this to answer "what injuries were recorded for case X". '
            . 'Use "include" to also pull the parent case under each row\'s "relations" key.';
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
        ];
    }

    /**
     * Columns always returned. Foreign keys needed by an "include" are added on demand.
     *
     * @var array<int, string>
     */
    protected array $baseColumns = ['id', 'case_id', 'injury_code', 'active'];

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'case_id' => ['type' => 'string', 'description' => 'Filter by the case id the injuries belong to.'],
                'injury_code' => ['type' => 'string', 'description' => 'Filter by a specific injury code (ref_type_of_injury).'],
                'injury' => ['type' => 'string', 'description' => 'Filter by injury type name (partial match, e.g. "Neck", "Head Injury").'],
                'active' => ['type' => 'boolean', 'description' => 'Only active injury rows (default true).'],
                'include' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'enum' => array_keys($this->allowedIncludes())],
                    'description' => 'Related data to eager-load, returned under each row\'s "relations" key. '
                    . 'Options: case (the parent case registration with its status and source hospital).',
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
        $query = CaseTypeOfInjury::query()->with('injury:id,code,name,short_name');

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
        if (isset($arguments['injury_code'])) {
            $query->where('injury_code', $arguments['injury_code']);
        }
        if (isset($arguments['injury'])) {
            $query->whereHas('injury', fn($q) => $q->where('name', 'like', '%' . $arguments['injury'] . '%'));
        }
        // Default to active rows unless the caller explicitly asks for all.
        $active = $arguments['active'] ?? true;
        if ($active !== null) {
            $query->where('active', (bool) $active);
        }

        $limit = min(max((int) ($arguments['limit'] ?? 1000), 1), 1000);

        $rows = $query->limit($limit)
            ->get($columns)
            ->map(function (CaseTypeOfInjury $row) use ($includes) {
                $data = $row->attributesToArray();
                $data['injury_name'] = $row->injury?->name;
                $data = $this->attachIncludes($data, $row, $includes);

                return $data;
            });

        return [
            'count' => $rows->count(),
            'case_type_of_injuries' => $rows->all(),
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
