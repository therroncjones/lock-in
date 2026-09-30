<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('one_rep_maxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight', 6, 2);
            $table->timestamps();

            $table->unique(['user_id', 'exercise_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('one_rep_maxes');
    }
};
