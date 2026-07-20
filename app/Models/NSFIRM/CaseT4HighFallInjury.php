<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * T4 detail row: high fall injury.
 *
 * Multi-row per case (`case_id` is NOT unique). Shape: id, case_id, code.
 * `code` is a foreign key to `ref_t4_high_fall_injury.code`.
 */
class CaseT4HighFallInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'case_t4_high_fall_injury';

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
        return $this->belongsTo(RefT4HighFallInjury::class, 'code', 'code');
    }
}
