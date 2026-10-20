<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceProposal extends Model
{
    protected $table = 'maintenance_proposals';

    protected $fillable = [
        'kode_proposal',
        'user_id',
        'departemen_id',
        'judul',
        'tujuan',
        'total_anggaran',
        'tanggal_pengajuan',
        'keterangan',
        'status',
        'lampiran'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function departemen()
    {
        return $this->belongsTo(Departemen::class);
    }

    public function items()
    {
        return $this->hasMany(MaintenanceItem::class, 'maintenance_proposal_id');
    }
}
