<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class RefFacilityWard extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'ref_facility_ward';

    protected $guarded = [];
}
