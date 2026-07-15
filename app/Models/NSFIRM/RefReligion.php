<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefReligion extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_religion';

    protected $guarded = [];

    /**
     * Deceased records with this religion.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'religion_code', 'code');
    }
}
