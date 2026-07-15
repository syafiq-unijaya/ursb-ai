<?php

namespace App\AiTools;

use App\Models\NSFIRM\CaseTypeOfInjury;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
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
            . 'Use this to answer "what injuries were recorded for case X".';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'case_id' => ['type' => 'string', 'description' => 'Filter by the case id the injuries belong to.'],
                'injury_code' => ['type' => 'string', 'description' => 'Filter by a specific injury code (ref_type_of_injury).'],
                'active' => ['type' => 'boolean', 'description' => 'Only active injury rows (default true).'],
                'limit' => ['type' => 'integer', 'description' => 'Max rows to return (default 50, max 200).'],
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

        if (isset($arguments['case_id'])) {
            $query->where('case_id', $arguments['case_id']);
        }
        if (isset($arguments['injury_code'])) {
            $query->where('injury_code', $arguments['injury_code']);
        }
        // Default to active rows unless the caller explicitly asks for all.
        $active = $arguments['active'] ?? true;
        if ($active !== null) {
            $query->where('active', (bool) $active);
        }

        $limit = min(max((int) ($arguments['limit'] ?? 50), 1), 200);

        $rows = $query->limit($limit)
            ->get(['id', 'case_id', 'injury_code', 'active'])
            ->map(function (CaseTypeOfInjury $row) {
                $data = $row->toArray();
                $data['injury_name'] = $row->injury?->name;

                return $data;
            });

        return [
            'count' => $rows->count(),
            'case_type_of_injuries' => $rows->all(),
        ];
    }
}
