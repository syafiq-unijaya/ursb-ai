<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * T1 lookup: land transport injury.
 */
class RefT1LandInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_t1_land_injury';

    protected $guarded = [];

    /**
     * Case rows referencing this lookup value.
     */
    public function landInjuries(): HasMany
    {
        return $this->hasMany(CaseT1LandInjury::class, 'code', 'code');
    }
}
