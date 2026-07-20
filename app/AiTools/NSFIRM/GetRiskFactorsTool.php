<?php

namespace App\AiTools\NSFIRM;

use App\Models\NSFIRM\RiskFactor;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class GetRiskFactorsTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_risk_factors';
    }

    public function description(): string
    {
        return 'List the risk factor profile recorded against forensic cases in the NSFIRM system: '
            . 'mental health problems and treatment, past suicide attempts, suicide notes, physical health, '
            . 'social problems, addiction, and recent legal problems. One row per case. '
            . 'Each flag is answered as yes / no / unknown, or is left unanswered — filter with those words, not 1/0. '
            . 'IMPORTANT: most flags are unanswered for most cases, so every response includes an "answered_counts" '
            . 'block giving the real denominator per flag. Always quote prevalence against that denominator, '
            . 'never against the total case count. '
            . 'Use "include" to pull the coded detail rows (which specific problems) under each row\'s "relations" key.';
    }

    /**
     * The ten yes/no/unknown questions on the risk factor form.
     * Keyed by the argument name exposed to the AI => the underlying column.
     *
     * @var array<string, string>
     */
    protected array $flags = [
        'mental_health_problem' => 'have_mental_health_problem',
        'current_mental_health_treatment' => 'have_current_treatment_of_mental_health',
        'past_mental_health_treatment' => 'have_past_treatment_of_mental_health',
        'past_suicide_attempt' => 'past_suicide_attempt',
        'physical_health_problem' => 'physical_health_problem',
        'social_problem' => 'have_social_problem',
        'addiction_problem' => 'have_addiction_problem',
        'recent_legal_problem' => 'have_recent_legal_problem',
        'suicidal_note' => 'suicidal_note',
        // Column name is misspelled upstream in the nsfirm schema; keep the argument readable.
        'clinically_diagnosed_psychiatric_illness' => 'clinically_diagnosed_psyciatric_illnes',
    ];

    /**
     * How the tri-state answer is stored. NULL means the question was never answered,
     * which is the dominant state and must not be read as "no".
     *
     * @var array<string, int|null>
     */
    protected array $answerValues = [
        'yes' => 1,
        'no' => 0,
        'unknown' => 2,
        'unanswered' => null,
    ];

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
            'mental_health_problems' => ['load' => ['mentalHealthProblems.ref:id,code,name'], 'relation' => 'mentalHealthProblems', 'fk' => ['case_id']],
            'mental_health_treatments' => ['load' => ['mentalHealthTreatments.ref:id,code,name'], 'relation' => 'mentalHealthTreatments', 'fk' => ['case_id']],
            'suicide_attempts' => ['load' => ['suicideAttempts.ref:id,code,name'], 'relation' => 'suicideAttempts', 'fk' => ['case_id']],
            'suicide_notes' => ['load' => ['suicideNotes.ref:id,code,name'], 'relation' => 'suicideNotes', 'fk' => ['case_id']],
            'physical_health_problems' => ['load' => ['physicalHealthProblems.ref:id,code,name'], 'relation' => 'physicalHealthProblems', 'fk' => ['case_id']],
            'social_problems' => ['load' => ['socialProblems.ref:id,code,name'], 'relation' => 'socialProblems', 'fk' => ['case_id']],
            'addiction_problems' => ['load' => ['addictionProblems.ref:id,code,name'], 'relation' => 'addictionProblems', 'fk' => ['case_id']],
            'legal_problems' => ['load' => ['legalProblems.ref:id,code,name'], 'relation' => 'legalProblems', 'fk' => ['case_id']],
        ];
    }

    /**
     * Columns always returned. Foreign keys needed by an "include" are added on demand.
     *
     * @var array<int, string>
     */
    protected array $baseColumns = ['id', 'case_id'];

    public function parameters(): array
    {
        $answers = array_keys($this->answerValues);

        $properties = [
            'case_id' => ['type' => 'string', 'description' => 'Filter by the case id the risk factors belong to.'],
        ];

        foreach (array_keys($this->flags) as $flag) {
            $properties[$flag] = [
                'type' => 'string',
                'enum' => $answers,
                'description' => sprintf(
                    'Filter cases where "%s" was answered %s. Use "unanswered" for cases where the question was never filled in.',
                    str_replace('_', ' ', $flag),
                    implode(' / ', $answers)
                ),
            ];
        }

        $properties['completed_only'] = ['type' => 'boolean', 'description' => 'Only cases whose risk factor section is fully completed (all completion flags set).'];
        $properties['include'] = [
            'type' => 'array',
            'items' => ['type' => 'string', 'enum' => array_keys($this->allowedIncludes())],
            'description' => 'Related data to eager-load, returned under each row\'s "relations" key. '
                . 'Options: case (parent case registration with status and source hospital), and the coded '
                . 'detail rows behind each flag: mental_health_problems, mental_health_treatments, suicide_attempts, '
                . 'suicide_notes, physical_health_problems, social_problems, addiction_problems, legal_problems.',
        ];
        $properties['limit'] = ['type' => 'integer', 'description' => 'Max rows to return (default 1000, max 1000).'];

        return [
            'type' => 'object',
            'properties' => $properties,
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
        $query = RiskFactor::query();

        // Requested includes: eager-load them AND make sure their foreign keys are selected,
        // otherwise the relation cannot resolve and comes back null.
        $columns = array_merge($this->baseColumns, array_values($this->flags));
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

        foreach ($this->flags as $flag => $column) {
            if (! isset($arguments[$flag])) {
                continue;
            }
            $answer = (string) $arguments[$flag];
            if (! array_key_exists($answer, $this->answerValues)) {
                continue;
            }
            $value = $this->answerValues[$answer];
            // NULL is a real answer state here ("never asked"), so it needs whereNull, not where(=).
            $value === null ? $query->whereNull($column) : $query->where($column, $value);
        }

        if (! empty($arguments['completed_only'])) {
            foreach (range(1, 7) as $n) {
                $query->where('completion_flag_' . $n, 1);
            }
        }

        $limit = min(max((int) ($arguments['limit'] ?? 1000), 1), 1000);

        $rows = $query->limit($limit)
            ->get($columns)
            ->map(function (RiskFactor $row) use ($includes) {
                $data = ['id' => $row->id, 'case_id' => $row->case_id];
                // Return the words the caller filtered with, not the raw 1/0/2/NULL.
                foreach ($this->flags as $flag => $column) {
                    $data[$flag] = $this->describeAnswer($row->{$column});
                }

                return $this->attachIncludes($data, $row, $includes);
            });

        return [
            'count' => $rows->count(),
            'answered_counts' => $this->answeredCounts($rows),
            'risk_factors' => $rows->all(),
        ];
    }

    /**
     * Translate the stored tri-state value into the vocabulary the tool exposes.
     */
    protected function describeAnswer(mixed $value): string
    {
        if ($value === null) {
            return 'unanswered';
        }

        $match = array_search((int) $value, $this->answerValues, true);

        return $match === false ? 'unanswered' : $match;
    }

    /**
     * Per-flag denominator for the returned rows. Without this the model will read
     * "3 cases with addiction problems" out of 237 rows as 1.3% when 190 of those
     * rows never answered the question at all.
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $rows
     * @return array<string, array<string, int>>
     */
    protected function answeredCounts($rows): array
    {
        $counts = [];
        foreach (array_keys($this->flags) as $flag) {
            $values = $rows->pluck($flag);
            $counts[$flag] = [
                'yes' => $values->filter(fn ($v) => $v === 'yes')->count(),
                'no' => $values->filter(fn ($v) => $v === 'no')->count(),
                'unknown' => $values->filter(fn ($v) => $v === 'unknown')->count(),
                'unanswered' => $values->filter(fn ($v) => $v === 'unanswered')->count(),
                'answered_total' => $values->filter(fn ($v) => $v !== 'unanswered')->count(),
            ];
        }

        return $counts;
    }

    /**
     * Resolve the caller's requested includes against the allow-list.
     *
     * @return array<string, array{load: array<int, string>, relation: string}>
     */
    protected function resolveIncludes(array $arguments): array
    {
        $requested = $arguments['include'] ?? [];
        if (! is_array($requested)) {
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
