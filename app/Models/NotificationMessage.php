<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationMessage extends Model
{
    protected $table = 'notification_outbox';

    protected $guarded = ['id'];

    protected $casts = ['message' => 'encrypted', 'expires_at' => 'datetime', 'next_attempt_at' => 'datetime', 'sent_at' => 'datetime'];
}
