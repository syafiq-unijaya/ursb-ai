<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefMentalHealthTreatment extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_mental_health_treatments';

    protected $guarded = [];

    /**
     * Risk factor records citing this mental health treatment.
     */
    public function riskFactorMentalHealthTreatments(): HasMany
    {
        return $this->hasMany(RiskFactorMentalHealthTreatment::class, 'ref_mental_health_treatment_id', 'id');
    }
}
