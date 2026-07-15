<?php

namespace App\AiTools;

use App\Models\NSFIRM\CaseRegistration;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
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
            . 'Filter by case id, case type/number, workflow status, or source hospital. '
            . 'Returns each case with its resolved workflow status name and registration dates.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'case_id' => ['type' => 'string', 'description' => 'Exact or partial case id (e.g. "202600000001").'],
                'case_type' => ['type' => 'integer', 'description' => 'Filter by case type code.'],
                'case_number' => ['type' => 'integer', 'description' => 'Filter by case number.'],
                'status_id' => ['type' => 'integer', 'description' => 'Filter by workflow status id (see ref_status).'],
                'source_hospital_id' => ['type' => 'string', 'description' => 'Filter by source hospital id.'],
                'court_ruling' => ['type' => 'integer', 'description' => 'Filter by court ruling flag.'],
                'isSMRP' => ['type' => 'boolean', 'description' => 'Filter cases flagged as SMRP.'],
                'limit' => ['type' => 'integer', 'description' => 'Max rows to return (default 25, max 100).'],
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
        $query = CaseRegistration::query()->with('status:id,status_name');

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
        if (isset($arguments['source_hospital_id'])) {
            $query->where('source_hospital_id', $arguments['source_hospital_id']);
        }
        if (isset($arguments['court_ruling'])) {
            $query->where('court_ruling', $arguments['court_ruling']);
        }
        if (isset($arguments['isSMRP'])) {
            $query->where('isSMRP', (bool) $arguments['isSMRP']);
        }

        $limit = min(max((int) ($arguments['limit'] ?? 25), 1), 100);

        $rows = $query->latest('date_register')
            ->limit($limit)
            ->get([
                'case_id', 'case_type', 'case_number', 'status_id', 'source_hospital_id',
                'register_by', 'verify_by', 'submitted_by', 'verification_date',
                'submitted_date', 'date_register', 'court_ruling',
            ])
            ->map(function (CaseRegistration $case) {
                $data = $case->toArray();
                $data['status_name'] = $case->status?->status_name;

                return $data;
            });

        return [
            'count' => $rows->count(),
            'case_registrations' => $rows->all(),
        ];
    }
}
