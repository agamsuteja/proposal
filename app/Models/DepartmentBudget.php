<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentBudget extends Model
{
    protected $table = 'budget_departemen';
    protected $guarded = ['id'];
    public function departemen() { return $this->belongsTo(Departemen::class); }
}
