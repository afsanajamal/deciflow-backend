<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends Model
{
    protected $fillable = [
        'user_id',
        'department_id',
        'title',
        'description',
        'category',
        'amount',
        'vendor_name',
        'urgency',
        'urgency_reason',
        'travel_start_date',
        'travel_end_date',
        'status',
    ];

    protected $casts = [
        'amount' => 'integer',
        'travel_start_date' => 'date',
        'travel_end_date' => 'date',
    ];

    /**
     * Get the user who created the request
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the department this request belongs to
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get all attachments for this request
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(RequestAttachment::class);
    }

    /**
     * Get all approval steps for this request
     */
    public function approvalSteps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class);
    }

    /**
     * Get all audit logs for this request
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
