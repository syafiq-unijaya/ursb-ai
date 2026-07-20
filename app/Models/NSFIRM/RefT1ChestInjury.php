<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * T1 lookup: chest injury.
 */
class RefT1ChestInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_t1_chest_injury';

    protected $guarded = [];

    /**
     * Case rows referencing this lookup value.
     */
    public function chestInjuries(): HasMany
    {
        return $this->hasMany(CaseT1ChestInjury::class, 'code', 'code');
    }
}
