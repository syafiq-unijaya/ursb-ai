<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefPresentation extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_presentation';

    protected $guarded = [];

    /**
     * Deceased records with this death presentation.
     */
    public function deceasedInformations(): HasMany
    {
        return $this->hasMany(DeceasedInformation::class, 'death_presentation', 'code');
    }
}
