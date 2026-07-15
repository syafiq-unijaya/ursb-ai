<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefPresentation extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_presentation';

    protected $guarded = [];
}
