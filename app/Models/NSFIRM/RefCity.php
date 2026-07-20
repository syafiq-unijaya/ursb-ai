<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefCity extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_city';

    protected $guarded = [];

    /**
     * Deceased records whose residence city is this city.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'city_code', 'code');
    }

    /**
     * Deceased records whose next-of-kin city is this city.
     */
    public function nextOfKinDeceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'next_of_kin_city_code', 'code');
    }

    /**
     * Deceased records whose place-of-incident city is this city.
     */
    public function placeOfIncidentDeceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'place_of_incident_city_code', 'code');
    }
}
