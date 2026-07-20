<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskFactorPhysicalHealthProblem extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'risk_factor_physical_health_problems';

    protected $guarded = [];

    /**
     * The case this physical health problem record belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * The physical health problem reference.
     */
    public function ref(): BelongsTo
    {
        return $this->belongsTo(RefPhysicalHealthProblem::class, 'ref_physical_health_problem_id', 'id');
    }
}
