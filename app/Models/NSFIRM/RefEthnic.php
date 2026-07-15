<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefEthnic extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_ethnic';

    protected $guarded = [];
}
