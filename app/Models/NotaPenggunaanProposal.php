<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaPenggunaanProposal extends Model
{
    use HasFactory;

    protected $table = 'nota_penggunaan_proposal';

    protected $fillable = [
        'proposal_id',
        'jumlah',
        'file_nota',
        'keterangan',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }
}
