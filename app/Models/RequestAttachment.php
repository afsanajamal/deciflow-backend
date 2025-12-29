<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestAttachment extends Model
{
    protected $fillable = [
        'request_id',
        'file_name',
        'file_path',
        'mime_type',
    ];

    /**
     * Get the request this attachment belongs to
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }
}
