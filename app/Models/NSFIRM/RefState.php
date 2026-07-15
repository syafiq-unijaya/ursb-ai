<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefState extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_state';

    protected $guarded = [];
}
