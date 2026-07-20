<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskFactorSuicideNote extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'risk_factor_suicide_notes';

    protected $guarded = [];

    /**
     * The case this suicide note record belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * The suicide note reference.
     */
    public function ref(): BelongsTo
    {
        return $this->belongsTo(RefSuicideNote::class, 'ref_suicide_note_id', 'id');
    }
}
