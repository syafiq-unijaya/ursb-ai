<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Injury type T16 - Injury not elsewhere classified.
 *
 * One row per case: `case_id` is UNIQUE, so this is a 1:1 record for the case.
 * ICD column: `icd_11_code_16` (narrative column: `injury16`).
 */
class InjuryNotElsewhereClassified extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'injury_not_elsewhere_classified';

    protected $guarded = [];

    /**
     * The case this injury detail belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }
}
