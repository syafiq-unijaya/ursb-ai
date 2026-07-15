<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefOccupationSector extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_occupation_sector';

    protected $guarded = [];
}
