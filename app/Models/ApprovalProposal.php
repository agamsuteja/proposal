<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalProposal extends Model
{
    protected $table = 'approval_proposal'; // pakai nama tabel kamu (tanpa 's')
    
    protected $fillable = [
        'proposal_id',
        'tahap',
        'urutan',
        'approver_id',
        'status',
        'catatan',
        'tanggal_approve',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
