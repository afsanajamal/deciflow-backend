<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStep extends Model
{
    protected $fillable = [
        'request_id',
        'step_number',
        'approver_role',
        'approver_id',
        'status',
        'comment',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * Get the request this step belongs to
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    /**
     * Get the approver (user) for this step
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
