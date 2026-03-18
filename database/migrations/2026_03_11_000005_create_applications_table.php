<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('users_id')->constrained('users');
            $table->foreignId('internships_id')->constrained('internships');
            $table->timestamp('approved_at')->nullable();
            $table->text('motivation_letter')->nullable();
            $table->timestamps();

            $table->unique(['users_id', 'internships_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
