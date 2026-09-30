<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->decimal('actual_weight', 6, 2)->nullable()->after('position');
            $table->unsignedSmallInteger('actual_reps')->nullable()->after('actual_weight');
        });
    }

    public function down(): void
    {
        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->dropColumn(['actual_weight', 'actual_reps']);
        });
    }
};
