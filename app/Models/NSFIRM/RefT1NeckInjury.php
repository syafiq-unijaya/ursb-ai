<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * T1 lookup: neck injury.
 */
class RefT1NeckInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_t1_neck_injury';

    protected $guarded = [];

    /**
     * Case rows referencing this lookup value.
     */
    public function neckInjuries(): HasMany
    {
        return $this->hasMany(CaseT1NeckInjury::class, 'code', 'code');
    }
}
