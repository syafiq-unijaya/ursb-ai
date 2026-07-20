<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefSuicideNote extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_suicide_notes';

    protected $guarded = [];

    /**
     * Risk factor records citing this suicide note type.
     */
    public function riskFactorSuicideNotes(): HasMany
    {
        return $this->hasMany(RiskFactorSuicideNote::class, 'ref_suicide_note_id', 'id');
    }
}
