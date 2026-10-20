<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PencairanDana extends Model
{
    protected $table = 'pencairan_dana';

    protected $fillable = [
        'proposal_id',
        'rekening_id',
        'jumlah_dana',
        'catatan',
        'bukti_transfer',
        'tanggal_pencairan'
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function rekening()
    {
        return $this->belongsTo(Rekening::class);
    }
}
