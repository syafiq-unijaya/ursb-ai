<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefStatus extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_status';

    protected $guarded = [];

    /**
     * Cases currently in this workflow status.
     */
    public function caseRegistrations(): HasMany
    {
        return $this->hasMany(CaseRegistration::class, 'status_id', 'id');
    }
}
