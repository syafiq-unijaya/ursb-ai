<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Injury type T1 - Transportation related death.
 *
 * One row per case: `case_id` is UNIQUE, so this is a 1:1 record for the case.
 * ICD column: `icd_11_code_1`.
 *
 * IRREGULARITY: unlike the other sixteen injury_* tables, which each carry an
 * `injury<n>` free-text narrative column alongside `icd_11_code_<n>`, this table
 * has `icd_11_code_1` but NO `injury1` column. Verified against the live schema.
 *
 * Multi-valued detail lives in seven child tables keyed by `case_id`:
 * case_t1_{land,air,water,head,neck,chest,abdomen}_injury.
 */
class InjuryTransportationRelatedDeath extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'injury_transportation_related_death';

    protected $guarded = [];

    /**
     * The case this injury detail belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * Land transport injuries recorded for this case.
     */
    public function landInjuries(): HasMany
    {
        return $this->hasMany(CaseT1LandInjury::class, 'case_id', 'case_id');
    }

    /**
     * Air transport injuries recorded for this case.
     */
    public function airInjuries(): HasMany
    {
        return $this->hasMany(CaseT1AirInjury::class, 'case_id', 'case_id');
    }

    /**
     * Water transport injuries recorded for this case.
     */
    public function waterInjuries(): HasMany
    {
        return $this->hasMany(CaseT1WaterInjury::class, 'case_id', 'case_id');
    }

    /**
     * Head injuries recorded for this case.
     */
    public function headInjuries(): HasMany
    {
        return $this->hasMany(CaseT1HeadInjury::class, 'case_id', 'case_id');
    }

    /**
     * Neck injuries recorded for this case.
     */
    public function neckInjuries(): HasMany
    {
        return $this->hasMany(CaseT1NeckInjury::class, 'case_id', 'case_id');
    }

    /**
     * Chest injuries recorded for this case.
     */
    public function chestInjuries(): HasMany
    {
        return $this->hasMany(CaseT1ChestInjury::class, 'case_id', 'case_id');
    }

    /**
     * Abdomen injuries recorded for this case.
     */
    public function abdomenInjuries(): HasMany
    {
        return $this->hasMany(CaseT1AbdomenInjury::class, 'case_id', 'case_id');
    }
}
