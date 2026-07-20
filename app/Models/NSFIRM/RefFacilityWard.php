<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefFacilityWard extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_facility_ward';

    protected $guarded = [];

    /**
     * Deceased records admitted to this ward.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'ward_no', 'id');
    }
}
