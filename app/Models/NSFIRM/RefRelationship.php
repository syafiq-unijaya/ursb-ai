<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefRelationship extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_relationship';

    protected $guarded = [];
}
