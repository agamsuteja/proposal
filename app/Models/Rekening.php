<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rekening extends Model
{
    protected $table = 'rekening';

    protected $casts = ['saldo' => 'decimal:2'];

    protected $fillable = [
        'nama_rekening',
        'nomor_rekening',
        'bank',
        'saldo',
        'keterangan',
    ];

    public function pengembalian_dana()
    {
        return $this->hasMany(PengembalianDana::class);
    }
}
