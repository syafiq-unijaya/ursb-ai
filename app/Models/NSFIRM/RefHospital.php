<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefHospital extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_hospital';

    protected $guarded = [];

    /**
     * Cases registered with this hospital as their source facility.
     * Linked on facilityCode (case_registration.source_hospital_id).
     */
    public function caseRegistrations(): HasMany
    {
        return $this->hasMany(CaseRegistration::class, 'source_hospital_id', 'facilityCode');
    }

    /**
     * The state this hospital is located in.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(RefState::class, 'state_code', 'code');
    }
}
