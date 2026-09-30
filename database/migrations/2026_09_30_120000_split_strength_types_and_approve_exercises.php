<?php

use App\Models\Exercise;
use App\Models\Workout;
use Database\Seeders\ExerciseSeeder;
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
        Schema::table('exercises', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('session_types');
        });

        Exercise::query()->orderBy('id')->each(function (Exercise $exercise): void {
            $exercise->session_types = ExerciseSeeder::typesFor($exercise->name, $exercise->group);

            if ($exercise->user_id === null) {
                $exercise->approved_at = $exercise->created_at ?? now();
            }

            $exercise->save();
        });

        Workout::query()
            ->with('exercises.exercise')
            ->orderBy('id')
            ->each(function (Workout $workout): void {
                $type = $workout->type;

                if ($type === null || $type === '') {
                    return;
                }

                if (in_array($type, ['Cardio', 'Bodyweight', 'Conditioning', 'Mobility'], true)) {
                    return;
                }

                $workout->update([
                    'type' => $this->strengthType($workout),
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Workout::query()
            ->where('type', 'like', 'Strength - %')
            ->update(['type' => 'Strength']);

        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }

    private function strengthType(Workout $workout): string
    {
        $groups = $workout->exercises
            ->map(fn ($entry): ?string => $entry->exercise?->group)
            ->filter()
            ->unique();

        $hasUpper = $groups->contains('Upper Body');
        $hasLower = $groups->contains('Lower Body');
        $hasFull = $groups->contains('Full Body');

        if ($hasFull || ($hasUpper && $hasLower)) {
            return 'Strength - Full Body';
        }

        if ($hasUpper) {
            return 'Strength - Upper Body';
        }

        if ($hasLower) {
            return 'Strength - Lower Body';
        }

        return 'Strength - Full Body';
    }
};
