<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefDesignation extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_designation';

    protected $guarded = [];

    /**
     * Deceased records whose certifier holds this designation.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'certified_by_designation_code', 'code');
    }
}
