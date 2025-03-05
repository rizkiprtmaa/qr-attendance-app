<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    protected $guarded = [];

    public function major()
    {
        return $this->belongsTo(Major::class);
    }
}
