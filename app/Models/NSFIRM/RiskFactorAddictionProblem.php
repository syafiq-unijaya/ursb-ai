<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskFactorAddictionProblem extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'risk_factor_addiction_problems';

    protected $guarded = [];

    /**
     * The case this addiction problem record belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * The addiction problem reference.
     */
    public function ref(): BelongsTo
    {
        return $this->belongsTo(RefAddictionProblem::class, 'ref_addiction_problem_id', 'id');
    }
}
