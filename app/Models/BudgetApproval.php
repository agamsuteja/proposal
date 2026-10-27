<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetApproval extends Model
{
    protected $table = 'budget_approvals';
    protected $guarded = ['id'];
    public function approver() { return $this->belongsTo(User::class, 'approver_id'); }
}
