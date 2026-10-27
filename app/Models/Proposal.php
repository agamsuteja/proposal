<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    protected $table = 'proposal';
    protected $fillable = [
        'jenis',
        'user_id',
        'departemen_id',
        'judul',
        'tujuan',
        'total_anggaran',
        'tanggal_pengajuan',
        'tanggal_acara',
        'lokasi_acara',
        'keterangan',
        'lampiran',
        'nota_penggunaan',
        'status'
    ];

    protected static function booted(): void
    {
        static::updating(function ($proposal) {
            if (($proposal->isDirty('departemen_id') || $proposal->isDirty('tanggal_pengajuan')) && $proposal->pencairan()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['departemen_id' => 'Departemen dan tahun proposal yang sudah dicairkan tidak dapat diubah.']);
            }
        });
        static::deleting(function ($proposal) {
            if ($proposal->pencairan()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['proposal' => 'Proposal dengan riwayat pencairan tidak dapat dihapus.']);
            }
        });
    }

    public function items()
    {
        return $this->hasMany(ProposalItem::class);
    }

    public function departemen()
    {
        return $this->belongsTo(Departemen::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approval_flows()
    {
        return $this->hasMany(ApprovalProposal::class, 'proposal_id')
                    ->orderBy('urutan', 'asc');
    }

    

    public function pencairan()
    {
        return $this->hasMany(\App\Models\PencairanDana::class, 'proposal_id');
    }


    public function nota_penggunaan()
    {
        return $this->hasMany(\App\Models\NotaPenggunaanProposal::class, 'proposal_id');
    }


    public function getStatusCairAttribute()
    {
        $totalCair = $this->pencairan->sum('jumlah_dana');
        if ($totalCair >= $this->total_anggaran) {
            return 'Dicairkan Penuh';
        } elseif ($totalCair > 0) {
            return 'Dicairkan Sebagian';
        } else {
            return 'Belum Dicairkan';
        }
    }

    public function pengembalianDana()
    {
        return $this->hasMany(PengembalianDana::class);
    }

    

    





}
