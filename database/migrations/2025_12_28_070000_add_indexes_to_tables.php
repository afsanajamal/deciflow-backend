<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add indexes to requests table for better query performance
        Schema::table('requests', function (Blueprint $table) {
            $table->index('status');
            $table->index('category');
            $table->index('created_at');
            $table->index(['user_id', 'status']);
            $table->index(['department_id', 'status']);
        });

        // Add indexes to approval_steps table
        Schema::table('approval_steps', function (Blueprint $table) {
            $table->index('status');
            $table->index('approver_role');
            $table->index(['request_id', 'status']);
            $table->index(['approver_role', 'status']);
        });

        // Add indexes to audit_logs table
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['request_id', 'created_at']);
        });

        // Add indexes to rules table
        Schema::table('rules', function (Blueprint $table) {
            $table->index('is_active');
            $table->index(['min_amount', 'max_amount']);
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['category']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['department_id', 'status']);
        });

        Schema::table('approval_steps', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['approver_role']);
            $table->dropIndex(['request_id', 'status']);
            $table->dropIndex(['approver_role', 'status']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['request_id', 'created_at']);
        });

        Schema::table('rules', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['min_amount', 'max_amount']);
        });
    }
};
