<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departemen extends Model
{
    protected $table = 'departemen';
    protected $fillable = ['grup_id', 'nama_departemen'];

    public function grup()
    {
        return $this->belongsTo(Grup::class);
    }
}
