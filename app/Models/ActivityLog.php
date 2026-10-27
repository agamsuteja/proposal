<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
}
