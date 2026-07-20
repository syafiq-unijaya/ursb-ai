<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefOccupationType extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_occupation_type';

    protected $guarded = [];

    /**
     * Deceased records with this occupation type.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'occupation_type', 'code');
    }
}
