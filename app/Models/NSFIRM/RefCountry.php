<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefCountry extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_country';

    protected $guarded = [];
}
