<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * T9 lookup: region of injury.
 */
class RefT9RegionOfInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_t9_region_of_injury';

    protected $guarded = [];

    /**
     * Case rows referencing this lookup value.
     */
    public function regionsOfInjury(): HasMany
    {
        return $this->hasMany(CaseT9RegionOfInjury::class, 'code', 'code');
    }
}
