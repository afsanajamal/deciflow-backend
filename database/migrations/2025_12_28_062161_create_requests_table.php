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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->enum('category', ['EQUIPMENT', 'SOFTWARE', 'SERVICE', 'TRAVEL']);
            $table->integer('amount'); // JPY amount
            $table->string('vendor_name')->nullable();
            $table->enum('urgency', ['NORMAL', 'URGENT'])->default('NORMAL');
            $table->text('urgency_reason')->nullable();
            $table->date('travel_start_date')->nullable();
            $table->date('travel_end_date')->nullable();
            $table->enum('status', ['DRAFT', 'SUBMITTED', 'IN_REVIEW', 'RETURNED', 'APPROVED', 'REJECTED', 'CANCELLED', 'ARCHIVED'])->default('DRAFT');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
