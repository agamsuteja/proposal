<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected static function booted(): void
    {
        static::updating(fn () => abort(403, 'Log audit tidak dapat diubah.'));
        static::deleting(fn () => abort(403, 'Log audit tidak dapat dihapus melalui model. Gunakan arsip terkontrol.'));
    }

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
