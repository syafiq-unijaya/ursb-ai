<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * T10 lookup: region of injury.
 */
class RefT10RegionOfInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_t10_region_of_injury';

    protected $guarded = [];

    /**
     * Case rows referencing this lookup value.
     */
    public function regionsOfInjury(): HasMany
    {
        return $this->hasMany(CaseT10RegionOfInjury::class, 'code', 'code');
    }
}
