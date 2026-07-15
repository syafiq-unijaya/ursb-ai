<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefPostcode extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_postcode';

    protected $guarded = [];
}
