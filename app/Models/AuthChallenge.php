<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthChallenge extends Model
{
    protected $table = 'auth_challenges';

    protected $guarded = ['id'];

    protected $casts = ['expires_at' => 'datetime'];
}
