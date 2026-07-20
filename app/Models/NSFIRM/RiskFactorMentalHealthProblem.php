<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mental health problem recorded against a case.
 *
 * Note: this table carries an extra `type_of_health_problem` column,
 * an enum of 'current' or 'past', distinguishing when the problem occurred.
 */
class RiskFactorMentalHealthProblem extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'risk_factor_mental_health_problem';

    protected $guarded = [];

    /**
     * The case this mental health problem record belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * The mental health problem reference.
     */
    public function ref(): BelongsTo
    {
        return $this->belongsTo(RefMentalHealthProblem::class, 'ref_mental_health_problem_id', 'id');
    }
}
