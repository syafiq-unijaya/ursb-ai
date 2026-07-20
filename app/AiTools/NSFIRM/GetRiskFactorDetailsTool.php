<?php
namespace App\AiTools\NSFIRM;

use App\Models\NSFIRM\RiskFactorAddictionProblem;
use App\Models\NSFIRM\RiskFactorLegalProblem;
use App\Models\NSFIRM\RiskFactorMentalHealthProblem;
use App\Models\NSFIRM\RiskFactorMentalHealthTreatment;
use App\Models\NSFIRM\RiskFactorPhysicalHealthProblem;
use App\Models\NSFIRM\RiskFactorSocialProblem;
use App\Models\NSFIRM\RiskFactorSuicideAttempt;
use App\Models\NSFIRM\RiskFactorSuicideNote;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
use Illuminate\Http\Request;

class GetRiskFactorDetailsTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_risk_factor_details';
    }

    public function description(): string
    {
        return 'List the specific coded risk factors recorded against forensic cases in the NSFIRM system — '
            . 'which mental health problem, which social problem, which method of a past suicide attempt, and so on. '
            . 'Pick one "category" per call. Each row resolves to a reference code and name (e.g. "01 / Major Depression"). '
            . 'A case can carry several codes in one category, so counting rows overcounts cases — the response '
            . 'returns both "count" (rows) and "case_count" (distinct cases); use case_count for prevalence. '
            . 'For the mental_health_problem and mental_health_treatment categories, "current" and "past" live in the '
            . 'same table — pass "health_problem_type" or the two are silently merged.';
    }

    /**
     * The eight pivot tables behind the risk factor form, exposed as one "category" argument
     * rather than eight near-identical tools. Each carries a free-text "others" column that is
     * only populated when the selected reference code is 99 (Others).
     *
     * @return array<string, array{model: class-string, others: string, label: string}>
     */
    protected function categories(): array
    {
        return [
            'mental_health_problem' => ['model' => RiskFactorMentalHealthProblem::class, 'others' => 'mental_health_problem_other', 'label' => 'mental health problems'],
            'mental_health_treatment' => ['model' => RiskFactorMentalHealthTreatment::class, 'others' => 'mental_health_treatment_other', 'label' => 'mental health treatments'],
            'suicide_attempt' => ['model' => RiskFactorSuicideAttempt::class, 'others' => 'suicide_attempt_others', 'label' => 'past suicide attempts'],
            'suicide_note' => ['model' => RiskFactorSuicideNote::class, 'others' => 'suicide_note_others', 'label' => 'suicide notes'],
            'physical_health_problem' => ['model' => RiskFactorPhysicalHealthProblem::class, 'others' => 'physical_health_problem_others', 'label' => 'physical health problems'],
            'social_problem' => ['model' => RiskFactorSocialProblem::class, 'others' => 'social_problem_others', 'label' => 'social problems'],
            'addiction_problem' => ['model' => RiskFactorAddictionProblem::class, 'others' => 'addiction_problem_others', 'label' => 'addiction problems'],
            'legal_problem' => ['model' => RiskFactorLegalProblem::class, 'others' => 'legal_problem_others', 'label' => 'recent legal problems'],
        ];
    }

    /**
     * The two categories whose table serves both current and past records.
     *
     * @var array<int, string>
     */
    protected array $typedCategories = ['mental_health_problem', 'mental_health_treatment'];

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'category' => [
                    'type' => 'string',
                    'enum' => array_keys($this->categories()),
                    'description' => 'Which kind of risk factor to list. Required.',
                ],
                'case_id' => ['type' => 'string', 'description' => 'Filter by the case id the risk factors belong to.'],
                'code' => ['type' => 'string', 'description' => 'Filter by the exact reference code within the category (e.g. "01"). Codes are stable; ids are not.'],
                'name' => ['type' => 'string', 'description' => 'Filter by reference name (partial match, e.g. "Depression", "Financial").'],
                'health_problem_type' => [
                    'type' => 'string',
                    'enum' => ['current', 'past'],
                    'description' => 'Only for the mental_health_problem and mental_health_treatment categories: '
                    . 'restrict to current or past records. Omitting this merges both.',
                ],
                'limit' => ['type' => 'integer', 'description' => 'Max rows to return (default 1000, max 1000).'],
            ],
            'required' => ['category'],
        ];
    }

    public function authorize(?Request $request = null): bool
    {
        // TODO: tighten this to your needs. Defaults to authenticated users only.
        return $request?->user() !== null;
    }

    public function handle(array $arguments): mixed
    {
        $categories = $this->categories();
        $category = (string) ($arguments['category'] ?? '');

        if (!isset($categories[$category])) {
            return [
                'error' => 'Unknown category. Choose one of: ' . implode(', ', array_keys($categories)) . '.',
            ];
        }

        $config = $categories[$category];
        $model = $config['model'];
        $query = $model::query()->with('ref:id,code,name');

        if (isset($arguments['case_id'])) {
            $query->where('case_id', $arguments['case_id']);
        }
        if (isset($arguments['code'])) {
            $query->whereHas('ref', fn($q) => $q->where('code', $arguments['code']));
        }
        if (isset($arguments['name'])) {
            $query->whereHas('ref', fn($q) => $q->where('name', 'like', '%' . $arguments['name'] . '%'));
        }
        // Only the two mental-health tables carry this discriminator; ignore it elsewhere
        // so a stray argument cannot produce an unknown-column error.
        if (isset($arguments['health_problem_type']) && in_array($category, $this->typedCategories, true)) {
            $query->where('type_of_health_problem', $arguments['health_problem_type']);
        }

        $limit = min(max((int) ($arguments['limit'] ?? 1000), 1), 1000);

        $rows = $query->limit($limit)->get()->map(function ($row) use ($config, $category) {
            $data = [
                'id' => $row->id,
                'case_id' => $row->case_id,
                'code' => $row->ref?->code,
                'name' => $row->ref?->name,
            ];
            if (in_array($category, $this->typedCategories, true)) {
                $data['health_problem_type'] = $row->type_of_health_problem;
            }
            // Surface the free-text only when it was actually filled in (code 99 / Others).
            $others = $row->{$config['others']} ?? null;
            if ($others !== null && $others !== '') {
                $data['others_detail'] = $others;
            }

            return $data;
        });

        return [
            'category' => $category,
            'count' => $rows->count(),
            'case_count' => $rows->pluck('case_id')->unique()->count(),
            'risk_factor_details' => $rows->all(),
        ];
    }
}
