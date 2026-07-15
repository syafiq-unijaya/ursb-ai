<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseRegistration extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'case_registration';

    protected $primaryKey = 'case_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /**
     * The assigned doctor for this case.
     */
    public function specificDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'specific_doctor', 'id');
    }

    /**
     * The workflow status of this case.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(RefStatus::class, 'status_id', 'id');
    }

    /**
     * The source hospital this case originated from.
     * Linked on facilityCode (source_hospital_id -> ref_hospital.facilityCode).
     */
    public function sourceHospital(): BelongsTo
    {
        return $this->belongsTo(RefHospital::class, 'source_hospital_id', 'facilityCode');
    }

    /**
     * Deceased records attached to this case.
     */
    public function deceasedInformation(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'case_id', 'case_id');
    }

    /**
     * Injury types recorded for this case.
     */
    public function typeOfInjuries(): HasMany
    {
        return $this->hasMany(CaseTypeOfInjury::class, 'case_id', 'case_id');
    }
}
