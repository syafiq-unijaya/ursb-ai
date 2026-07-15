<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefMannerDeath extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_manner_death';

    protected $guarded = [];
}
