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
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained()->onDelete('cascade');
            $table->integer('step_number');
            $table->string('approver_role'); // Which role should approve this step
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null'); // Actual approver
            $table->enum('status', ['pending', 'approved', 'rejected', 'returned'])->default('pending');
            $table->text('comment')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
    }
};
