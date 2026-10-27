<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetTransfer extends Model
{
    protected $table = 'budget_transfers';
    protected $guarded = ['id'];
    public function sumber() { return $this->belongsTo(Departemen::class, 'sumber_id'); }
    public function tujuan() { return $this->belongsTo(Departemen::class, 'tujuan_id'); }
}
