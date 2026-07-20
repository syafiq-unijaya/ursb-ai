<?php
namespace App\AiTools\NSFIRM;

use App\Models\NSFIRM\CaseHistory;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class GetCaseHistoriesTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_case_histories';
    }

    public function description(): string
    {
        return 'List the workflow audit trail for forensic cases in the NSFIRM system — every status transition a '
            . 'case has been through, who made it, when, and any description or remarks they left. '
            . 'Rows are returned newest first. Use this to answer questions about a case\'s progress, how long it '
            . 'has sat at a status, who verified or reopened it, or which cases are stuck awaiting action. '
            . 'Use "include" to resolve the status names, the acting user, and the parent case under each row\'s '
            . '"relations" key.';
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
            'status' => ['load' => ['status:id,status_name,status_description'], 'relation' => 'status', 'fk' => ['case_status']],
            'previous_status' => ['load' => ['previousStatus:id,status_name,status_description'], 'relation' => 'previousStatus', 'fk' => ['previous_case_status']],
            'user' => ['load' => ['user:id,name,email'], 'relation' => 'user', 'fk' => ['user_id']],
        ];
    }

    /**
     * Columns always returned. Foreign keys needed by an "include" are added on demand.
     *
     * @var array<int, string>
     */
    protected array $baseColumns = [
        'id', 'case_id', 'case_status', 'previous_case_status', 'decision_status',
        'user_id', 'description', 'remarks', 'created_at', 'updated_at',
    ];

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'case_id' => ['type' => 'string', 'description' => 'Filter by the case id the history belongs to.'],
                'case_status' => ['type' => 'integer', 'description' => 'Filter by the status id the case moved into (see ref_status).'],
                'status' => ['type' => 'string', 'description' => 'Filter by the status name the case moved into (partial match, e.g. "Verified", "Draft").'],
                'previous_case_status' => ['type' => 'integer', 'description' => 'Filter by the status id the case moved out of.'],
                'decision_status' => ['type' => 'integer', 'description' => 'Filter by the decision status recorded on the transition.'],
                'user_id' => ['type' => 'integer', 'description' => 'Filter by the id of the user who made the transition.'],
                'user_name' => ['type' => 'string', 'description' => 'Filter by the name of the user who made the transition (partial match).'],
                'remarks' => ['type' => 'string', 'description' => 'Filter by text in the remarks (partial match).'],
                'created_from' => ['type' => 'string', 'description' => 'Transitions on/after this date (YYYY-MM-DD).'],
                'created_to' => ['type' => 'string', 'description' => 'Transitions on/before this date (YYYY-MM-DD).'],
                'include' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'enum' => array_keys($this->allowedIncludes())],
                    'description' => 'Related data to eager-load, returned under each row\'s "relations" key. '
                    . 'Options: case (parent case registration with its current status and source hospital), '
                    . 'status (the status moved into), previous_status (the status moved out of), '
                    . 'user (who made the transition).',
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
        $query = CaseHistory::query();

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
        if (isset($arguments['case_status'])) {
            $query->where('case_status', $arguments['case_status']);
        }
        if (isset($arguments['status'])) {
            $query->whereHas('status', fn($q) => $q->where('status_name', 'like', '%' . $arguments['status'] . '%'));
        }
        if (isset($arguments['previous_case_status'])) {
            $query->where('previous_case_status', $arguments['previous_case_status']);
        }
        if (isset($arguments['decision_status'])) {
            $query->where('decision_status', $arguments['decision_status']);
        }
        if (isset($arguments['user_id'])) {
            $query->where('user_id', $arguments['user_id']);
        }
        if (isset($arguments['user_name'])) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', '%' . $arguments['user_name'] . '%'));
        }
        if (isset($arguments['remarks'])) {
            $query->where('remarks', 'like', '%' . $arguments['remarks'] . '%');
        }
        if (isset($arguments['created_from'])) {
            $query->whereDate('created_at', '>=', $arguments['created_from']);
        }
        if (isset($arguments['created_to'])) {
            $query->whereDate('created_at', '<=', $arguments['created_to']);
        }

        $limit = min(max((int) ($arguments['limit'] ?? 1000), 1), 1000);

        // Newest first: an audit trail is almost always read from the latest event backwards.
        $rows = $query->orderByDesc('created_at')
            ->limit($limit)
            ->get($columns)
            ->map(function (CaseHistory $row) use ($includes) {
                $data = $row->attributesToArray();

                return $this->attachIncludes($data, $row, $includes);
            });

        return [
            'count' => $rows->count(),
            'case_histories' => $rows->all(),
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
