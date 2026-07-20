<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * T8 lookup: region of injury.
 */
class RefT8RegionOfInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_t8_region_of_injury';

    protected $guarded = [];

    /**
     * Case rows referencing this lookup value.
     */
    public function regionsOfInjury(): HasMany
    {
        return $this->hasMany(CaseT8RegionOfInjury::class, 'code', 'code');
    }
}
