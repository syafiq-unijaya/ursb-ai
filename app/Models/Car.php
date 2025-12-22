<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'brand',
        'model',
        'variant',
        'year',
    ];

    // Many-to-many: reference car models can be owned by multiple users (pivot: car_user)
    public function users()
    {
        return $this->belongsToMany(User::class, 'car_user')
            ->withPivot('plate')
            ->withTimestamps();
    }
}
