<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mental health treatment recorded against a case.
 *
 * Note: this table carries an extra `type_of_health_problem` column,
 * an enum of 'current' or 'past', distinguishing when the treatment occurred.
 */
class RiskFactorMentalHealthTreatment extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'risk_factor_mental_health_treatment';

    protected $guarded = [];

    /**
     * The case this mental health treatment record belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * The mental health treatment reference.
     */
    public function ref(): BelongsTo
    {
        return $this->belongsTo(RefMentalHealthTreatment::class, 'ref_mental_health_treatment_id', 'id');
    }
}
