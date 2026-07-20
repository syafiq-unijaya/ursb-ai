<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefSuicideAttempt extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_suicide_attempts';

    protected $guarded = [];

    /**
     * Risk factor records citing this suicide attempt type.
     */
    public function riskFactorSuicideAttempts(): HasMany
    {
        return $this->hasMany(RiskFactorSuicideAttempt::class, 'ref_suicide_attempt_id', 'id');
    }
}
