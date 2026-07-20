<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Injury type T5 - Drowning.
 *
 * One row per case: `case_id` is UNIQUE, so this is a 1:1 record for the case.
 * ICD column: `icd_11_code_5` (narrative column: `injury5`).
 */
class InjuryDrowning extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'injury_drowning';

    protected $guarded = [];

    /**
     * The case this injury detail belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }
}
