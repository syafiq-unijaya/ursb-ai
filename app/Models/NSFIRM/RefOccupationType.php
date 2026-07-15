<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefOccupationType extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_occupation_type';

    protected $guarded = [];
}
