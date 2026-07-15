<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefCountry extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_country';

    protected $guarded = [];

    /**
     * Deceased records with this nationality.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'nationality_code', 'code');
    }
}
