<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefIdentificationType extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_identification_type';

    protected $guarded = [];
}
