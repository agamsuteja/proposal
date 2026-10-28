<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetReversal extends Model
{
    protected $table = 'budget_reversals';

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
