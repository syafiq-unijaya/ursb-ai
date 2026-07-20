<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskFactorSuicideAttempt extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'risk_factor_suicide_attempts';

    protected $guarded = [];

    /**
     * The case this suicide attempt record belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * The suicide attempt reference.
     */
    public function ref(): BelongsTo
    {
        return $this->belongsTo(RefSuicideAttempt::class, 'ref_suicide_attempt_id', 'id');
    }
}
