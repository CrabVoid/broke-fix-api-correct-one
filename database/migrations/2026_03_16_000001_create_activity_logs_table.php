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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            
            // Actor (who performed the action)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('user_type')->nullable(); // For polymorphic relation if needed
            
            // Action details
            $table->string('action'); // e.g., 'created', 'updated', 'deleted', 'login', 'logout'
            $table->string('entity_type')->nullable(); // e.g., 'App\Models\Application'
            $table->unsignedBigInteger('entity_id')->nullable(); // ID of affected record
            
            // Request context
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('method', 10)->nullable(); // HTTP method
            $table->string('url')->nullable(); // Request URL
            
            // Additional data
            $table->json('properties')->nullable(); // Additional metadata (old values, new values, etc.)
            
            $table->timestamps();
            
            // Indexes for common queries
            $table->index('user_id');
            $table->index('action');
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
