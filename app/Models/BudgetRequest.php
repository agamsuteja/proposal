<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetRequest extends Model
{
    protected $table = 'budget_requests';

    public function versions()
    {
        return $this->hasMany(BudgetRequestVersion::class)->latest('id');
    }

    public function reversal()
    {
        return $this->hasOne(BudgetReversal::class);
    }

    protected $guarded = ['id'];

    public function departemen()
    {
        return $this->belongsTo(Departemen::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvals()
    {
        return $this->hasMany(BudgetApproval::class)->orderBy('urutan');
    }

    public function transfers()
    {
        return $this->hasMany(BudgetTransfer::class);
    }
}
