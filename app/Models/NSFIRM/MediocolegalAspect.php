<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Medicolegal aspect of a case.
 *
 * Note: the underlying table is named `mediocolegal_aspect` — the misspelling
 * ("mediocolegal" instead of "medicolegal") exists upstream in the NSFIRM
 * database schema and is intentionally mirrored here.
 */
class MediocolegalAspect extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'mediocolegal_aspect';

    protected $guarded = [];

    /**
     * The case this medicolegal record belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * The finder's relationship to the deceased.
     */
    public function finderRelationship(): BelongsTo
    {
        return $this->belongsTo(RefRelationship::class, 'finder_relationship_code', 'code');
    }

    /**
     * The identification document type of the finder.
     */
    public function finderIdentificationType(): BelongsTo
    {
        return $this->belongsTo(RefIdentificationType::class, 'finder_identification_type', 'code');
    }

    /**
     * The city of the finder's address.
     */
    public function finderCity(): BelongsTo
    {
        return $this->belongsTo(RefCity::class, 'finder_city_code', 'code');
    }

    /**
     * The state of the finder's address.
     */
    public function finderState(): BelongsTo
    {
        return $this->belongsTo(RefState::class, 'finder_state_code', 'code');
    }

    /**
     * The postcode of the finder's address.
     */
    public function finderPostcode(): BelongsTo
    {
        return $this->belongsTo(RefPostcode::class, 'finder_postcode_code', 'postcode');
    }
}
