<?php

namespace App\AiTools\NSFIRM;

use App\Models\NSFIRM\RefCertifierDesignation;
use App\Models\NSFIRM\RefCity;
use App\Models\NSFIRM\RefCountry;
use App\Models\NSFIRM\RefDesignation;
use App\Models\NSFIRM\RefEducation;
use App\Models\NSFIRM\RefEthnic;
use App\Models\NSFIRM\RefFacilityWard;
use App\Models\NSFIRM\RefGender;
use App\Models\NSFIRM\RefHospital;
use App\Models\NSFIRM\RefIdentificationType;
use App\Models\NSFIRM\RefMannerDeath;
use App\Models\NSFIRM\RefMaritalStatus;
use App\Models\NSFIRM\RefOccupationSector;
use App\Models\NSFIRM\RefOccupationStatus;
use App\Models\NSFIRM\RefOccupationType;
use App\Models\NSFIRM\RefPlaceOfIncident;
use App\Models\NSFIRM\RefPostcode;
use App\Models\NSFIRM\RefPresentation;
use App\Models\NSFIRM\RefRelationship;
use App\Models\NSFIRM\RefReligion;
use App\Models\NSFIRM\RefState;
use App\Models\NSFIRM\RefStatus;
use App\Models\NSFIRM\RefTypeOfInjury;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
use Illuminate\Http\Request;

class GetNsfirmReferencesTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_nsfirm_references';
    }

    public function description(): string
    {
        return 'List the reference/lookup values used across the NSFIRM system (codes and their human-readable names). '
            . 'Use this FIRST to discover valid options or to map a name to a code before filtering the other tools '
            . '(e.g. find the state code for "Selangor", or list every manner-of-death option). '
            . 'Call without arguments to see which reference types are available.';
    }

    /**
     * Map of reference key => [model, columns to return, column to search/order by].
     *
     * @return array<string, array{model: class-string, columns: array<int, string>, search: string}>
     */
    protected function references(): array
    {
        return [
            'gender' => ['model' => RefGender::class, 'columns' => ['code', 'name'], 'search' => 'name'],
            'nationality' => ['model' => RefCountry::class, 'columns' => ['code', 'name'], 'search' => 'name'],
            'state' => ['model' => RefState::class, 'columns' => ['code', 'name'], 'search' => 'name'],
            'city' => ['model' => RefCity::class, 'columns' => ['code', 'name', 'state_code'], 'search' => 'name'],
            'religion' => ['model' => RefReligion::class, 'columns' => ['code', 'name'], 'search' => 'name'],
            'marital_status' => ['model' => RefMaritalStatus::class, 'columns' => ['code', 'name'], 'search' => 'name'],
            'ethnic' => ['model' => RefEthnic::class, 'columns' => ['code', 'name'], 'search' => 'name'],
            'education' => ['model' => RefEducation::class, 'columns' => ['code', 'name'], 'search' => 'name'],
            'manner_of_death' => ['model' => RefMannerDeath::class, 'columns' => ['code', 'name'], 'search' => 'name'],
            'presentation' => ['model' => RefPresentation::class, 'columns' => ['code', 'name', 'short_name'], 'search' => 'name'],
            'relationship' => ['model' => RefRelationship::class, 'columns' => ['code', 'name'], 'search' => 'name'],
            'identification_type' => ['model' => RefIdentificationType::class, 'columns' => ['code', 'name'], 'search' => 'name'],
            'occupation_status' => ['model' => RefOccupationStatus::class, 'columns' => ['code', 'name'], 'search' => 'name'],
            'occupation_sector' => ['model' => RefOccupationSector::class, 'columns' => ['code', 'name'], 'search' => 'name'],
            'occupation_type' => ['model' => RefOccupationType::class, 'columns' => ['code', 'name', 'type'], 'search' => 'name'],
            'place_of_incident' => ['model' => RefPlaceOfIncident::class, 'columns' => ['code', 'name'], 'search' => 'name'],
            'designation' => ['model' => RefDesignation::class, 'columns' => ['code', 'name'], 'search' => 'name'],
            'certifier_designation' => ['model' => RefCertifierDesignation::class, 'columns' => ['code', 'name'], 'search' => 'name'],
            'type_of_injury' => ['model' => RefTypeOfInjury::class, 'columns' => ['code', 'name', 'short_name'], 'search' => 'name'],
            'case_status' => ['model' => RefStatus::class, 'columns' => ['id', 'status_name'], 'search' => 'status_name'],
            'hospital' => ['model' => RefHospital::class, 'columns' => ['facilityCode', 'name', 'state_code'], 'search' => 'name'],
            'postcode' => ['model' => RefPostcode::class, 'columns' => ['postcode', 'city_code'], 'search' => 'postcode'],
            'facility_ward' => ['model' => RefFacilityWard::class, 'columns' => ['id', 'ward_name', 'ward_code', 'facility_code'], 'search' => 'ward_name'],
        ];
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'reference' => [
                    'type' => 'string',
                    'enum' => array_keys($this->references()),
                    'description' => 'Which reference list to return. Omit to get the list of available reference types.',
                ],
                'search' => ['type' => 'string', 'description' => 'Partial name/code to narrow the list (recommended for large lists like city, postcode, hospital).'],
                'limit' => ['type' => 'integer', 'description' => 'Max rows to return (default 200, max 1000).'],
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
        $references = $this->references();

        // No/unknown reference: help the AI discover what's available.
        $key = $arguments['reference'] ?? null;
        if (! is_string($key) || ! isset($references[$key])) {
            return [
                'available_references' => array_keys($references),
                'hint' => 'Call again with reference=<one of the above>, optionally with search to narrow.',
            ];
        }

        $cfg = $references[$key];
        $limit = min(max((int) ($arguments['limit'] ?? 200), 1), 1000);

        $query = $cfg['model']::query();

        if (isset($arguments['search']) && $arguments['search'] !== '') {
            $term = '%' . $arguments['search'] . '%';
            $search = $cfg['search'];
            $query->where(function ($q) use ($cfg, $term, $search) {
                $q->where($search, 'like', $term);
                // Also match on the code column when it isn't already the search column.
                if (in_array('code', $cfg['columns'], true) && $search !== 'code') {
                    $q->orWhere('code', 'like', $term);
                }
            });
        }

        $values = $query->orderBy($cfg['search'])
            ->limit($limit)
            ->get($cfg['columns'])
            ->toArray();

        return [
            'reference' => $key,
            'count' => count($values),
            'values' => $values,
        ];
    }
}
