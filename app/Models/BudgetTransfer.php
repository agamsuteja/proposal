<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetTransfer extends Model
{
    protected $table = 'budget_transfers';

    public function request()
    {
        return $this->belongsTo(BudgetRequest::class, 'budget_request_id');
    }

    protected $guarded = ['id'];

    public function sumber()
    {
        return $this->belongsTo(Departemen::class, 'sumber_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tujuan()
    {
        return $this->belongsTo(Departemen::class, 'tujuan_id');
    }
}
