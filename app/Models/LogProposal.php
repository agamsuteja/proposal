<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogProposal extends Model
{
    use HasFactory;

    protected $table = 'log_proposal';

    protected $fillable = [
        'proposal_id',
        'user_id',
        'aksi',
        'keterangan',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
