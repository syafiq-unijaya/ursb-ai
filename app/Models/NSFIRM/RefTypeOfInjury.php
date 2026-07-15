<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefTypeOfInjury extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_type_of_injury';

    protected $guarded = [];
}
