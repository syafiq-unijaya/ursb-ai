<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefEducation extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_education';

    protected $guarded = [];
}
