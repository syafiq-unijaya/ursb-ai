<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefDesignation extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_designation';

    protected $guarded = [];
}
