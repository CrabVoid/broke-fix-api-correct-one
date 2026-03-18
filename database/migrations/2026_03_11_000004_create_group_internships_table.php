<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups');
            $table->foreignId('internship_id')->constrained('internships');
            $table->date('start_at')->nullable();
            $table->date('end_at')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'internship_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_internships');
    }
};
