<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskFactorLegalProblem extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'risk_factor_legal_problems';

    protected $guarded = [];

    /**
     * The case this legal problem record belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * The legal problem reference.
     */
    public function ref(): BelongsTo
    {
        return $this->belongsTo(RefLegalProblem::class, 'ref_legal_problem_id', 'id');
    }
}
