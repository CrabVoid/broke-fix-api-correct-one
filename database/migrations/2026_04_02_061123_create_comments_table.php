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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // Comment content
            $table->text('content');

            // Author (user who created the comment)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            // Polymorphic relation - the entity being commented on
            $table->morphs('commentable'); // Creates commentable_type and commentable_id

            // Parent comment (for nested replies)
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade');

            $table->timestamps();

            // Indexes for common queries
            $table->index(['commentable_type', 'commentable_id']);
            $table->index('user_id');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
