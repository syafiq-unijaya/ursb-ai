<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseTypeOfInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'case_type_of_injury';

    protected $guarded = [];

    /**
     * The case this injury record belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * The type of injury reference.
     */
    public function injury(): BelongsTo
    {
        return $this->belongsTo(RefTypeOfInjury::class, 'injury_code', 'code');
    }
}
