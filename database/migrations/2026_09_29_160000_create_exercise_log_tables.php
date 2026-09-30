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
        Schema::table('workouts', function (Blueprint $table) {
            $table->string('type', 100)->nullable()->after('performed_on');
        });

        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('group')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'name']);
        });

        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['workout_id', 'exercise_id']);
        });

        Schema::create('workout_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->decimal('weight', 6, 2)->nullable();
            $table->unsignedSmallInteger('reps')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_sets');
        Schema::dropIfExists('workout_exercises');
        Schema::dropIfExists('exercises');

        Schema::table('workouts', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
