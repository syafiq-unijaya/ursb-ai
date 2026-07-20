<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * T4 lookup: high fall injury.
 */
class RefT4HighFallInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_t4_high_fall_injury';

    protected $guarded = [];

    /**
     * Case rows referencing this lookup value.
     */
    public function highFallInjuries(): HasMany
    {
        return $this->hasMany(CaseT4HighFallInjury::class, 'code', 'code');
    }
}
