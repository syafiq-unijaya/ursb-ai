<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefPostcode extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_postcode';

    protected $guarded = [];

    /**
     * Deceased records whose residence postcode is this postcode.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'postcode_code', 'postcode');
    }

    /**
     * Deceased records whose next-of-kin postcode is this postcode.
     */
    public function nextOfKinDeceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'next_of_kin_postcode_code', 'postcode');
    }
}
