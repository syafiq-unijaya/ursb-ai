<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefMaritalStatus extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_marital_status';

    protected $guarded = [];
}
