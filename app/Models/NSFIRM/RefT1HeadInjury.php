<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * T1 lookup: head injury.
 */
class RefT1HeadInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_t1_head_injury';

    protected $guarded = [];

    /**
     * Case rows referencing this lookup value.
     */
    public function headInjuries(): HasMany
    {
        return $this->hasMany(CaseT1HeadInjury::class, 'code', 'code');
    }
}
