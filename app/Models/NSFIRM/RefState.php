<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefState extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_state';

    protected $guarded = [];

    /**
     * Hospitals located in this state.
     */
    public function hospitals(): HasMany
    {
        return $this->hasMany(RefHospital::class, 'state_code', 'code');
    }

    /**
     * Deceased records whose residence state is this state.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'state_code', 'code');
    }

    /**
     * Deceased records whose next-of-kin state is this state.
     */
    public function nextOfKinDeceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'next_of_kin_state_code', 'code');
    }

    /**
     * Deceased records whose place-of-incident state is this state.
     */
    public function placeOfIncidentDeceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'place_of_incident_state_code', 'code');
    }
}
