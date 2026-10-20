<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengembalianDana extends Model
{
    protected $table = 'pengembalian_dana';

    protected $fillable = [
        'proposal_id',
        'rekening_id',
        'jumlah',
        'metode',
        'bukti',
        'catatan',
        'status',
        'tanggal_pengembalian',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'tanggal_pengembalian' => 'date',
    ];

    /* =====================
     | RELATIONS
     ===================== */

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function rekening()
    {
        return $this->belongsTo(Rekening::class);
    }


    protected static function booted()
    {
        static::deleting(function ($model) {
            if ($model->bukti) {
                \Storage::disk('public')->delete($model->bukti);
            }
        });
    }

}


