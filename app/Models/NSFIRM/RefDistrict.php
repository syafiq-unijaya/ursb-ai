<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lookup: administrative district. Business key is `code`.
 */
class RefDistrict extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_district';

    protected $guarded = [];

    /**
     * The state this district belongs to.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(RefState::class, 'state_code', 'code');
    }

    /**
     * Population figures recorded for this district.
     */
    public function overallPopulations(): HasMany
    {
        return $this->hasMany(RefOverallPopulation::class, 'district_code', 'code');
    }
}
