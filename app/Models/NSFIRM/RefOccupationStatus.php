<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefOccupationStatus extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_occupation_status';

    protected $guarded = [];

    /**
     * Deceased records with this occupation status.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'occupation_status', 'code');
    }
}
