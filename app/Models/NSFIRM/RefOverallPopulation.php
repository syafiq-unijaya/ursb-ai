<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefOverallPopulation extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_overall_population';

    protected $guarded = [];

    /**
     * The state this population figure belongs to.
     *
     * Suffixed `Ref` because the table also carries a plain `state` VARCHAR column
     * holding the state name, which would shadow the relation on attribute access.
     */
    public function stateRef(): BelongsTo
    {
        return $this->belongsTo(RefState::class, 'state_code', 'code');
    }

    /**
     * The district this population figure belongs to.
     *
     * Suffixed `Ref` for the same reason as `stateRef()` -- a plain `district`
     * VARCHAR column holding the district name sits alongside `district_code`.
     */
    public function districtRef(): BelongsTo
    {
        return $this->belongsTo(RefDistrict::class, 'district_code', 'code');
    }

    /**
     * The gender reference for this population figure.
     */
    public function genderRef(): BelongsTo
    {
        return $this->belongsTo(RefGender::class, 'gender_code', 'code');
    }
}
