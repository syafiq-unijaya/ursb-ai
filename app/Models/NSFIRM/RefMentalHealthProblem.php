<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefMentalHealthProblem extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_mental_health_problems';

    protected $guarded = [];

    /**
     * Risk factor records citing this mental health problem.
     */
    public function riskFactorMentalHealthProblems(): HasMany
    {
        return $this->hasMany(RiskFactorMentalHealthProblem::class, 'ref_mental_health_problem_id', 'id');
    }
}
