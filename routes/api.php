<?php

use App\Http\Controllers\Api\V1\ApprovalController;
use App\Http\Controllers\Api\V1\AttachmentController;
use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RequestController;
use App\Http\Controllers\Api\V1\RuleController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Requests
    Route::get('/requests', [RequestController::class, 'index']);
    Route::post('/requests', [RequestController::class, 'store']);
    Route::get('/requests/{id}', [RequestController::class, 'show']);
    Route::put('/requests/{id}', [RequestController::class, 'update']);
    Route::post('/requests/{id}/submit', [RequestController::class, 'submit']);
    Route::post('/requests/{id}/cancel', [RequestController::class, 'cancel']);
    Route::post('/requests/{id}/archive', [RequestController::class, 'archive']);

    // Attachments
    Route::get('/requests/{id}/attachments', [AttachmentController::class, 'index']);
    Route::post('/requests/{id}/attachments', [AttachmentController::class, 'upload']);
    Route::get('/attachments/{id}', [AttachmentController::class, 'download']);
    Route::delete('/attachments/{id}', [AttachmentController::class, 'destroy']);

    // Approvals
    Route::get('/approvals/inbox', [ApprovalController::class, 'inbox']);
    Route::post('/requests/{id}/approve', [ApprovalController::class, 'approve']);
    Route::post('/requests/{id}/reject', [ApprovalController::class, 'reject']);
    Route::post('/requests/{id}/return', [ApprovalController::class, 'return']);

    // Rules (dept_admin and super_admin only)
    Route::middleware('role:dept_admin,super_admin')->group(function () {
        Route::get('/rules', [RuleController::class, 'index']);
        Route::post('/rules', [RuleController::class, 'store']);
        Route::put('/rules/{id}', [RuleController::class, 'update']);
        Route::delete('/rules/{id}', [RuleController::class, 'destroy']);
    });

    // Audit logs
    Route::get('/audit', [AuditController::class, 'index']); // super_admin check inside controller
    Route::get('/requests/{id}/audit', [AuditController::class, 'requestAudit']);
});
