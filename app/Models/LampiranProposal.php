<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LampiranProposal extends Model
{
    use HasFactory;

    protected $table = 'lampiran_proposal';

    protected $fillable = [
        'proposal_id',
        'nama_file',
        'path_file'
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }
}
