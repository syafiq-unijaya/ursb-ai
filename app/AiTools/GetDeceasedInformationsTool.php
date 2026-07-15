<?php

namespace App\AiTools;

use App\Models\NSFIRM\DeceasedInformation;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
use Illuminate\Http\Request;

class GetDeceasedInformationsTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_deceased_informations';
    }

    public function description(): string
    {
        return 'Look up deceased person records for forensic cases in the NSFIRM system. '
            . 'Filter by case id, IC/id number, name (partial), gender, nationality, state, city, '
            . 'manner of death, or date of death. Reference codes are resolved to readable labels '
            . '(gender, nationality, state, city, manner of death). Use for demographic or '
            . 'cause/manner-of-death questions about the deceased.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'case_id' => ['type' => 'string', 'description' => 'Filter by the case id.'],
                'id_number' => ['type' => 'string', 'description' => 'Exact or partial IC / id number.'],
                'name' => ['type' => 'string', 'description' => 'Full or partial name of the deceased.'],
                'gender_code' => ['type' => 'string', 'description' => 'Filter by gender code (ref_gender).'],
                'nationality_code' => ['type' => 'string', 'description' => 'Filter by nationality/country code (ref_country).'],
                'state_code' => ['type' => 'string', 'description' => 'Filter by state code (ref_state).'],
                'city_code' => ['type' => 'string', 'description' => 'Filter by city code (ref_city).'],
                'manner_of_death' => ['type' => 'string', 'description' => 'Filter by manner of death code (ref_manner_death).'],
                'date_of_death' => ['type' => 'string', 'description' => 'Filter by exact date of death (YYYY-MM-DD).'],
                'date_of_death_from' => ['type' => 'string', 'description' => 'Date of death on/after this date (YYYY-MM-DD).'],
                'date_of_death_to' => ['type' => 'string', 'description' => 'Date of death on/before this date (YYYY-MM-DD).'],
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
        $query = DeceasedInformation::query()->with([
            'gender:id,code,name',
            'nationality:id,code,name',
            'state:id,code,name',
            'city:id,code,name',
            'mannerOfDeath:id,code,name',
        ]);

        // TODO: scope this query to the caller's authorised cases if required.
        if (isset($arguments['case_id'])) {
            $query->where('case_id', $arguments['case_id']);
        }
        if (isset($arguments['id_number'])) {
            $query->where('id_number', 'like', '%' . $arguments['id_number'] . '%');
        }
        if (isset($arguments['name'])) {
            $query->where('name', 'like', '%' . $arguments['name'] . '%');
        }
        if (isset($arguments['gender_code'])) {
            $query->where('gender_code', $arguments['gender_code']);
        }
        if (isset($arguments['nationality_code'])) {
            $query->where('nationality_code', $arguments['nationality_code']);
        }
        if (isset($arguments['state_code'])) {
            $query->where('state_code', $arguments['state_code']);
        }
        if (isset($arguments['city_code'])) {
            $query->where('city_code', $arguments['city_code']);
        }
        if (isset($arguments['manner_of_death'])) {
            $query->where('manner_of_death', $arguments['manner_of_death']);
        }
        if (isset($arguments['date_of_death'])) {
            $query->whereDate('date_of_death', $arguments['date_of_death']);
        }
        if (isset($arguments['date_of_death_from'])) {
            $query->whereDate('date_of_death', '>=', $arguments['date_of_death_from']);
        }
        if (isset($arguments['date_of_death_to'])) {
            $query->whereDate('date_of_death', '<=', $arguments['date_of_death_to']);
        }

        $limit = min(max((int) ($arguments['limit'] ?? 25), 1), 100);

        $rows = $query->latest('date_of_death')
            ->limit($limit)
            ->get([
                'id', 'case_id', 'name', 'id_number', 'age', 'gender_code', 'nationality_code',
                'state_code', 'city_code', 'date_of_birth', 'date_of_death', 'time_of_death',
                'cause_of_death', 'manner_of_death', 'post_morten_no', 'post_morten_date',
            ])
            ->map(function (DeceasedInformation $d) {
                $data = $d->toArray();
                // Keep the raw code (the relation key collides with the column name in toArray()).
                $data['manner_of_death'] = $d->getAttributes()['manner_of_death'] ?? null;
                $data['gender'] = $d->gender?->name;
                $data['nationality'] = $d->nationality?->name;
                $data['state'] = $d->state?->name;
                $data['city'] = $d->city?->name;
                $data['manner_of_death_label'] = $d->mannerOfDeath?->name;

                return $data;
            });

        return [
            'count' => $rows->count(),
            'deceased_informations' => $rows->all(),
        ];
    }
}
