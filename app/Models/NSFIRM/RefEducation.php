<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefEducation extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_education';

    protected $guarded = [];

    /**
     * Deceased records with this education level.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'education_level_code', 'code');
    }
}
