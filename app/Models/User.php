<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;
    protected $table = 'users';

    protected $fillable = [
        'name',
        'phone',
        'password',
        'is_active',
        'role_id'
    ];

    public function role(){
        return $this->belongsTo(UserRole::class, 'role_id');
    }

   public function sales(){
       return $this->hasMany(Sale::class);
   }
}
