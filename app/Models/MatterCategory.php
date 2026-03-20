<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatterCategory extends Model
{
    protected $fillable = ['name', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
