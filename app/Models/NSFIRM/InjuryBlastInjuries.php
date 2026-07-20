<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Injury type T13 - Blast injuries.
 *
 * One row per case: `case_id` is UNIQUE, so this is a 1:1 record for the case.
 * ICD column: `icd_11_code_13` (narrative column: `injury13`).
 * Multi-valued regions of injury live in `CaseT13RegionOfInjury` keyed by `case_id`.
 */
class InjuryBlastInjuries extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'injury_blast_injuries';

    protected $guarded = [];

    /**
     * The case this injury detail belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * Regions of injury recorded for this case.
     */
    public function regionsOfInjury(): HasMany
    {
        return $this->hasMany(CaseT13RegionOfInjury::class, 'case_id', 'case_id');
    }
}
