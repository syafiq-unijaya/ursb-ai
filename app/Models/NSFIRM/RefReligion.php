<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefReligion extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_religion';

    protected $guarded = [];
}
