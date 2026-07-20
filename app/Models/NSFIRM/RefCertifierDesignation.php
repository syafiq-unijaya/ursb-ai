<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefCertifierDesignation extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_certifier_designation';

    protected $guarded = [];

    /**
     * Deceased records whose certifier holds this certifier designation.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'certified_by_certifier_designation_code', 'code');
    }
}
