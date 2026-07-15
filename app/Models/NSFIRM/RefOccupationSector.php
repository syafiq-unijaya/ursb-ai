<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefOccupationSector extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_occupation_sector';

    protected $guarded = [];

    /**
     * Deceased records in this occupation sector.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'occupation_sector', 'code');
    }
}
