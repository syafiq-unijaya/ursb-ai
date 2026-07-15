<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefIdentificationType extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_identification_type';

    protected $guarded = [];

    /**
     * Deceased records identified by this identification type.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'identification_type', 'code');
    }

    /**
     * Deceased records whose next-of-kin uses this identification type.
     */
    public function nextOfKinDeceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'next_of_kin_identification_type', 'code');
    }
}
