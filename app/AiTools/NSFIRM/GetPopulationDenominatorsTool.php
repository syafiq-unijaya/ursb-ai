<?php

namespace App\AiTools\NSFIRM;

use App\Models\NSFIRM\RefOverallPopulation;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
use Illuminate\Http\Request;

class GetPopulationDenominatorsTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_population_denominators';
    }

    public function description(): string
    {
        return 'Get Malaysian census population figures to use as denominators when converting NSFIRM case counts '
            . 'into rates (e.g. deaths per 100,000 population). Broken down by state, district, gender, ethnicity, '
            . 'age band and year (2020-2024). '
            . 'By default this returns the true total population for the geography and year requested. '
            . 'To split the figure, pass "breakdown_by" with the dimensions you want (e.g. ["gender"] or '
            . '["state","age_band"]); every dimension you do not list is collapsed to its total so the numbers '
            . 'always sum correctly. Use this together with get_nsfirm_statistics to report rates rather than raw counts.';
    }

    /**
     * The table stores aggregate rows ("overall") in the SAME columns as the breakdown rows.
     * Summing without pinning the unused dimensions to these sentinels multiplies the
     * population roughly 8x. Every query below pins each dimension the caller did not
     * ask to break out.
     *
     * @var array<string, array{column: string, total: string}>
     */
    protected array $dimensions = [
        'gender' => ['column' => 'gender_code', 'total' => '00'],
        'ethnicity' => ['column' => 'ethnic', 'total' => 'overall'],
        'age_band' => ['column' => 'range_min', 'total' => 'overall'],
    ];

    /**
     * Gender is stored by code; expose the words instead.
     *
     * @var array<string, string>
     */
    protected array $genderCodes = ['male' => 'L', 'female' => 'P', 'both' => '00'];

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'year' => ['type' => 'integer', 'description' => 'Census year, 2020 to 2024. Defaults to the latest available (2024).'],
                'state' => ['type' => 'string', 'description' => 'Filter by state name (partial match, e.g. "Selangor", "Johor").'],
                'state_code' => ['type' => 'string', 'description' => 'Filter by state code.'],
                'district' => ['type' => 'string', 'description' => 'Filter by district name (partial match, e.g. "Batu Pahat").'],
                'gender' => [
                    'type' => 'string',
                    'enum' => ['male', 'female', 'both'],
                    'description' => 'Restrict to one gender. Defaults to both combined.',
                ],
                'ethnicity' => [
                    'type' => 'string',
                    'enum' => ['bumi_malay', 'bumi_other', 'chinese', 'indian', 'other_citizen', 'other_noncitizen', 'overall'],
                    'description' => 'Restrict to one ethnic group. Defaults to all combined.',
                ],
                'age_min' => ['type' => 'integer', 'description' => 'Lower bound of the age band to restrict to (bands are 5-year: 0, 5, 10 ... 85).'],
                'age_max' => ['type' => 'integer', 'description' => 'Upper bound of the age range to restrict to. Combined with age_min this sums the covered bands.'],
                'breakdown_by' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'enum' => ['state', 'district', 'gender', 'ethnicity', 'age_band']],
                    'description' => 'Split the total by these dimensions. Anything not listed is collapsed to its '
                        . 'total, so the returned figures always sum to the overall population without double counting.',
                ],
                'limit' => ['type' => 'integer', 'description' => 'Max grouped rows to return (default 200, max 1000).'],
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
        $year = (int) ($arguments['year'] ?? 2024);
        $breakdown = array_values(array_filter(
            (array) ($arguments['breakdown_by'] ?? []),
            fn ($d) => in_array($d, ['state', 'district', 'gender', 'ethnicity', 'age_band'], true)
        ));

        $query = RefOverallPopulation::query()
            ->where('year', $year)
            ->where('is_active', 1);

        if (isset($arguments['state'])) {
            $query->where('state', 'like', '%' . $arguments['state'] . '%');
        }
        if (isset($arguments['state_code'])) {
            $query->where('state_code', $arguments['state_code']);
        }
        if (isset($arguments['district'])) {
            $query->where('district', 'like', '%' . $arguments['district'] . '%');
        }

        // Gender: an explicit filter pins the value; otherwise pin to the combined row
        // unless the caller asked to break gender out.
        if (isset($arguments['gender'])) {
            $query->where('gender_code', $this->genderCodes[$arguments['gender']] ?? '00');
        } elseif (! in_array('gender', $breakdown, true)) {
            $query->where('gender_code', $this->dimensions['gender']['total']);
        } else {
            // Breaking out gender means excluding the combined row, or it double counts.
            $query->where('gender_code', '!=', $this->dimensions['gender']['total']);
        }

        if (isset($arguments['ethnicity'])) {
            $query->where('ethnic', $arguments['ethnicity']);
        } elseif (! in_array('ethnicity', $breakdown, true)) {
            $query->where('ethnic', $this->dimensions['ethnicity']['total']);
        } else {
            $query->where('ethnic', '!=', $this->dimensions['ethnicity']['total']);
        }

        // Age bands are stored as varchar with an "overall" sentinel, so numeric
        // comparison has to exclude that row explicitly before casting.
        $hasAgeFilter = isset($arguments['age_min']) || isset($arguments['age_max']);
        if ($hasAgeFilter) {
            $query->where('range_min', '!=', $this->dimensions['age_band']['total']);
            if (isset($arguments['age_min'])) {
                $query->whereRaw('CAST(range_min AS UNSIGNED) >= ?', [(int) $arguments['age_min']]);
            }
            if (isset($arguments['age_max'])) {
                $query->whereRaw('CAST(range_min AS UNSIGNED) <= ?', [(int) $arguments['age_max']]);
            }
        } elseif (! in_array('age_band', $breakdown, true)) {
            $query->where('range_min', $this->dimensions['age_band']['total']);
        } else {
            $query->where('range_min', '!=', $this->dimensions['age_band']['total']);
        }

        $groupColumns = $this->groupColumns($breakdown);

        if ($groupColumns === []) {
            return [
                'year' => $year,
                'filters_applied' => $this->describeFilters($arguments, $breakdown),
                'population' => (int) $query->sum('population'),
            ];
        }

        $limit = min(max((int) ($arguments['limit'] ?? 200), 1), 1000);

        $rows = $query->selectRaw(implode(', ', $groupColumns) . ', SUM(population) AS population')
            ->groupBy(array_map(fn ($c) => explode(' AS ', $c)[0], $groupColumns))
            ->orderByDesc('population')
            ->limit($limit)
            ->get()
            ->map(function ($row) use ($breakdown) {
                $data = $row->only(array_map(fn ($d) => $this->outputKey($d), $breakdown));
                if (in_array('gender', $breakdown, true)) {
                    $data['gender'] = $row->gender;
                }
                if (in_array('age_band', $breakdown, true)) {
                    $data['age_band'] = $row->range_min . '-' . $row->range_max;
                }
                $data['population'] = (int) $row->population;

                return $data;
            });

        return [
            'year' => $year,
            'breakdown_by' => $breakdown,
            'filters_applied' => $this->describeFilters($arguments, $breakdown),
            'count' => $rows->count(),
            'total_population' => $rows->sum('population'),
            'populations' => $rows->all(),
        ];
    }

    /**
     * Map the requested breakdown dimensions onto the columns to select and group by.
     *
     * @param  array<int, string>  $breakdown
     * @return array<int, string>
     */
    protected function groupColumns(array $breakdown): array
    {
        $map = [
            'state' => ['state', 'state_code'],
            'district' => ['district', 'district_code'],
            'gender' => ['gender', 'gender_code'],
            'ethnicity' => ['ethnic'],
            'age_band' => ['range_min', 'range_max'],
        ];

        $columns = [];
        foreach ($breakdown as $dimension) {
            foreach ($map[$dimension] ?? [] as $column) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    /**
     * The key each dimension is reported under.
     */
    protected function outputKey(string $dimension): string
    {
        return match ($dimension) {
            'ethnicity' => 'ethnic',
            default => $dimension,
        };
    }

    /**
     * Spell out which dimensions were collapsed to a total, so the caller can see
     * that e.g. an unfiltered request really is the whole population.
     *
     * @param  array<int, string>  $breakdown
     * @return array<string, string>
     */
    protected function describeFilters(array $arguments, array $breakdown): array
    {
        $applied = [];
        foreach (['gender', 'ethnicity'] as $dimension) {
            if (isset($arguments[$dimension])) {
                $applied[$dimension] = (string) $arguments[$dimension];
            } elseif (! in_array($dimension, $breakdown, true)) {
                $applied[$dimension] = 'all combined';
            }
        }
        if (isset($arguments['age_min']) || isset($arguments['age_max'])) {
            $applied['age_band'] = ($arguments['age_min'] ?? 0) . '-' . ($arguments['age_max'] ?? 'max');
        } elseif (! in_array('age_band', $breakdown, true)) {
            $applied['age_band'] = 'all ages';
        }
        foreach (['state', 'state_code', 'district'] as $key) {
            if (isset($arguments[$key])) {
                $applied[$key] = (string) $arguments[$key];
            }
        }

        return $applied;
    }
}
