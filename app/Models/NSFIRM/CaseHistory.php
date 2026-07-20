<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseHistory extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'case_history';

    protected $guarded = [];

    /**
     * The case this history entry belongs to.
     */
    public function caseRegistration(): BelongsTo
    {
        return $this->belongsTo(CaseRegistration::class, 'case_id', 'case_id');
    }

    /**
     * The workflow status the case moved into.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(RefStatus::class, 'case_status', 'id');
    }

    /**
     * The workflow status the case moved out of.
     */
    public function previousStatus(): BelongsTo
    {
        return $this->belongsTo(RefStatus::class, 'previous_case_status', 'id');
    }

    /**
     * The user who performed this transition.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
