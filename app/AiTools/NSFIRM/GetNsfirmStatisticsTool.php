<?php

namespace App\AiTools\NSFIRM;

use App\Models\NSFIRM\CaseRegistration;
use App\Models\NSFIRM\CaseTypeOfInjury;
use App\Models\NSFIRM\DeceasedInformation;
use App\Models\NSFIRM\RefCountry;
use App\Models\NSFIRM\RefEthnic;
use App\Models\NSFIRM\RefGender;
use App\Models\NSFIRM\RefHospital;
use App\Models\NSFIRM\RefMannerDeath;
use App\Models\NSFIRM\RefMaritalStatus;
use App\Models\NSFIRM\RefPlaceOfIncident;
use App\Models\NSFIRM\RefReligion;
use App\Models\NSFIRM\RefState;
use App\Models\NSFIRM\RefStatus;
use App\Models\NSFIRM\RefTypeOfInjury;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class GetNsfirmStatisticsTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_nsfirm_statistics';
    }

    public function description(): string
    {
        return 'Get aggregated counts (breakdowns) for NSFIRM data instead of raw rows. '
            . 'Choose a dataset (deceased, cases, injuries) and a group_by dimension, and it returns the count '
            . 'per group with resolved labels, sorted by count. Optional date range and name-based filters. '
            . 'Use this for "how many", "breakdown by", "top N", or "count by ..." questions rather than listing rows. '
            . 'Call without arguments to see the available datasets and their group_by options.';
    }

    /**
     * dataset => [model, date_column, groups]. Each group is either a plain column
     * (optionally with a ref resolver [model, codeCol, nameCol]) or a raw SQL expr.
     *
     * @return array<string, array{model: class-string, date_column: string, groups: array<string, array<string, mixed>>}>
     */
    protected function datasets(): array
    {
        return [
            'deceased' => [
                'model' => DeceasedInformation::class,
                'date_column' => 'date_of_death',
                'groups' => [
                    'manner_of_death' => ['column' => 'manner_of_death', 'ref' => [RefMannerDeath::class, 'code', 'name']],
                    'gender' => ['column' => 'gender_code', 'ref' => [RefGender::class, 'code', 'name']],
                    'nationality' => ['column' => 'nationality_code', 'ref' => [RefCountry::class, 'code', 'name']],
                    'state' => ['column' => 'state_code', 'ref' => [RefState::class, 'code', 'name']],
                    'religion' => ['column' => 'religion_code', 'ref' => [RefReligion::class, 'code', 'name']],
                    'ethnic' => ['column' => 'ethnic_code', 'ref' => [RefEthnic::class, 'code', 'name']],
                    'marital_status' => ['column' => 'marital_status_code', 'ref' => [RefMaritalStatus::class, 'code', 'name']],
                    'place_of_incident' => ['column' => 'place_of_incident_code', 'ref' => [RefPlaceOfIncident::class, 'code', 'name']],
                    'year_of_death' => ['expr' => 'YEAR(date_of_death)'],
                    'month_of_death' => ['expr' => "DATE_FORMAT(date_of_death, '%Y-%m')"],
                ],
            ],
            'cases' => [
                'model' => CaseRegistration::class,
                'date_column' => 'date_register',
                'groups' => [
                    'status' => ['column' => 'status_id', 'ref' => [RefStatus::class, 'id', 'status_name']],
                    'hospital' => ['column' => 'source_hospital_id', 'ref' => [RefHospital::class, 'facilityCode', 'name']],
                    'case_type' => ['column' => 'case_type'],
                    'court_ruling' => ['column' => 'court_ruling'],
                    'year_registered' => ['expr' => 'YEAR(date_register)'],
                    'month_registered' => ['expr' => "DATE_FORMAT(date_register, '%Y-%m')"],
                ],
            ],
            'injuries' => [
                'model' => CaseTypeOfInjury::class,
                'date_column' => 'created_at',
                'groups' => [
                    'injury' => ['column' => 'injury_code', 'ref' => [RefTypeOfInjury::class, 'code', 'name']],
                    'active' => ['column' => 'active'],
                ],
            ],
        ];
    }

    public function parameters(): array
    {
        $datasets = $this->datasets();
        $groupOptions = [];
        foreach ($datasets as $cfg) {
            $groupOptions = array_merge($groupOptions, array_keys($cfg['groups']));
        }

        return [
            'type' => 'object',
            'properties' => [
                'dataset' => [
                    'type' => 'string',
                    'enum' => array_keys($datasets),
                    'description' => 'What to count: deceased, cases, or injuries.',
                ],
                'group_by' => [
                    'type' => 'string',
                    'enum' => array_values(array_unique($groupOptions)),
                    'description' => 'Dimension to group by. deceased: manner_of_death, gender, nationality, state, religion, '
                        . 'ethnic, marital_status, place_of_incident, year_of_death, month_of_death. '
                        . 'cases: status, hospital, case_type, court_ruling, year_registered, month_registered. '
                        . 'injuries: injury, active.',
                ],
                'date_from' => ['type' => 'string', 'description' => 'Count records on/after this date (YYYY-MM-DD). deceased=date_of_death, cases=date_register.'],
                'date_to' => ['type' => 'string', 'description' => 'Count records on/before this date (YYYY-MM-DD).'],
                // deceased filters (names, partial match)
                'gender' => ['type' => 'string', 'description' => 'deceased only: filter by gender name.'],
                'state' => ['type' => 'string', 'description' => 'deceased only: filter by state name.'],
                'religion' => ['type' => 'string', 'description' => 'deceased only: filter by religion name.'],
                'manner_of_death' => ['type' => 'string', 'description' => 'deceased only: filter by manner of death name.'],
                // cases filters
                'hospital_name' => ['type' => 'string', 'description' => 'cases only: filter by source hospital name.'],
                'status' => ['type' => 'string', 'description' => 'cases only: filter by workflow status name.'],
                // injuries filters
                'active' => ['type' => 'boolean', 'description' => 'injuries only: filter by active flag.'],
                'injury' => ['type' => 'string', 'description' => 'injuries only: filter by injury type name.'],
                'limit' => ['type' => 'integer', 'description' => 'Max groups to return (default 50, max 200).'],
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
        $datasets = $this->datasets();

        $dataset = $arguments['dataset'] ?? null;
        if (! is_string($dataset) || ! isset($datasets[$dataset])) {
            return [
                'available_datasets' => array_keys($datasets),
                'group_by_options' => array_map(fn ($d) => array_keys($d['groups']), $datasets),
                'hint' => 'Call again with dataset and group_by.',
            ];
        }

        $cfg = $datasets[$dataset];
        $groupBy = $arguments['group_by'] ?? null;
        if (! is_string($groupBy) || ! isset($cfg['groups'][$groupBy])) {
            return [
                'error' => "Invalid or missing group_by for dataset '{$dataset}'.",
                'group_by_options' => array_keys($cfg['groups']),
            ];
        }

        $group = $cfg['groups'][$groupBy];
        $model = $cfg['model'];

        /** @var Builder $query */
        $query = $model::query();

        if (isset($arguments['date_from'])) {
            $query->whereDate($cfg['date_column'], '>=', $arguments['date_from']);
        }
        if (isset($arguments['date_to'])) {
            $query->whereDate($cfg['date_column'], '<=', $arguments['date_to']);
        }
        $this->applyFilters($query, $dataset, $arguments);

        $total = (clone $query)->count();

        $expr = $group['expr'] ?? $group['column'];
        $limit = min(max((int) ($arguments['limit'] ?? 50), 1), 200);

        $grouped = (clone $query)
            ->selectRaw("{$expr} as group_key, COUNT(*) as aggregate")
            ->groupByRaw($expr)
            ->orderByDesc('aggregate')
            ->limit($limit)
            ->get();

        // Resolve code -> name labels when the group has a ref table.
        $labels = [];
        if (isset($group['ref'])) {
            [$refModel, $codeCol, $nameCol] = $group['ref'];
            $labels = $refModel::pluck($nameCol, $codeCol)->all();
        }

        $groups = $grouped->map(function ($row) use ($group, $labels) {
            $key = $row->group_key;
            $label = $key;
            if (isset($group['ref'])) {
                $label = $labels[$key] ?? ($key === null ? '(none)' : $key);
            } elseif ($key === null) {
                $label = '(none)';
            }

            return [
                'key' => $key,
                'label' => $label,
                'count' => (int) $row->aggregate,
            ];
        })->all();

        return [
            'dataset' => $dataset,
            'group_by' => $groupBy,
            'total' => $total,
            'groups' => $groups,
        ];
    }

    /**
     * Apply dataset-specific name filters (partial match via the model's relations).
     */
    protected function applyFilters(Builder $query, string $dataset, array $args): void
    {
        $like = fn (string $rel, string $col, string $val) => $query->whereHas($rel, function ($q) use ($col, $val) {
            $q->where($col, 'like', '%' . $val . '%');
        });

        if ($dataset === 'deceased') {
            if (isset($args['gender'])) {
                $like('gender', 'name', $args['gender']);
            }
            if (isset($args['state'])) {
                $like('state', 'name', $args['state']);
            }
            if (isset($args['religion'])) {
                $like('religion', 'name', $args['religion']);
            }
            if (isset($args['manner_of_death'])) {
                $like('mannerOfDeath', 'name', $args['manner_of_death']);
            }
        } elseif ($dataset === 'cases') {
            if (isset($args['hospital_name'])) {
                $like('sourceHospital', 'name', $args['hospital_name']);
            }
            if (isset($args['status'])) {
                $like('status', 'status_name', $args['status']);
            }
        } elseif ($dataset === 'injuries') {
            if (isset($args['active'])) {
                $query->where('active', (bool) $args['active']);
            }
            if (isset($args['injury'])) {
                $like('injury', 'name', $args['injury']);
            }
        }
    }
}
