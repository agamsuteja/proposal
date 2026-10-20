<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grup extends Model
{
    protected $table = 'grup';
    protected $fillable = ['nama_grup', 'deskripsi'];

    public function departemen()
    {
        return $this->hasMany(Departemen::class, 'grup_id');
    }
}
