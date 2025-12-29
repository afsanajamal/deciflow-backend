<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    const UPDATED_AT = null; // Immutable, only created_at

    protected $fillable = [
        'request_id',
        'user_id',
        'action',
        'from_status',
        'to_status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the request this log belongs to
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
