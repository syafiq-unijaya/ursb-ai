<?php
namespace App\Models\NSFIRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    protected $connection = 'nsfirm';

    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Cases where this user is the assigned specific doctor.
     */
    public function caseRegistrations(): HasMany
    {
        return $this->hasMany(CaseRegistration::class, 'specific_doctor', 'id');
    }
}
