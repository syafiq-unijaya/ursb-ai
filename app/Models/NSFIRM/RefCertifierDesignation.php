<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefCertifierDesignation extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_certifier_designation';

    protected $guarded = [];
}
