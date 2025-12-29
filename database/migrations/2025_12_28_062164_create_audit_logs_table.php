<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Actor who performed the action
            $table->string('action'); // e.g., "created", "submitted", "approved", "rejected"
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->json('meta')->nullable(); // Additional metadata
            $table->timestamp('created_at'); // Immutable, no updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
