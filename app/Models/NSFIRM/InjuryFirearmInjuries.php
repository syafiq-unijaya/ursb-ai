<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Injury type T9 - Firearm injuries.
 *
 * One row per case: `case_id` is UNIQUE, so this is a 1:1 record for the case.
 * ICD column: `icd_11_code_9` (narrative column: `injury9`).
 * Multi-valued regions of injury live in `CaseT9RegionOfInjury` keyed by `case_id`.
 */
class InjuryFirearmInjuries extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'injury_firearm_injuries';

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
        return $this->hasMany(CaseT9RegionOfInjury::class, 'case_id', 'case_id');
    }
}
