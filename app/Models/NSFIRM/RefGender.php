<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefGender extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_gender';

    protected $guarded = [];
}
