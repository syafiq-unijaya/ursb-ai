<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefEthnic extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_ethnic';

    protected $guarded = [];

    /**
     * Deceased records with this ethnicity.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'ethnic_code', 'code');
    }
}
