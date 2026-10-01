<?php

namespace Tests\Feature;

use App\Livewire\Progress;
use App\Models\Exercise;
use App\Models\OneRepMax;
use App\Models\Run;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Database\Seeders\ExerciseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExerciseSeeder::class);
    }

    public function test_progress_is_empty_until_a_workout_is_logged(): void
    {
        $user = User::factory()->guest()->create();

        $this->actingAs($user);

        Livewire::test(Progress::class)
            ->assertSet('type', 'Strength - Upper Body')
            ->assertSee('Strength')
            ->assertSee('Cardio')
            ->assertSee('Bodyweight')
            ->assertSee('Log a workout to see progress.')
            ->assertDontSee('Bench Press');
    }

    public function test_progress_matches_the_selected_exercise_and_workout_type(): void
    {
        $user = User::factory()->guest()->create();
        $bench = Exercise::query()->where('name', 'Bench Press')->firstOrFail();
        $squat = Exercise::query()->where('name', 'Barbell Back Squat')->firstOrFail();
        $row = Exercise::query()->where('name', 'Barbell Row')->firstOrFail();
        $run = Exercise::query()->where('name', 'Outdoor Run')->firstOrFail();

        $this->logSet($user, $squat, '2026-09-02', '315', 3);
        $this->logSet($user, $bench, '2026-09-01', '185', 5);
        $this->logSet($user, $bench, '2026-09-10', '205', 3);
        $this->logSet($user, $bench, '2026-09-20', '135', 10);
        $this->logSet($user, $bench, '2026-09-21', '155', 8);
        $this->logSet($user, $bench, '2026-09-22', '165', 6);
        $this->logSet($user, $bench, '2026-09-23', '175', 5);
        $latestBench = $this->logSet($user, $bench, '2026-09-24', '180', 4);
        $latestBench->update([
            'actual_weight' => 175,
            'actual_reps' => 3,
        ]);
        $this->logSet($user, $row, '2026-09-03', '135', 8);
        $this->logSet($user, $run, '2026-09-29', null, 20, 'Cardio');
        $this->logExercise($user, Exercise::query()->where('name', 'Front Squat')->firstOrFail(), '2026-09-04');

        OneRepMax::query()->create([
            'user_id' => $user->id,
            'exercise_id' => $bench->id,
            'weight' => 200,
            'recorded_on' => '2026-09-01',
        ]);
        OneRepMax::query()->create([
            'user_id' => $user->id,
            'exercise_id' => $bench->id,
            'weight' => 225,
            'recorded_on' => '2026-09-28',
        ]);

        $this->actingAs($user);
        $this->travelTo('2026-09-29 12:00:00');

        $progress = Livewire::test(Progress::class)
            ->assertSet('exercise', $bench->id)
            ->assertSee('1RM')
            ->assertSee('225 lbs')
            ->assertSee('Sep 28, 2026')
            ->assertSee('+25 lbs')
            ->assertSee('vs last best')
            ->assertSee('1RM Progress')
            ->assertDontSee('Estimated 1RM')
            ->assertSee('Latest max saved for this lift.')
            ->assertSee('Best Set')
            ->assertSee('Heaviest set logged in this range.')
            ->assertSee('Weight times reps, added up.')
            ->assertSee('Workouts that included this exercise.')
            ->assertSee('Best set compared with your max that day.')
            ->assertSee('Each set compared with what was planned.')
            ->assertSee('205 × 3')
            ->assertSee('Sep 10, 2026')
            ->assertSee('6,715 lbs')
            ->assertSee('This Year')
            ->set('range', 'all')
            ->assertSee('+236%')
            ->assertSee('vs earlier half')
            ->assertSee('7 sessions')
            ->assertSee('Last Sep 24, 2026')
            ->assertSee('102%')
            ->assertSee('205 lbs of 200')
            ->assertSee('175 × 3')
            ->assertSee('Plan 180 × 4')
            ->assertSee('-5 lbs, -1 rep')
            ->assertSee('Heaviest Set')
            ->assertSee('Workouts per week')
            ->assertSee('Recent Sets')
            ->assertSee('View All')
            ->assertDontSee('(est)')
            ->assertSee('PR')
            ->assertDontSee('185 × 5')
            ->assertDontSee('315 × 3')
            ->assertDontSee('Outdoor Run')
            ->assertSee('Front Squat')
            ->assertSee('Barbell Row')
            ->assertSee('Barbell Back Squat');

        $this->assertLabelsInOrder($progress->html(), [
            '180 × 4',
            '175 × 5',
            '165 × 6',
            '155 × 8',
            '135 × 10',
            '205 × 3',
        ], 'Recent Sets');

        $progress->call('showEverySet')
            ->assertSee('185 × 5')
            ->assertSee('Show recent');

        $this->assertLabelsInOrder($progress->html(), [
            '135 × 10',
            '205 × 3',
            '185 × 5',
        ], 'Recent Sets');

        $progress->set('exercise', $squat->id)
            ->assertSee('315 × 3')
            ->assertSee('1 Rep Max')
            ->assertDontSee('225 lbs')
            ->assertDontSee('6,715 lbs');

        $progress->set('exercise', $row->id)
            ->assertSee('135 × 8')
            ->assertDontSee('1 Rep Max')
            ->assertDontSee('% of 1RM')
            ->assertDontSee('1RM Progress');

        $frontSquat = Exercise::query()->where('name', 'Front Squat')->firstOrFail();

        $progress->set('exercise', $frontSquat->id)
            ->assertSee('No sets in this range.')
            ->assertDontSee('Workouts per week');

        $progress->call('filterType', 'Cardio')
            ->assertDontSee('Outdoor Run')
            ->assertDontSee('20 reps')
            ->assertSee('Log a workout to see progress.')
            ->assertDontSee('Bench Press')
            ->assertDontSee('205 × 3')
            ->assertDontSee('7 sessions');
    }

    public function test_full_body_progress_includes_upper_and_lower_sessions(): void
    {
        $user = User::factory()->guest()->create();
        $bench = Exercise::query()->where('name', 'Bench Press')->firstOrFail();
        $squat = Exercise::query()->where('name', 'Barbell Back Squat')->firstOrFail();

        $this->logSet($user, $bench, '2026-09-10', '200', 5, 'Strength - Upper Body');
        $this->logSet($user, $bench, '2026-09-12', '225', 3, 'Strength - Full Body');
        $this->logSet($user, $squat, '2026-09-11', '315', 3, 'Strength - Lower Body');

        $this->actingAs($user);
        $this->travelTo('2026-09-29 12:00:00');

        Livewire::test(Progress::class)
            ->assertSet('type', 'Strength - Upper Body')
            ->assertDontSee('Includes upper body, lower body, and full body sessions.')
            ->assertSee('200 × 5')
            ->assertDontSee('225 × 3')
            ->assertDontSee('315 × 3')
            ->call('filterType', 'Strength - Full Body')
            ->assertSee('Includes upper body, lower body, and full body sessions.')
            ->assertSee('200 × 5')
            ->assertSee('225 × 3')
            ->assertSee('Barbell Back Squat')
            ->set('exercise', $squat->id)
            ->assertSee('315 × 3')
            ->call('filterType', 'Strength - Upper Body')
            ->assertSee('200 × 5')
            ->assertDontSee('225 × 3')
            ->assertDontSee('315 × 3')
            ->call('filterType', 'Strength - Lower Body')
            ->assertSee('315 × 3')
            ->assertDontSee('200 × 5');
    }

    public function test_view_all_recent_sets_is_paginated(): void
    {
        $user = User::factory()->guest()->create();
        $bench = Exercise::query()->where('name', 'Bench Press')->firstOrFail();

        foreach (range(1, 11) as $day) {
            $this->logSet($user, $bench, sprintf('2026-09-%02d', $day), (string) (100 + $day), 5);
        }

        $this->actingAs($user);
        $this->travelTo('2026-09-29 12:00:00');

        Livewire::test(Progress::class)
            ->assertDontSee('101 × 5')
            ->call('showEverySet')
            ->assertSee('102 × 5')
            ->assertDontSee('101 × 5')
            ->assertSee('Next')
            ->call('nextPage')
            ->assertSee('101 × 5')
            ->assertDontSee('102 × 5')
            ->call('showRecentSets')
            ->assertDontSee('101 × 5')
            ->assertDontSee('Next');
    }

    public function test_this_month_keeps_only_the_current_month(): void
    {
        $user = User::factory()->guest()->create();
        $bench = Exercise::query()->where('name', 'Bench Press')->firstOrFail();

        $this->logSet($user, $bench, '2026-08-15', '100', 5);
        $this->logSet($user, $bench, '2026-09-15', '150', 5);
        $this->actingAs($user);
        $this->travelTo('2026-09-29 12:00:00');

        Livewire::test(Progress::class)
            ->assertSet('range', 'month')
            ->assertSee('This Month')
            ->set('range', 'month')
            ->assertSee('150 × 5')
            ->assertDontSee('100 × 5')
            ->assertSee('vs last month');
    }

    public function test_cardio_progress_includes_runs_from_the_runs_log(): void
    {
        $user = User::factory()->guest()->create();

        $run = Run::query()->create([
            'user_id' => $user->id,
            'performed_on' => '2026-09-15',
            'type' => 'outdoor',
            'distance_miles' => 3.5,
            'duration_seconds' => 1800,
        ]);
        $run->splits()->create([
            'position' => 1,
            'distance_miles' => 1,
            'duration_seconds' => 480,
        ]);

        $this->actingAs($user);
        $this->travelTo('2026-09-29 12:00:00');

        Livewire::test(Progress::class)
            ->call('filterType', 'Cardio')
            ->assertSee('1 run')
            ->assertSee('3.5 mi')
            ->assertSee('8:34 /mi')
            ->assertSee('1 mi · 8:00 /mi')
            ->assertSee(route('runs', ['run' => $run->id]), false);
    }

    public function test_actuals_are_compared_set_by_set(): void
    {
        $user = User::factory()->guest()->create();
        $bench = Exercise::query()->where('name', 'Bench Press')->firstOrFail();
        $entry = $this->logSet($user, $bench, '2026-09-20', '180', 4);
        $heavy = $entry->sets()->firstOrFail();
        $heavy->update([
            'actual_weight' => 175,
            'actual_reps' => 3,
        ]);
        WorkoutSet::query()->create([
            'workout_exercise_id' => $entry->id,
            'position' => 2,
            'weight' => 100,
            'reps' => 10,
            'actual_weight' => 100,
            'actual_reps' => 8,
        ]);

        $this->actingAs($user);
        $this->travelTo('2026-09-29 12:00:00');

        Livewire::test(Progress::class)
            ->assertSee('175 × 3')
            ->assertSee('1,325 lbs')
            ->assertSee('Plan 180 × 4')
            ->assertSee('-5 lbs, -1 rep')
            ->assertSee('100 × 8')
            ->assertSee('Plan 100 × 10')
            ->assertSee('-2 reps');
    }

    public function test_progress_does_not_include_another_users_training(): void
    {
        $user = User::factory()->guest()->create();
        $other = User::factory()->guest()->create();
        $bench = Exercise::query()->where('name', 'Bench Press')->firstOrFail();
        $row = Exercise::query()->where('name', 'Barbell Row')->firstOrFail();

        $this->logSet($user, $bench, '2026-09-29', '185', 5);
        $this->logSet($other, $row, '2026-09-29', '225', 5);

        $this->actingAs($user);

        Livewire::test(Progress::class)
            ->assertSee('Bench Press')
            ->assertDontSee('Barbell Row')
            ->assertDontSee('225 × 5');
    }

    /**
     * @param  list<string>  $labels
     */
    private function assertLabelsInOrder(string $html, array $labels, ?string $after = null): void
    {
        $haystack = $after === null ? $html : strstr($html, $after);
        $this->assertNotFalse($haystack);

        $cursor = 0;

        foreach ($labels as $label) {
            $found = strpos($haystack, $label, $cursor);
            $this->assertNotFalse($found, $label);
            $cursor = $found + strlen($label);
        }
    }

    private function logExercise(User $user, Exercise $exercise, string $date, string $type = 'Strength - Upper Body'): void
    {
        $workout = Workout::factory()->create([
            'user_id' => $user->id,
            'performed_on' => $date,
            'type' => $type,
        ]);

        WorkoutExercise::query()->create([
            'workout_id' => $workout->id,
            'exercise_id' => $exercise->id,
            'position' => 1,
        ]);
    }

    private function logSet(User $user, Exercise $exercise, string $date, ?string $weight, int $reps, string $type = 'Strength - Upper Body'): WorkoutExercise
    {
        $workout = Workout::factory()->create([
            'user_id' => $user->id,
            'performed_on' => $date,
            'type' => $type,
        ]);

        $entry = WorkoutExercise::query()->create([
            'workout_id' => $workout->id,
            'exercise_id' => $exercise->id,
            'position' => 1,
        ]);

        WorkoutSet::query()->create([
            'workout_exercise_id' => $entry->id,
            'position' => 1,
            'weight' => $weight,
            'reps' => $reps,
        ]);

        return $entry;
    }
}
