<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefLegalProblem extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_legal_problems';

    protected $guarded = [];

    /**
     * Risk factor records citing this legal problem.
     */
    public function riskFactorLegalProblems(): HasMany
    {
        return $this->hasMany(RiskFactorLegalProblem::class, 'ref_legal_problem_id', 'id');
    }
}
