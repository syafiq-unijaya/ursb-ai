<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * T1 lookup: air transport injury.
 */
class RefT1AirInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_t1_air_injury';

    protected $guarded = [];

    /**
     * Case rows referencing this lookup value.
     */
    public function airInjuries(): HasMany
    {
        return $this->hasMany(CaseT1AirInjury::class, 'code', 'code');
    }
}
