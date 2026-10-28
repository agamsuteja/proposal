<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupFunding extends Model
{
    protected $table = 'group_fundings';

    protected $guarded = ['id'];

    protected $casts = ['tahun' => 'integer', 'plafon' => 'decimal:2'];

    public function rekening()
    {
        return $this->belongsTo(Rekening::class);
    }

    public function grup()
    {
        return $this->belongsTo(Grup::class);
    }
}
