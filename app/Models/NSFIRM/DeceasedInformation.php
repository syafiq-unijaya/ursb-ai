<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeceasedInformation extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'deceased_information';

    protected $guarded = [];

    protected $casts = [
        'int_source' => 'array',
        'filled_from_fields' => 'array',
    ];

    /**
     * The case this deceased record belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(RefGender::class, 'gender_code', 'code');
    }

    public function identificationType(): BelongsTo
    {
        return $this->belongsTo(RefIdentificationType::class, 'identification_type', 'code');
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(RefCountry::class, 'nationality_code', 'code');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(RefCity::class, 'city_code', 'code');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(RefState::class, 'state_code', 'code');
    }

    public function postcode(): BelongsTo
    {
        return $this->belongsTo(RefPostcode::class, 'postcode_code', 'postcode');
    }

    public function maritalStatus(): BelongsTo
    {
        return $this->belongsTo(RefMaritalStatus::class, 'marital_status_code', 'code');
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(RefReligion::class, 'religion_code', 'code');
    }

    public function ethnic(): BelongsTo
    {
        return $this->belongsTo(RefEthnic::class, 'ethnic_code', 'code');
    }

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(RefEducation::class, 'education_level_code', 'code');
    }

    public function occupationStatus(): BelongsTo
    {
        return $this->belongsTo(RefOccupationStatus::class, 'occupation_status', 'code');
    }

    public function occupationSector(): BelongsTo
    {
        return $this->belongsTo(RefOccupationSector::class, 'occupation_sector', 'code');
    }

    public function occupationType(): BelongsTo
    {
        return $this->belongsTo(RefOccupationType::class, 'occupation_type', 'code');
    }

    public function nextOfKinRelationship(): BelongsTo
    {
        return $this->belongsTo(RefRelationship::class, 'next_of_kin_relationship_code', 'code');
    }

    public function nextOfKinIdentificationType(): BelongsTo
    {
        return $this->belongsTo(RefIdentificationType::class, 'next_of_kin_identification_type', 'code');
    }

    public function nextOfKinCity(): BelongsTo
    {
        return $this->belongsTo(RefCity::class, 'next_of_kin_city_code', 'code');
    }

    public function nextOfKinState(): BelongsTo
    {
        return $this->belongsTo(RefState::class, 'next_of_kin_state_code', 'code');
    }

    public function nextOfKinPostcode(): BelongsTo
    {
        return $this->belongsTo(RefPostcode::class, 'next_of_kin_postcode_code', 'postcode');
    }

    public function deathPresentation(): BelongsTo
    {
        return $this->belongsTo(RefPresentation::class, 'death_presentation', 'code');
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(RefFacilityWard::class, 'ward_no', 'id');
    }

    public function certifiedByDesignation(): BelongsTo
    {
        return $this->belongsTo(RefDesignation::class, 'certified_by_designation_code', 'code');
    }

    public function certifiedByCertifierDesignation(): BelongsTo
    {
        return $this->belongsTo(RefCertifierDesignation::class, 'certified_by_certifier_designation_code', 'code');
    }

    public function mannerOfDeath(): BelongsTo
    {
        return $this->belongsTo(RefMannerDeath::class, 'manner_of_death', 'code');
    }

    public function placeOfIncidentCity(): BelongsTo
    {
        return $this->belongsTo(RefCity::class, 'place_of_incident_city_code', 'code');
    }

    public function placeOfIncidentState(): BelongsTo
    {
        return $this->belongsTo(RefState::class, 'place_of_incident_state_code', 'code');
    }

    public function placeOfIncident(): BelongsTo
    {
        return $this->belongsTo(RefPlaceOfIncident::class, 'place_of_incident_code', 'code');
    }
}
