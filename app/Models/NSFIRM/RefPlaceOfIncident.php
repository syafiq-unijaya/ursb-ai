<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefPlaceOfIncident extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_place_of_incident';

    protected $guarded = [];
}
