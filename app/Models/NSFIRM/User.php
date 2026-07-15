<?php

namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
