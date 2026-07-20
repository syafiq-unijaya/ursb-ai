<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskFactor extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'risk_factor';

    protected $guarded = [];

    /**
     * The case this risk factor record belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * Mental health problems recorded for this case.
     */
    public function mentalHealthProblems(): HasMany
    {
        return $this->hasMany(RiskFactorMentalHealthProblem::class, 'case_id', 'case_id');
    }

    /**
     * Mental health treatments recorded for this case.
     */
    public function mentalHealthTreatments(): HasMany
    {
        return $this->hasMany(RiskFactorMentalHealthTreatment::class, 'case_id', 'case_id');
    }

    /**
     * Past suicide attempts recorded for this case.
     */
    public function suicideAttempts(): HasMany
    {
        return $this->hasMany(RiskFactorSuicideAttempt::class, 'case_id', 'case_id');
    }

    /**
     * Suicide notes recorded for this case.
     */
    public function suicideNotes(): HasMany
    {
        return $this->hasMany(RiskFactorSuicideNote::class, 'case_id', 'case_id');
    }

    /**
     * Physical health problems recorded for this case.
     */
    public function physicalHealthProblems(): HasMany
    {
        return $this->hasMany(RiskFactorPhysicalHealthProblem::class, 'case_id', 'case_id');
    }

    /**
     * Social problems recorded for this case.
     */
    public function socialProblems(): HasMany
    {
        return $this->hasMany(RiskFactorSocialProblem::class, 'case_id', 'case_id');
    }

    /**
     * Addiction problems recorded for this case.
     */
    public function addictionProblems(): HasMany
    {
        return $this->hasMany(RiskFactorAddictionProblem::class, 'case_id', 'case_id');
    }

    /**
     * Recent legal problems recorded for this case.
     */
    public function legalProblems(): HasMany
    {
        return $this->hasMany(RiskFactorLegalProblem::class, 'case_id', 'case_id');
    }
}
