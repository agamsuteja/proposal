<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetRequestVersion extends Model
{
    protected $table = 'budget_request_versions';

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = ['snapshot' => 'array'];
}
