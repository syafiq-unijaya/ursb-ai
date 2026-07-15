<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefOccupationStatus extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_occupation_status';

    protected $guarded = [];
}
