<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefCity extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_city';

    protected $guarded = [];
}
