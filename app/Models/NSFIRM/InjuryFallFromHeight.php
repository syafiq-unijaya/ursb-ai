<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Injury type T4 - Fall from height.
 *
 * One row per case: `case_id` is UNIQUE, so this is a 1:1 record for the case.
 * ICD column: `icd_11_code_4` (narrative column: `injury4`).
 * Multi-valued detail lives in `case_t4_low_fall_injury` and
 * `case_t4_high_fall_injury`, both keyed by `case_id`.
 */
class InjuryFallFromHeight extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'injury_fall_from_height';

    protected $guarded = [];

    /**
     * The case this injury detail belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * Low fall injuries recorded for this case.
     */
    public function lowFallInjuries(): HasMany
    {
        return $this->hasMany(CaseT4LowFallInjury::class, 'case_id', 'case_id');
    }

    /**
     * High fall injuries recorded for this case.
     */
    public function highFallInjuries(): HasMany
    {
        return $this->hasMany(CaseT4HighFallInjury::class, 'case_id', 'case_id');
    }
}
