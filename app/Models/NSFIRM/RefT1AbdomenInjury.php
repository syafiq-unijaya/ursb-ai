<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * T1 lookup: abdomen injury.
 */
class RefT1AbdomenInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_t1_abdomen_injury';

    protected $guarded = [];

    /**
     * Case rows referencing this lookup value.
     */
    public function abdomenInjuries(): HasMany
    {
        return $this->hasMany(CaseT1AbdomenInjury::class, 'code', 'code');
    }
}
