<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->index('user_id');
            $table->dropUnique(['user_id', 'performed_on']);
        });

        Schema::table('workout_sets', function (Blueprint $table) {
            $table->decimal('actual_weight', 6, 2)->nullable()->after('reps');
            $table->unsignedSmallInteger('actual_reps')->nullable()->after('actual_weight');
        });

        $entries = DB::table('workout_exercises')
            ->where(function ($query): void {
                $query->whereNotNull('actual_weight')->orWhereNotNull('actual_reps');
            })
            ->get();

        foreach ($entries as $entry) {
            $set = DB::table('workout_sets')
                ->where('workout_exercise_id', $entry->id)
                ->orderByDesc('weight')
                ->orderByDesc('reps')
                ->orderBy('position')
                ->first();

            if ($set === null) {
                continue;
            }

            DB::table('workout_sets')->where('id', $set->id)->update([
                'actual_weight' => $entry->actual_weight,
                'actual_reps' => $entry->actual_reps,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('workout_sets', function (Blueprint $table) {
            $table->dropColumn(['actual_weight', 'actual_reps']);
        });

        Schema::table('workouts', function (Blueprint $table) {
            $table->unique(['user_id', 'performed_on']);
            $table->dropIndex(['user_id']);
        });
    }
};
