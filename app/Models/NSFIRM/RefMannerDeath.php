<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefMannerDeath extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_manner_death';

    protected $guarded = [];

    /**
     * Deceased records with this manner of death.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'manner_of_death', 'code');
    }
}
