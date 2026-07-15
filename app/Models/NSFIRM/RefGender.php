<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefGender extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_gender';

    protected $guarded = [];

    /**
     * Deceased records with this gender.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'gender_code', 'code');
    }
}
