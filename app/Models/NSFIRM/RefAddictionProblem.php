<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefAddictionProblem extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_addiction_problems';

    protected $guarded = [];

    /**
     * Risk factor records citing this addiction problem.
     */
    public function riskFactorAddictionProblems(): HasMany
    {
        return $this->hasMany(RiskFactorAddictionProblem::class, 'ref_addiction_problem_id', 'id');
    }
}
