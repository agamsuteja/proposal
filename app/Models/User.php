<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'departemen_id',
        'grup_id', // 🔥 tambahkan ini!
        'name',
        'email',
        'nohp',
        'password',
        'role',
        'is_active',
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function departemen()
    {
        return $this->belongsTo(Departemen::class, 'departemen_id');
    }

    public function isRole($role)
    {
        return $this->role === $role;
    }

    public function grup()
    {
        return $this->belongsTo(\App\Models\Grup::class, 'grup_id');
    }

}
