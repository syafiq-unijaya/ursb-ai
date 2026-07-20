<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The reporting view NSFIRM builds its official statistics from.
 *
 * Flattens case_registration + deceased_information + the risk factor flags into one
 * row per case, with date_of_death pre-split into year and month. This is the sanctioned
 * numerator source for mortality rates -- only rows with status_id = 3 (Verified) count.
 *
 * Read-only: it is a database VIEW, so it has no auto-increment key and no timestamps
 * of its own to maintain.
 */
class ViewCaseReport extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'view_case_report';

    protected $primaryKey = 'case_id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    /**
     * The status id that marks a case as Verified. Draft, unverified and deleted
     * cases are never counted in official statistics.
     */
    public const STATUS_VERIFIED = 3;

    /**
     * Limit the query to verified cases only.
     */
    public function scopeVerified($query)
    {
        return $query->where('status_id', self::STATUS_VERIFIED);
    }

    /**
     * The underlying case registration.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * The injury types recorded against this case.
     */
    public function typeOfInjuries(): HasMany
    {
        return $this->hasMany(CaseTypeOfInjury::class, 'case_id', 'case_id');
    }

    /**
     * The hospital the case was registered from.
     */
    public function sourceHospital(): BelongsTo
    {
        return $this->belongsTo(RefHospital::class, 'source_hospital_id', 'facilityCode');
    }
}
