<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefPlaceOfIncident extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_place_of_incident';

    protected $guarded = [];

    /**
     * Deceased records with this place of incident.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'place_of_incident_code', 'code');
    }
}
