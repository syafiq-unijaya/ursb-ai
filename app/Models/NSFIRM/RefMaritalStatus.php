<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefMaritalStatus extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_marital_status';

    protected $guarded = [];

    /**
     * Deceased records with this marital status.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'marital_status_code', 'code');
    }
}
