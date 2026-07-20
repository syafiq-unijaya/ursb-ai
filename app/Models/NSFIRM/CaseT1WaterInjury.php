<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * T1 detail row: water transport injury.
 *
 * Multi-row per case (`case_id` is NOT unique). Shape: id, case_id, code.
 * `code` is a foreign key to `ref_t1_water_injury.code`.
 */
class CaseT1WaterInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'case_t1_water_injury';

    protected $guarded = [];

    /**
     * The case this row belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * The lookup value for this row.
     */
    public function ref(): BelongsTo
    {
        return $this->belongsTo(RefT1WaterInjury::class, 'code', 'code');
    }
}
