<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
    ];

    /**
     * Get all users in this department
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all requests from this department
     */
    public function requests(): HasMany
    {
        return $this->hasMany(Request::class);
    }
}
