<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    protected $fillable = [
        'name',
        'min_amount',
        'max_amount',
        'approval_steps_json',
        'category',
        'is_active',
    ];

    protected $casts = [
        'min_amount' => 'integer',
        'max_amount' => 'integer',
        'approval_steps_json' => 'array',
        'is_active' => 'boolean',
    ];
}
