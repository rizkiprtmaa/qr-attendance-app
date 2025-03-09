<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    protected $guarded = [];

    public function classes()
    {
        return $this->hasMany(Classes::class);
    }
}
