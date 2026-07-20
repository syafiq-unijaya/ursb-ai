<?php
namespace App\AiTools\NSFIRM;

use App\Models\NSFIRM\CaseTypeOfInjury;
use App\Models\NSFIRM\RefOverallPopulation;
use App\Models\NSFIRM\ViewCaseReport;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
use Illuminate\Http\Request;

class GetMortalityRateTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_mortality_rate';
    }

    public function description(): string
    {
        return 'Calculate the NSFIRM crude mortality rate: (verified cases / population) x 100,000, '
            . 'expressed as deaths per 100,000 population to 2 decimal places. '
            . 'Only Verified cases count towards the numerator — draft, unverified and deleted cases are never included. '
            . 'Filter by year of death, geography (state, district or hospital), injury type, and manner of death '
            . '(use probable_suicide=true for the probable suicide rate). '
            . 'Pass "breakdown_by" to split the rate by gender, age group, ethnicity, citizenship, state or district — '
            . 'each subgroup is then divided by its OWN population, not the total. '
            . 'Population figures only exist up to 2024, so later years fall back to 2024 and the response says so '
            . 'in "population_year_used". Use this instead of computing rates yourself from raw case counts.';
    }

    /**
     * Deaths per this many people. NSFIRM reports a crude rate per 100,000.
     */
    protected const RATE_BASE = 100000;

    /**
     * Latest year ref_overall_population holds figures for. Anything later reuses it,
     * per the NSFIRM rule that 2025 temporarily uses 2024 population.
     */
    protected const LATEST_POPULATION_YEAR = 2024;

    /**
     * ref_ethnic codes recorded on a case, mapped onto the six population groups
     * in ref_overall_population. The two vocabularies do not match, so a case's
     * ethnic code has to be translated before it can find its denominator.
     *
     * Code 00 (No Information) has no population group and is reported as unmatched.
     *
     * @var array<string, string>
     */
    protected array $ethnicToPopulationGroup = [
        '01' => 'bumi_malay',       // Melayu
        '02' => 'chinese',          // Cina
        '03' => 'indian',           // India
        '04' => 'bumi_other',       // Orang Asli Semenanjung
        '05' => 'bumi_other',       // Bajau
        '06' => 'bumi_other',       // Dusun
        '07' => 'bumi_other',       // Kadazan
        '08' => 'bumi_other',       // Murut
        '10' => 'bumi_other',       // Bumiputera Sabah Lain
        '11' => 'bumi_other',       // Melanau
        '12' => 'bumi_other',       // Kedayan
        '13' => 'bumi_other',       // Iban
        '14' => 'bumi_other',       // Bidayuh
        '15' => 'bumi_other',       // Other Bumiputera (Sarawak)
        '99' => 'other_citizen',    // Lain-Lain
        '17' => 'other_noncitizen', // Bukan Warganegara
    ];

    /**
     * Population ethnic groups that make up the citizen population. Citizenship is not
     * a column in ref_overall_population, so it is derived from the ethnic split.
     *
     * @var array<int, string>
     */
    protected array $citizenGroups = ['bumi_malay', 'bumi_other', 'chinese', 'indian', 'other_citizen'];

    /**
     * Gender codes that have a matching population subgroup. ref_gender also defines
     * R (Undetermined) and 00 (No Information), which the census does not break out.
     *
     * @var array<string, string>
     */
    protected array $genderNames = ['L' => 'Male', 'P' => 'Female'];

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'year' => ['type' => 'integer', 'description' => 'Year of death to calculate the rate for. Defaults to the most recent year with verified cases.'],
                'state' => ['type' => 'string', 'description' => 'Restrict to one state by name (partial match, e.g. "Selangor").'],
                'state_code' => ['type' => 'string', 'description' => 'Restrict to one state by code.'],
                'district_code' => ['type' => 'string', 'description' => 'Restrict to one district by code.'],
                'source_hospital_id' => ['type' => 'string', 'description' => 'Restrict to cases registered by one hospital (facility code). Note: no population figure exists per hospital, so a rate cannot be produced at hospital level — use this for case counts only.'],
                'geography_basis' => [
                    'type' => 'string',
                    'enum' => ['hospital', 'death'],
                    'description' => 'Whether the state/district filter follows the reporting hospital\'s location (default) or the place of death.',
                ],
                'injury_code' => [
                    'type' => 'string',
                    'description' => 'Restrict to one injury type, T1 to T17 (e.g. T5 drowning). Omit for all fatal injuries.',
                ],
                'manner_of_death' => ['type' => 'string', 'description' => 'Restrict to one manner of death code (01 Accidental, 02 Natural, 03 Probable Suicide, 04 Homicide, 05 Undetermined).'],
                'probable_suicide' => ['type' => 'boolean', 'description' => 'Shortcut for manner_of_death = 03. Use this for the probable suicide mortality rate.'],
                'breakdown_by' => [
                    'type' => 'string',
                    'enum' => ['gender', 'age_group', 'ethnicity', 'citizenship', 'state', 'district'],
                    'description' => 'Split the rate by one dimension. Each subgroup is divided by its own population.',
                ],
                'limit' => ['type' => 'integer', 'description' => 'Max breakdown rows to return (default 50, max 200).'],
            ],
            'required' => [],
        ];
    }

    public function authorize(?Request $request = null): bool
    {
        // TODO: NSFIRM scopes reports by the user's access level — National users see all
        // states, State and Hospital users only their own. Add that scoping here (and as a
        // where() on the case query) before exposing this beyond National-level users.
        return $request?->user() !== null;
    }

    public function handle(array $arguments): mixed
    {
        $year = isset($arguments['year']) ? (int) $arguments['year'] : $this->latestCaseYear();
        if ($year === null) {
            return ['error' => 'No verified cases with a recorded year of death were found.'];
        }

        // Population is only published to 2024; later years reuse the latest figures.
        $populationYear = min($year, self::LATEST_POPULATION_YEAR);
        $basis = ($arguments['geography_basis'] ?? 'hospital') === 'death' ? 'death' : 'hospital';
        $breakdown = $arguments['breakdown_by'] ?? null;

        $cases = $this->caseQuery($arguments, $year, $basis);

        if ($breakdown === null) {
            $caseCount = (clone $cases)->count();
            $population = $this->populationFor($arguments, $populationYear);

            return array_merge(
                $this->context($year, $populationYear, $arguments, $basis, null),
                [
                    'cases' => $caseCount,
                    'population' => $population,
                    'mortality_rate_per_100k' => $this->rate($caseCount, $population),
                ]
            );
        }

        return array_merge(
            $this->context($year, $populationYear, $arguments, $basis, $breakdown),
            $this->breakdown($breakdown, $cases, $arguments, $populationYear, $limit = min(max((int) ($arguments['limit'] ?? 50), 1), 200))
        );
    }

    /**
     * The numerator: verified cases only, for the requested year and geography.
     */
    protected function caseQuery(array $arguments, int $year, string $basis): \Illuminate\Database\Eloquent\Builder
    {
        $stateColumn = $basis . '_state_code';
        $districtColumn = $basis . '_district_code';

        $query = ViewCaseReport::query()
            ->verified()
            ->where('date_of_death_year', $year);

        if (isset($arguments['state_code'])) {
            $query->where($stateColumn, $arguments['state_code']);
        }
        if (isset($arguments['state'])) {
            // The view stores only codes, so resolve the name through ref_state.
            $codes = RefOverallPopulation::query()
                ->where('state', 'like', '%' . $arguments['state'] . '%')
                ->distinct()
                ->pluck('state_code');
            $query->whereIn($stateColumn, $codes);
        }
        if (isset($arguments['district_code'])) {
            $query->where($districtColumn, $arguments['district_code']);
        }
        if (isset($arguments['source_hospital_id'])) {
            $query->where('source_hospital_id', $arguments['source_hospital_id']);
        }
        if (!empty($arguments['probable_suicide'])) {
            $query->where('manner_of_death', '03');
        } elseif (isset($arguments['manner_of_death'])) {
            $query->where('manner_of_death', $arguments['manner_of_death']);
        }
        if (isset($arguments['injury_code'])) {
            // Injury type lives outside the view, so restrict by the active rows
            // in case_type_of_injury for that code.
            $query->whereIn('case_id', CaseTypeOfInjury::query()
                    ->where('injury_code', strtoupper((string) $arguments['injury_code']))
                    ->where('active', 1)
                    ->select('case_id'));
        }

        return $query;
    }

    /**
     * The denominator. Every dimension the caller is not breaking out is pinned to its
     * aggregate row, because ref_overall_population stores "overall" totals in the same
     * columns as the breakdown rows — summing without pinning multiplies the population.
     *
     * @param  array<string, string>  $subgroup  extra pins for a single breakdown bucket
     */
    protected function populationFor(array $arguments, int $populationYear, array $subgroup = []): int
    {
        $query = RefOverallPopulation::query()
            ->where('year', $populationYear)
            ->where('is_active', 1);

        if (isset($arguments['state_code'])) {
            $query->where('state_code', $arguments['state_code']);
        }
        if (isset($arguments['state'])) {
            $query->where('state', 'like', '%' . $arguments['state'] . '%');
        }
        if (isset($arguments['district_code'])) {
            $query->where('district_code', $arguments['district_code']);
        }

        $query->where('gender_code', $subgroup['gender_code'] ?? '00');
        $query->where('range_min', $subgroup['range_min'] ?? 'overall');

        if (isset($subgroup['ethnic_in'])) {
            $query->whereIn('ethnic', $subgroup['ethnic_in']);
        } else {
            $query->where('ethnic', $subgroup['ethnic'] ?? 'overall');
        }
        if (isset($subgroup['state_code'])) {
            $query->where('state_code', $subgroup['state_code']);
        }
        if (isset($subgroup['district_code'])) {
            $query->where('district_code', $subgroup['district_code']);
        }

        return (int) $query->sum('population');
    }

    /**
     * Split the rate by one dimension, dividing each subgroup by its own population.
     *
     * @return array<string, mixed>
     */
    protected function breakdown(string $dimension, $cases, array $arguments, int $populationYear, int $limit): array
    {
        $rows = match ($dimension) {
            'gender' => $this->byGender($cases, $arguments, $populationYear),
            'age_group' => $this->byAgeGroup($cases, $arguments, $populationYear),
            'ethnicity' => $this->byEthnicity($cases, $arguments, $populationYear),
            'citizenship' => $this->byCitizenship($cases, $arguments, $populationYear),
            'state' => $this->byGeography($cases, $arguments, $populationYear, 'state'),
            'district' => $this->byGeography($cases, $arguments, $populationYear, 'district'),
        };

        $unmatched = array_values(array_filter($rows, fn($r) => ($r['population'] ?? 0) === 0 && $r['cases'] > 0));
        $rows = array_slice($rows, 0, $limit);

        $result = ['count' => count($rows), 'rates' => $rows];
        if ($unmatched !== []) {
            $result['note'] = 'Some subgroups have no matching population figure, so their rate is reported as 0. '
                . 'Their cases are still counted in the overall rate.';
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function byGender($cases, array $arguments, int $populationYear): array
    {
        $counts = (clone $cases)->selectRaw('gender_code, COUNT(*) AS c')->groupBy('gender_code')->pluck('c', 'gender_code');

        $rows = [];
        foreach ($counts as $code => $count) {
            // Only L and P have a census subgroup; R (Undetermined) and 00 do not.
            $population = isset($this->genderNames[$code])
            ? $this->populationFor($arguments, $populationYear, ['gender_code' => $code])
            : 0;
            $rows[] = [
                'gender_code' => $code,
                'gender' => $this->genderNames[$code] ?? ($code === 'R' ? 'Undetermined' : 'No Information'),
                'cases' => (int) $count,
                'population' => $population,
                'mortality_rate_per_100k' => $this->rate((int) $count, $population),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function byAgeGroup($cases, array $arguments, int $populationYear): array
    {
        $ages = (clone $cases)->whereNotNull('age')->pluck('age');

        // Census bands are 5-year, topping out at 85+.
        $bucketed = [];
        foreach ($ages as $age) {
            $band = min((int) (floor((int) $age / 5) * 5), 85);
            $bucketed[$band] = ($bucketed[$band] ?? 0) + 1;
        }
        ksort($bucketed);

        $rows = [];
        foreach ($bucketed as $band => $count) {
            $population = $this->populationFor($arguments, $populationYear, ['range_min' => (string) $band]);
            $rows[] = [
                'age_group' => $band === 85 ? '85+' : $band . '-' . ($band + 4),
                'cases' => $count,
                'population' => $population,
                'mortality_rate_per_100k' => $this->rate($count, $population),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function byEthnicity($cases, array $arguments, int $populationYear): array
    {
        $counts = (clone $cases)->selectRaw('ethnic_code, COUNT(*) AS c')->groupBy('ethnic_code')->pluck('c', 'ethnic_code');

        // Several ref_ethnic codes collapse into one census group, so aggregate first.
        $grouped = [];
        $unmapped = 0;
        foreach ($counts as $code => $count) {
            $group = $this->ethnicToPopulationGroup[(string) $code] ?? null;
            if ($group === null) {
                $unmapped += (int) $count;

                continue;
            }
            $grouped[$group] = ($grouped[$group] ?? 0) + (int) $count;
        }

        $rows = [];
        foreach ($grouped as $group => $count) {
            $population = $this->populationFor($arguments, $populationYear, ['ethnic' => $group]);
            $rows[] = [
                'ethnic_group' => $group,
                'cases' => $count,
                'population' => $population,
                'mortality_rate_per_100k' => $this->rate($count, $population),
            ];
        }
        if ($unmapped > 0) {
            $rows[] = [
                'ethnic_group' => 'no_information',
                'cases' => $unmapped,
                'population' => 0,
                'mortality_rate_per_100k' => 0.0,
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function byCitizenship($cases, array $arguments, int $populationYear): array
    {
        $counts = (clone $cases)->selectRaw('citizenship, COUNT(*) AS c')->groupBy('citizenship')->pluck('c', 'citizenship');

        $labels = [1 => 'Citizen', 0 => 'Non-citizen', 2 => 'No Information'];
        $rows = [];
        foreach ($counts as $value => $count) {
            // ref_overall_population has no citizenship column; it is derived from the
            // ethnic split, where other_noncitizen is the non-citizen population.
            $population = match ((int) $value) {
                1 => $this->populationFor($arguments, $populationYear, ['ethnic_in' => $this->citizenGroups]),
                0 => $this->populationFor($arguments, $populationYear, ['ethnic' => 'other_noncitizen']),
                default => 0,
            };
            $rows[] = [
                'citizenship' => $labels[(int) $value] ?? 'Unknown',
                'cases' => (int) $count,
                'population' => $population,
                'mortality_rate_per_100k' => $this->rate((int) $count, $population),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function byGeography($cases, array $arguments, int $populationYear, string $level): array
    {
        $basis = ($arguments['geography_basis'] ?? 'hospital') === 'death' ? 'death' : 'hospital';
        $column = $basis . '_' . ($level === 'state' ? 'state_code' : 'district_code');

        $counts = (clone $cases)->selectRaw("$column AS bucket, COUNT(*) AS c")->groupBy('bucket')->pluck('c', 'bucket');

        // Resolve codes to names from the population table, which carries both.
        $names = RefOverallPopulation::query()
            ->where('year', $populationYear)
            ->distinct()
            ->pluck($level, $level . '_code');

        $rows = [];
        foreach ($counts as $code => $count) {
            $population = $this->populationFor($arguments, $populationYear, [$level . '_code' => $code]);
            $rows[] = [
                $level . '_code' => $code,
                $level => $names[$code] ?? null,
                'cases' => (int) $count,
                'population' => $population,
                'mortality_rate_per_100k' => $this->rate((int) $count, $population),
            ];
        }

        usort($rows, fn($a, $b) => $b['mortality_rate_per_100k'] <=> $a['mortality_rate_per_100k']);

        return $rows;
    }

    /**
     * The NSFIRM crude rate. A zero or missing population yields 0 rather than a
     * division error, per the registry's rule.
     */
    protected function rate(int $cases, int $population): float
    {
        if ($population <= 0) {
            return 0.0;
        }

        return round(($cases / $population) * self::RATE_BASE, 2);
    }

    /**
     * The most recent year that has verified cases, used when the caller omits one.
     */
    protected function latestCaseYear(): ?int
    {
        $year = ViewCaseReport::query()->verified()->whereNotNull('date_of_death_year')->max('date_of_death_year');

        return $year === null ? null : (int) $year;
    }

    /**
     * Echo back what was actually calculated, so the figure can never be read out of context.
     *
     * @return array<string, mixed>
     */
    protected function context(int $year, int $populationYear, array $arguments, string $basis, ?string $breakdown): array
    {
        $context = [
            'year' => $year,
            'population_year_used' => $populationYear,
            'formula' => '(verified cases / population) x 100,000',
            'counted' => 'Verified cases only (status_id = 3)',
            'geography_basis' => $basis === 'death' ? 'place of death' : 'reporting hospital location',
        ];

        if ($populationYear !== $year) {
            $context['population_year_note'] = sprintf(
                'Population figures are only published up to %d, so %d population was used for the %d rate.',
                self::LATEST_POPULATION_YEAR,
                $populationYear,
                $year
            );
        }
        if ($breakdown !== null) {
            $context['breakdown_by'] = $breakdown;
            $context['denominator'] = 'Each subgroup is divided by its own population, not the total.';
        }
        if (!empty($arguments['probable_suicide'])) {
            $context['manner_of_death'] = '03 (Probable Suicide)';
        } elseif (isset($arguments['manner_of_death'])) {
            $context['manner_of_death'] = (string) $arguments['manner_of_death'];
        }
        if (isset($arguments['injury_code'])) {
            $context['injury_code'] = strtoupper((string) $arguments['injury_code']);
        }
        if (isset($arguments['source_hospital_id'])) {
            $context['hospital_note'] = 'No population figure exists per hospital, so the rate for a hospital filter is not meaningful — treat the case count as the useful output.';
        }
        foreach (['state', 'state_code', 'district_code'] as $key) {
            if (isset($arguments[$key])) {
                $context[$key] = (string) $arguments[$key];
            }
        }

        return $context;
    }
}
