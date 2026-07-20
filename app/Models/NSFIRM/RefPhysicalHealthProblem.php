<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefPhysicalHealthProblem extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_physical_health_problems';

    protected $guarded = [];

    /**
     * Risk factor records citing this physical health problem.
     */
    public function riskFactorPhysicalHealthProblems(): HasMany
    {
        return $this->hasMany(RiskFactorPhysicalHealthProblem::class, 'ref_physical_health_problem_id', 'id');
    }
}
