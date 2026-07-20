<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * T1 lookup: water transport injury.
 */
class RefT1WaterInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_t1_water_injury';

    protected $guarded = [];

    /**
     * Case rows referencing this lookup value.
     */
    public function waterInjuries(): HasMany
    {
        return $this->hasMany(CaseT1WaterInjury::class, 'code', 'code');
    }
}
