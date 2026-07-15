<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefStatus extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_status';

    protected $guarded = [];
}
