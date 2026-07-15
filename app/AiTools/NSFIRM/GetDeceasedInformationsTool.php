<?php

namespace App\AiTools\NSFIRM;

use App\Models\NSFIRM\DeceasedInformation;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
use Illuminate\Database\Eloquent\Model;
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
            . '(gender, nationality, state, city, manner of death). Use "include" to also pull other '
            . 'related lookups (religion, marital status, occupation, next-of-kin, ward, parent case, '
            . 'etc.) nested under each row\'s "relations" key.';
    }

    /**
     * Allow-listed relationships the AI may request via the "include" argument.
     *
     * @return array<string, array{load: array<int, string>, relation: string}>
     */
    protected function allowedIncludes(): array
    {
        return [
            'case' => ['load' => ['caseRegistration.status:id,status_name', 'caseRegistration.sourceHospital:id,name,facilityCode,state_code'], 'relation' => 'caseRegistration'],
            'identification_type' => ['load' => ['identificationType'], 'relation' => 'identificationType'],
            'postcode' => ['load' => ['postcode'], 'relation' => 'postcode'],
            'marital_status' => ['load' => ['maritalStatus'], 'relation' => 'maritalStatus'],
            'religion' => ['load' => ['religion'], 'relation' => 'religion'],
            'ethnic' => ['load' => ['ethnic'], 'relation' => 'ethnic'],
            'education_level' => ['load' => ['educationLevel'], 'relation' => 'educationLevel'],
            'occupation_status' => ['load' => ['occupationStatus'], 'relation' => 'occupationStatus'],
            'occupation_sector' => ['load' => ['occupationSector'], 'relation' => 'occupationSector'],
            'occupation_type' => ['load' => ['occupationType'], 'relation' => 'occupationType'],
            'next_of_kin_relationship' => ['load' => ['nextOfKinRelationship'], 'relation' => 'nextOfKinRelationship'],
            'next_of_kin_identification_type' => ['load' => ['nextOfKinIdentificationType'], 'relation' => 'nextOfKinIdentificationType'],
            'next_of_kin_city' => ['load' => ['nextOfKinCity'], 'relation' => 'nextOfKinCity'],
            'next_of_kin_state' => ['load' => ['nextOfKinState'], 'relation' => 'nextOfKinState'],
            'next_of_kin_postcode' => ['load' => ['nextOfKinPostcode'], 'relation' => 'nextOfKinPostcode'],
            'death_presentation' => ['load' => ['deathPresentation'], 'relation' => 'deathPresentation'],
            'ward' => ['load' => ['ward'], 'relation' => 'ward'],
            'certified_by_designation' => ['load' => ['certifiedByDesignation'], 'relation' => 'certifiedByDesignation'],
            'certified_by_certifier_designation' => ['load' => ['certifiedByCertifierDesignation'], 'relation' => 'certifiedByCertifierDesignation'],
            'place_of_incident_city' => ['load' => ['placeOfIncidentCity'], 'relation' => 'placeOfIncidentCity'],
            'place_of_incident_state' => ['load' => ['placeOfIncidentState'], 'relation' => 'placeOfIncidentState'],
            'place_of_incident' => ['load' => ['placeOfIncident'], 'relation' => 'placeOfIncident'],
        ];
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
                'include' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'enum' => array_keys($this->allowedIncludes())],
                    'description' => 'Related lookups to eager-load, returned under each row\'s "relations" key. '
                        . 'Options: case, identification_type, postcode, marital_status, religion, ethnic, '
                        . 'education_level, occupation_status, occupation_sector, occupation_type, '
                        . 'next_of_kin_relationship, next_of_kin_identification_type, next_of_kin_city, '
                        . 'next_of_kin_state, next_of_kin_postcode, death_presentation, ward, '
                        . 'certified_by_designation, certified_by_certifier_designation, '
                        . 'place_of_incident_city, place_of_incident_state, place_of_incident.',
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
        $query = DeceasedInformation::query()->with([
            'gender:id,code,name',
            'nationality:id,code,name',
            'state:id,code,name',
            'city:id,code,name',
            'mannerOfDeath:id,code,name',
        ]);

        $includes = $this->resolveIncludes($arguments);
        foreach ($includes as $cfg) {
            $query->with($cfg['load']);
        }

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

        $limit = min(max((int) ($arguments['limit'] ?? 1000), 1), 1000);

        $rows = $query->latest('date_of_death')
            ->limit($limit)
            ->get([
                'id', 'case_id', 'name', 'id_number', 'age', 'gender_code', 'nationality_code',
                'state_code', 'city_code', 'date_of_birth', 'date_of_death', 'time_of_death',
                'cause_of_death', 'manner_of_death', 'post_morten_no', 'post_morten_date',
            ])
            ->map(function (DeceasedInformation $d) use ($includes) {
                $data = $d->attributesToArray();
                $data['gender'] = $d->gender?->name;
                $data['nationality'] = $d->nationality?->name;
                $data['state'] = $d->state?->name;
                $data['city'] = $d->city?->name;
                $data['manner_of_death_label'] = $d->mannerOfDeath?->name;
                $data = $this->attachIncludes($data, $d, $includes);

                return $data;
            });

        return [
            'count' => $rows->count(),
            'deceased_informations' => $rows->all(),
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
