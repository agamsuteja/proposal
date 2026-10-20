<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProposalItem extends Model
{
    protected $table = 'proposal_items';
    protected $fillable = [
        'proposal_id',
        'nama_barang',
        'merk_tipe',
        'jumlah',
        'tujuan_pembelian',
        'anggaran',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }
}
