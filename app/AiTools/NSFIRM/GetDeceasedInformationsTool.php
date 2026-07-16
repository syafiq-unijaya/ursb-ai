<?php

namespace App\AiTools\NSFIRM;

use App\Models\NSFIRM\DeceasedInformation;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class GetDeceasedInformationsTool implements ToolInterface
{
    /**
     * Columns always returned. Foreign keys needed by an "include" are added on demand.
     *
     * @var array<int, string>
     */
    protected array $baseColumns = [
        'id', 'case_id', 'name', 'id_number', 'age', 'gender_code', 'nationality_code',
        'state_code', 'city_code', 'religion_code', 'date_of_birth', 'date_of_death', 'time_of_death',
        'cause_of_death', 'manner_of_death', 'post_morten_no', 'post_morten_date',
    ];

    public function name(): string
    {
        return 'get_deceased_informations';
    }

    public function description(): string
    {
        return 'Look up deceased person records for forensic cases in the NSFIRM system. '
            . 'Filter by case id, IC/id number, name (partial), gender, nationality, state, city, '
            . 'religion, manner of death, or date of death. Reference codes are resolved to readable labels '
            . '(gender, nationality, state, city, religion, manner of death). Use "include" to also pull other '
            . 'related lookups (marital status, ethnic, education, occupation, next-of-kin, ward, parent case, '
            . 'etc.) nested under each row\'s "relations" key.';
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
            'identification_type' => ['load' => ['identificationType'], 'relation' => 'identificationType', 'fk' => ['identification_type']],
            'postcode' => ['load' => ['postcode'], 'relation' => 'postcode', 'fk' => ['postcode_code']],
            'marital_status' => ['load' => ['maritalStatus'], 'relation' => 'maritalStatus', 'fk' => ['marital_status_code']],
            'ethnic' => ['load' => ['ethnic'], 'relation' => 'ethnic', 'fk' => ['ethnic_code']],
            'education_level' => ['load' => ['educationLevel'], 'relation' => 'educationLevel', 'fk' => ['education_level_code']],
            'occupation_status' => ['load' => ['occupationStatus'], 'relation' => 'occupationStatus', 'fk' => ['occupation_status']],
            'occupation_sector' => ['load' => ['occupationSector'], 'relation' => 'occupationSector', 'fk' => ['occupation_sector']],
            'occupation_type' => ['load' => ['occupationType'], 'relation' => 'occupationType', 'fk' => ['occupation_type']],
            'next_of_kin_relationship' => ['load' => ['nextOfKinRelationship'], 'relation' => 'nextOfKinRelationship', 'fk' => ['next_of_kin_relationship_code']],
            'next_of_kin_identification_type' => ['load' => ['nextOfKinIdentificationType'], 'relation' => 'nextOfKinIdentificationType', 'fk' => ['next_of_kin_identification_type']],
            'next_of_kin_city' => ['load' => ['nextOfKinCity'], 'relation' => 'nextOfKinCity', 'fk' => ['next_of_kin_city_code']],
            'next_of_kin_state' => ['load' => ['nextOfKinState'], 'relation' => 'nextOfKinState', 'fk' => ['next_of_kin_state_code']],
            'next_of_kin_postcode' => ['load' => ['nextOfKinPostcode'], 'relation' => 'nextOfKinPostcode', 'fk' => ['next_of_kin_postcode_code']],
            'death_presentation' => ['load' => ['deathPresentation'], 'relation' => 'deathPresentation', 'fk' => ['death_presentation']],
            'ward' => ['load' => ['ward'], 'relation' => 'ward', 'fk' => ['ward_no']],
            'certified_by_designation' => ['load' => ['certifiedByDesignation'], 'relation' => 'certifiedByDesignation', 'fk' => ['certified_by_designation_code']],
            'certified_by_certifier_designation' => ['load' => ['certifiedByCertifierDesignation'], 'relation' => 'certifiedByCertifierDesignation', 'fk' => ['certified_by_certifier_designation_code']],
            'place_of_incident_city' => ['load' => ['placeOfIncidentCity'], 'relation' => 'placeOfIncidentCity', 'fk' => ['place_of_incident_city_code']],
            'place_of_incident_state' => ['load' => ['placeOfIncidentState'], 'relation' => 'placeOfIncidentState', 'fk' => ['place_of_incident_state_code']],
            'place_of_incident' => ['load' => ['placeOfIncident'], 'relation' => 'placeOfIncident', 'fk' => ['place_of_incident_code']],
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
                'gender' => ['type' => 'string', 'description' => 'Filter by gender name (partial match, e.g. "Male").'],
                'gender_code' => ['type' => 'string', 'description' => 'Filter by gender code (ref_gender).'],
                'nationality' => ['type' => 'string', 'description' => 'Filter by nationality/country name (partial match, e.g. "Malaysia").'],
                'nationality_code' => ['type' => 'string', 'description' => 'Filter by nationality/country code (ref_country).'],
                'state' => ['type' => 'string', 'description' => 'Filter by state name (partial match, e.g. "Selangor").'],
                'state_code' => ['type' => 'string', 'description' => 'Filter by state code (ref_state).'],
                'city' => ['type' => 'string', 'description' => 'Filter by city name (partial match).'],
                'city_code' => ['type' => 'string', 'description' => 'Filter by city code (ref_city).'],
                'religion' => ['type' => 'string', 'description' => 'Filter by religion name (partial match, e.g. "Islam", "Buddha").'],
                'religion_code' => ['type' => 'string', 'description' => 'Filter by religion code (ref_religion).'],
                'manner_of_death_name' => ['type' => 'string', 'description' => 'Filter by manner of death name (partial match, e.g. "Suicide", "Homicide").'],
                'manner_of_death' => ['type' => 'string', 'description' => 'Filter by manner of death code (ref_manner_death).'],
                'date_of_death' => ['type' => 'string', 'description' => 'Filter by exact date of death (YYYY-MM-DD).'],
                'date_of_death_from' => ['type' => 'string', 'description' => 'Date of death on/after this date (YYYY-MM-DD).'],
                'date_of_death_to' => ['type' => 'string', 'description' => 'Date of death on/before this date (YYYY-MM-DD).'],
                'include' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'enum' => array_keys($this->allowedIncludes())],
                    'description' => 'Related lookups to eager-load, returned under each row\'s "relations" key. '
                        . 'Options: case, identification_type, postcode, marital_status, ethnic, '
                        . 'education_level, occupation_status, occupation_sector, occupation_type, '
                        . 'next_of_kin_relationship, next_of_kin_identification_type, next_of_kin_city, '
                        . 'next_of_kin_state, next_of_kin_postcode, death_presentation, ward, '
                        . 'certified_by_designation, certified_by_certifier_designation, '
                        . 'place_of_incident_city, place_of_incident_state, place_of_incident. '
                        . '(gender, nationality, state, city, religion and manner of death are always returned as labels.)',
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
            'religion:id,code,name',
            'mannerOfDeath:id,code,name',
        ]);

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
        if (isset($arguments['gender'])) {
            $query->whereHas('gender', fn ($q) => $q->where('name', 'like', '%' . $arguments['gender'] . '%'));
        }
        if (isset($arguments['nationality_code'])) {
            $query->where('nationality_code', $arguments['nationality_code']);
        }
        if (isset($arguments['nationality'])) {
            $query->whereHas('nationality', fn ($q) => $q->where('name', 'like', '%' . $arguments['nationality'] . '%'));
        }
        if (isset($arguments['state_code'])) {
            $query->where('state_code', $arguments['state_code']);
        }
        if (isset($arguments['state'])) {
            $query->whereHas('state', fn ($q) => $q->where('name', 'like', '%' . $arguments['state'] . '%'));
        }
        if (isset($arguments['city_code'])) {
            $query->where('city_code', $arguments['city_code']);
        }
        if (isset($arguments['city'])) {
            $query->whereHas('city', fn ($q) => $q->where('name', 'like', '%' . $arguments['city'] . '%'));
        }
        if (isset($arguments['religion_code'])) {
            $query->where('religion_code', $arguments['religion_code']);
        }
        if (isset($arguments['religion'])) {
            $query->whereHas('religion', fn ($q) => $q->where('name', 'like', '%' . $arguments['religion'] . '%'));
        }
        if (isset($arguments['manner_of_death'])) {
            $query->where('manner_of_death', $arguments['manner_of_death']);
        }
        if (isset($arguments['manner_of_death_name'])) {
            $query->whereHas('mannerOfDeath', fn ($q) => $q->where('name', 'like', '%' . $arguments['manner_of_death_name'] . '%'));
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
            ->get($columns)
            ->map(function (DeceasedInformation $d) use ($includes) {
                $data = $d->attributesToArray();
                $data['gender'] = $d->gender?->name;
                $data['nationality'] = $d->nationality?->name;
                $data['state'] = $d->state?->name;
                $data['city'] = $d->city?->name;
                $data['religion'] = $d->religion?->name;
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
     * @return array<string, array{load: array<int, string>, relation: string, fk: array<int, string>}>
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
     * @param  array<string, array{load: array<int, string>, relation: string, fk: array<int, string>}>  $includes
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
