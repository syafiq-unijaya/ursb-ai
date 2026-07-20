<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskFactorSocialProblem extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'risk_factor_social_problems';

    protected $guarded = [];

    /**
     * The case this social problem record belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * The social problem reference.
     */
    public function ref(): BelongsTo
    {
        return $this->belongsTo(RefSocialProblem::class, 'ref_social_problem_id', 'id');
    }
}
