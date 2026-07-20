<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * T4 lookup: low fall injury.
 */
class RefT4LowFallInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_t4_low_fall_injury';

    protected $guarded = [];

    /**
     * Case rows referencing this lookup value.
     */
    public function lowFallInjuries(): HasMany
    {
        return $this->hasMany(CaseT4LowFallInjury::class, 'code', 'code');
    }
}
