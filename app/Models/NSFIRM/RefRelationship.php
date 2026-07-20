<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefRelationship extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_relationship';

    protected $guarded = [];

    /**
     * Deceased records whose next-of-kin holds this relationship.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'next_of_kin_relationship_code', 'code');
    }
}
