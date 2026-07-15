<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefTypeOfInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_type_of_injury';

    protected $guarded = [];

    /**
     * Case injury rows referencing this injury type.
     */
    public function caseTypeOfInjuries(): HasMany
    {
        return $this->hasMany(CaseTypeOfInjury::class, 'injury_code', 'code');
    }
}
