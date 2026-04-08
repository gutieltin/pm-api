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
        Schema::create('tasks', function (Blueprint $table) {
        $table->id();
        $table->foreignId('project_id')->constrained()->cascadeOnDelete();
        $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete(); // Who created the task
        $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete(); // Who does the work
        $table->string('title');
        $table->text('description')->nullable();
        $table->string('priority')->default('medium'); // low, medium, high
        $table->string('status')->default('pending'); // pending, in_progress, review, done
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
