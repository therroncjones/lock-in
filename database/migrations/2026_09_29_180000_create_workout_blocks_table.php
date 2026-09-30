<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->unsignedSmallInteger('position');
            $table->timestamps();
        });

        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->foreignId('workout_block_id')
                ->nullable()
                ->after('workout_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workout_block_id');
        });

        Schema::dropIfExists('workout_blocks');
    }
};
