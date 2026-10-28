<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetPeriod extends Model
{
    protected $table = 'budget_periods';

    protected $guarded = ['id'];

    protected $primaryKey = 'tahun';

    public $incrementing = false;
}
