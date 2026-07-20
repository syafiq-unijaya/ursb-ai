<?php
namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
