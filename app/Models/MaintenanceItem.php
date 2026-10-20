<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceItem extends Model
{
    protected $table = 'maintenance_items';

    protected $fillable = [
        'maintenance_proposal_id',
        'nama_item',
        'lokasi',
        'jenis_perawatan',
        'deskripsi_kerusakan',
        'estimasi_biaya'
    ];

    public function proposal()
    {
        return $this->belongsTo(MaintenanceProposal::class, 'maintenance_proposal_id');
    }
}
