<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefSocialProblem extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_social_problems';

    protected $guarded = [];

    /**
     * Risk factor records citing this social problem.
     */
    public function riskFactorSocialProblems(): HasMany
    {
        return $this->hasMany(RiskFactorSocialProblem::class, 'ref_social_problem_id', 'id');
    }
}
