<?php

namespace Tests\Feature;

use App\Livewire\LogWorkout;
use App\Models\Exercise;
use App\Support\WorkoutText;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutBlock;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Carbon\Carbon;
use Database\Seeders\ExerciseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LogWorkoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExerciseSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_the_log_screen_offers_a_date_a_type_and_the_exercise_library(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-29 10:00:00'));
        $user = User::factory()->guest()->create();

        $this->actingAs($user)
            ->get(route('log'))
            ->assertOk()
            ->assertSee('Type of workout')
            ->assertSee('Strength - Upper Body')
            ->assertSee('Strength - Lower Body')
            ->assertSee('Strength - Full Body')
            ->assertDontSee('Or type a workout type')
            ->assertSee('Add Exercise')
            ->assertDontSee('Bench Press')
            ->assertSee('Tue, Sep 29')
            ->assertSeeInOrder(['Today', 'Daily', 'Monthly']);

        Livewire::test(LogWorkout::class)
            ->set('exerciseQuery', 'Bench Press')
            ->call('openExercisePicker')
            ->assertSee('Bench Press')
            ->call('closeExercisePicker')
            ->assertDontSee('Bench Press');

        $this->actingAs($user)
            ->get(route('log', ['date' => '2026-10-05']))
            ->assertSee('Mon, Oct 5');
    }

    public function test_a_library_exercise_can_be_logged_with_sets_weight_and_reps(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-29 10:00:00'));
        $user = User::factory()->guest()->create();
        $exercise = Exercise::query()->where('name', 'Barbell Back Squat')->firstOrFail();

        $this->actingAs($user);

        $component = Livewire::test(LogWorkout::class)
            ->call('openExercisePicker')
            ->set('type', 'Strength - Full Body')
            ->call('addExercise', $exercise->id)
            ->assertSet('showExercisePicker', true)
            ->assertSee('Barbell Back Squat')
            ->assertSee('Planned Weight (lbs)')
            ->assertSee('Planned Reps')
            ->assertSee('Actual Weight')
            ->assertSee('Actual Reps')
            ->assertSee('Add Set');

        $entry = WorkoutExercise::query()->firstOrFail();
        $set = WorkoutSet::query()->firstOrFail();

        $component
            ->set("setWeights.{$set->id}", '185')
            ->set("setReps.{$set->id}", '6')
            ->call('addSet', $entry->id)
            ->assertHasNoErrors();

        $set->refresh();

        $this->assertSame('185.00', $set->weight);
        $this->assertSame(6, $set->reps);

        $component
            ->set("setActualWeights.{$set->id}", '175')
            ->set("setActualReps.{$set->id}", '5')
            ->assertHasNoErrors();

        $set->refresh();
        $this->assertSame('175.00', $set->actual_weight);
        $this->assertSame(5, $set->actual_reps);
        $this->assertSame(2, $entry->sets()->count());

        $workout = Workout::query()->firstOrFail();
        $this->assertSame('Strength - Full Body', $workout->type);
        $this->assertNull($workout->completed_at);

        $this->get(route('home'))
            ->assertSee('data-date="2026-09-29" data-completed="false"', false);

        $component->call('complete')
            ->assertHasNoErrors()
            ->assertSee('Mark as Incomplete')
            ->assertSee('Created '.$workout->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A'))
            ->assertSee('Updated');

        $workout->refresh();
        $this->assertNotNull($workout->completed_at);

        $component->set("setActualWeights.{$set->id}", '200');
        $this->assertSame('175.00', $set->fresh()->actual_weight);

        $this->get(route('home'))
            ->assertSee('data-date="2026-09-29" data-completed="true"', false);

        $exercises = WorkoutExercise::query()->count();
        $sets = WorkoutSet::query()->count();

        $component->call('addSet', $entry->id)
            ->call('removeSet', $set->id)
            ->call('removeExercise', $entry->id)
            ->call('addBlock')
            ->assertDontSee('Add Set')
            ->assertDontSee('Add Block')
            ->assertDontSee('Remove exercise');

        $this->assertSame($exercises, WorkoutExercise::query()->count());
        $this->assertSame($sets, WorkoutSet::query()->count());
        $this->assertSame(0, WorkoutBlock::query()->count());

        $component->call('markIncomplete')->assertSee('Complete Workout');

        $workout->refresh();
        $this->assertNull($workout->completed_at);

        $this->get(route('home'))
            ->assertSee('data-date="2026-09-29" data-completed="false"', false);
    }

    public function test_typing_an_unknown_exercise_saves_it_for_that_user(): void
    {
        $user = User::factory()->guest()->create();
        $other = User::factory()->guest()->create();

        $this->actingAs($user);

        Livewire::test(LogWorkout::class)
            ->set('exerciseQuery', 'Battle Ropes')
            ->call('addCustomExercise')
            ->assertSee('Battle Ropes')
            ->assertSee('Pending approval')
            ->assertHasNoErrors();

        $custom = Exercise::query()->where('name', 'Battle Ropes')->first();

        $this->assertNotNull($custom);
        $this->assertSame($user->id, $custom->user_id);
        $this->assertNull($custom->approved_at);

        $this->actingAs($other);

        Livewire::test(LogWorkout::class)
            ->set('exerciseQuery', 'Battle')
            ->assertDontSee('Battle Ropes');
    }

    public function test_a_pending_exercise_can_be_renamed_and_withdrawn(): void
    {
        $user = User::factory()->guest()->create();

        $this->actingAs($user);

        $component = Livewire::test(LogWorkout::class)
            ->set('type', 'Strength - Full Body')
            ->set('exerciseQuery', 'Battle Ropes')
            ->call('addCustomExercise')
            ->assertSee('Pending approval');

        $exercise = Exercise::query()->where('name', 'Battle Ropes')->firstOrFail();
        $entry = WorkoutExercise::query()->where('exercise_id', $exercise->id)->firstOrFail();

        $component
            ->set('pendingNames.'.$exercise->id, 'Rope Slams')
            ->assertSee('Rope Slams')
            ->assertHasNoErrors()
            ->call('removeExercise', $entry->id);

        $this->assertNull(Exercise::query()->where('name', 'Rope Slams')->first());
    }

    public function test_a_pending_exercise_stays_out_of_other_sessions_until_it_is_searched(): void
    {
        $user = User::factory()->guest()->create();
        $exercise = Exercise::query()->create([
            'user_id' => $user->id,
            'name' => 'Battle Ropes',
        ]);
        $workout = Workout::factory()->for($user)->create([
            'performed_on' => '2026-09-20',
            'type' => 'Strength - Full Body',
        ]);
        $workout->exercises()->create([
            'exercise_id' => $exercise->id,
            'position' => 1,
        ]);

        $this->actingAs($user);

        Livewire::test(LogWorkout::class)
            ->set('type', 'Cardio')
            ->call('openExercisePicker')
            ->assertDontSee('Battle Ropes')
            ->set('exerciseQuery', 'Battle')
            ->assertSee('Battle Ropes')
            ->set('type', 'Strength - Full Body')
            ->set('exerciseQuery', '')
            ->assertDontSee('Battle Ropes')
            ->set('exerciseQuery', 'Battle')
            ->assertSee('Battle Ropes');
    }

    public function test_recent_exercises_are_pinned_above_the_full_list(): void
    {
        $user = User::factory()->guest()->create();
        $bench = Exercise::query()->where('name', 'Bench Press')->firstOrFail();
        $workout = Workout::factory()->for($user)->create([
            'performed_on' => '2026-09-20',
            'type' => 'Strength - Upper Body',
        ]);
        $workout->exercises()->create([
            'exercise_id' => $bench->id,
            'position' => 1,
        ]);

        $this->actingAs($user);

        Livewire::test(LogWorkout::class)
            ->set('type', 'Strength - Full Body')
            ->call('openExercisePicker')
            ->assertSeeInOrder(['Recent', 'Bench Press', 'All exercises'])
            ->set('type', 'Cardio')
            ->assertDontSee('Bench Press')
            ->assertDontSee('Recent');
    }

    public function test_typing_a_library_name_reuses_the_shared_exercise(): void
    {
        $user = User::factory()->guest()->create();
        $library = Exercise::query()->where('name', 'Bench Press')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(LogWorkout::class)
            ->set('exerciseQuery', 'bench press')
            ->call('addCustomExercise')
            ->assertHasNoErrors();

        $this->assertSame(1, Exercise::query()->whereRaw('lower(name) = ?', ['bench press'])->count());
        $this->assertDatabaseHas('workout_exercises', [
            'exercise_id' => $library->id,
        ]);
    }

    public function test_opening_one_exercise_closes_the_others(): void
    {
        $user = User::factory()->guest()->create();
        $squat = Exercise::query()->where('name', 'Barbell Back Squat')->firstOrFail();
        $press = Exercise::query()->where('name', 'Bench Press')->firstOrFail();

        $this->actingAs($user);

        $component = Livewire::test(LogWorkout::class)
            ->call('addExercise', $squat->id)
            ->call('addExercise', $press->id);

        $squatEntry = WorkoutExercise::query()->where('exercise_id', $squat->id)->firstOrFail();
        $pressEntry = WorkoutExercise::query()->where('exercise_id', $press->id)->firstOrFail();

        $expanded = $component->get('expanded');
        $this->assertTrue((bool) ($expanded[$pressEntry->id] ?? false));
        $this->assertFalse((bool) ($expanded[$squatEntry->id] ?? false));

        $component->call('toggleExercise', $squatEntry->id);

        $expanded = $component->get('expanded');
        $this->assertTrue((bool) ($expanded[$squatEntry->id] ?? false));
        $this->assertFalse((bool) ($expanded[$pressEntry->id] ?? false));
    }

    public function test_opening_one_block_closes_the_others(): void
    {
        $user = User::factory()->guest()->create();
        $squat = Exercise::query()->where('name', 'Barbell Back Squat')->firstOrFail();

        $this->actingAs($user);

        $component = Livewire::test(LogWorkout::class)
            ->call('addBlock')
            ->call('openExercisePicker', WorkoutBlock::query()->value('id'))
            ->call('addExercise', $squat->id)
            ->call('addBlock');

        $blocks = WorkoutBlock::query()->orderBy('id')->get();
        $first = $blocks[0];
        $second = $blocks[1];

        $expanded = $component->get('expandedBlocks');
        $this->assertTrue((bool) ($expanded[$second->id] ?? false));
        $this->assertFalse((bool) ($expanded[$first->id] ?? false));
        $component->assertDontSee('Barbell Back Squat');

        $component->call('toggleBlock', $first->id)->assertSee('Barbell Back Squat');

        $expanded = $component->get('expandedBlocks');
        $this->assertTrue((bool) ($expanded[$first->id] ?? false));
        $this->assertFalse((bool) ($expanded[$second->id] ?? false));
    }

    public function test_exercises_can_be_grouped_into_a_block(): void
    {
        $user = User::factory()->guest()->create();
        $squat = Exercise::query()->where('name', 'Barbell Back Squat')->firstOrFail();
        $press = Exercise::query()->where('name', 'Bench Press')->firstOrFail();

        $this->actingAs($user);

        $component = Livewire::test(LogWorkout::class)
            ->call('addBlock')
            ->assertSet('blockNames', [1 => 'Block A']);

        $block = WorkoutBlock::query()->firstOrFail();

        $component
            ->call('openExercisePicker', $block->id)
            ->assertSee('Adding to Block A')
            ->call('addExercise', $squat->id)
            ->assertSet('showExercisePicker', true);

        $this->assertDatabaseHas('workout_exercises', [
            'exercise_id' => $squat->id,
            'workout_block_id' => $block->id,
        ]);

        $component
            ->set('blockNames.'.$block->id, 'Strength')
            ->assertHasNoErrors();

        $this->assertSame('Strength', $block->fresh()->name);

        $component
            ->call('openExercisePicker')
            ->call('addExercise', $press->id);

        $pressEntry = WorkoutExercise::query()->where('exercise_id', $press->id)->firstOrFail();
        $this->assertNull($pressEntry->workout_block_id);

        $component->call('assignExercise', $pressEntry->id, (string) $block->id);

        $pressEntry->refresh();
        $this->assertSame($block->id, $pressEntry->workout_block_id);

        $component->call('removeBlock', $block->id);

        $this->assertModelMissing($block);
        $this->assertNull($pressEntry->fresh()->workout_block_id);
        $this->assertModelExists($pressEntry);
    }

    public function test_the_month_view_opens_the_day_that_was_clicked(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-29 10:00:00'));
        $user = User::factory()->guest()->create();
        $exercise = Exercise::query()->where('name', 'Bench Press')->firstOrFail();

        $workout = Workout::factory()->for($user)->create([
            'performed_on' => '2026-09-14',
            'type' => 'Strength - Upper Body',
            'completed_at' => Carbon::parse('2026-09-14 18:00:00'),
        ]);
        $workout->exercises()->create([
            'exercise_id' => $exercise->id,
            'position' => 1,
        ]);

        Workout::factory()->create([
            'performed_on' => '2026-09-15',
            'type' => 'Conditioning',
        ]);

        $this->actingAs($user);

        Livewire::test(LogWorkout::class)
            ->call('showMonth')
            ->assertSet('view', 'month')
            ->assertSee('September 2026')
            ->assertSee('Strength')
            ->assertDontSee('Conditioning')
            ->assertDontSee('Type of workout')
            ->call('nextMonth')
            ->assertSee('October 2026')
            ->assertDontSee('Strength')
            ->call('previousMonth')
            ->call('selectDay', '2026-09-14')
            ->assertSet('view', 'day')
            ->assertSet('date', '2026-09-14')
            ->assertSee('Mon, Sep 14, 2026')
            ->assertSee('Bench Press')
            ->assertSee('Type of workout')
            ->call('showToday')
            ->assertSet('date', '2026-09-29')
            ->assertSet('view', 'day')
            ->assertSee('Tue, Sep 29, 2026');
    }

    public function test_a_user_cannot_change_someone_elses_log(): void
    {
        $owner = User::factory()->guest()->create();
        $other = User::factory()->guest()->create();
        $exercise = Exercise::query()->where('name', 'Plank')->firstOrFail();

        $this->actingAs($owner);

        Livewire::test(LogWorkout::class)->call('addExercise', $exercise->id);

        $entry = WorkoutExercise::query()->firstOrFail();

        $this->actingAs($other);

        Livewire::test(LogWorkout::class)
            ->call('removeExercise', $entry->id)
            ->assertNotFound();

        $this->assertModelExists($entry);

        $this->actingAs($owner);

        Livewire::test(LogWorkout::class)->call('addBlock');
        $block = WorkoutBlock::query()->firstOrFail();

        $this->actingAs($other);

        Livewire::test(LogWorkout::class)
            ->call('removeBlock', $block->id)
            ->assertNotFound();

        $this->assertModelExists($block);
    }

    public function test_a_day_can_hold_two_sessions_and_one_can_be_deleted(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-29 10:00:00'));
        $user = User::factory()->guest()->create();

        $this->actingAs($user);

        $log = Livewire::test(LogWorkout::class)
            ->set('type', 'Strength - Full Body')
            ->call('startSession')
            ->set('type', 'Mobility')
            ->assertSee('Strength')
            ->assertSee('Mobility');

        Livewire::test(LogWorkout::class)
            ->assertSet('type', 'Strength - Full Body');

        $log->call('deleteSession');

        $remaining = Workout::query()->get();

        $this->assertCount(1, $remaining);
        $this->assertSame('Strength - Full Body', $remaining->first()->type);
    }

    public function test_run_exercises_are_left_to_the_runs_page(): void
    {
        $user = User::factory()->guest()->create();

        $this->actingAs($user);

        Livewire::test(LogWorkout::class)
            ->set('type', 'Cardio')
            ->call('openExercisePicker')
            ->assertDontSee('Outdoor Run')
            ->assertDontSee('Treadmill Run')
            ->assertDontSee('Bench Press')
            ->assertDontSee('>Upper Body<', false)
            ->assertSee('Rowing')
            ->assertSee('logged on')
            ->assertSee(route('runs'), false);
    }

    public function test_add_session_hides_after_the_workout_is_complete(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-29 10:00:00'));
        $user = User::factory()->guest()->create();
        $exercise = Exercise::query()->where('name', 'Bench Press')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(LogWorkout::class)
            ->set('type', 'Strength - Full Body')
            ->assertSee('Add session')
            ->call('addExercise', $exercise->id)
            ->call('complete')
            ->assertDontSee('Add session')
            ->call('startSession');

        $this->assertSame(1, Workout::query()->count());
    }

    public function test_bodyweight_conditioning_and_mobility_list_only_their_exercises(): void
    {
        $user = User::factory()->guest()->create();
        $benchId = Exercise::query()->where('name', 'Bench Press')->value('id');

        $this->actingAs($user);

        Livewire::test(LogWorkout::class)
            ->set('type', 'Bodyweight')
            ->call('openExercisePicker')
            ->assertSee('Push-Up')
            ->assertSee('Plank')
            ->assertDontSee('Bench Press')
            ->assertDontSee('>Upper Body<', false)
            ->assertDontSee('Rowing')
            ->call('addExercise', $benchId)
            ->assertNotFound();

        Livewire::test(LogWorkout::class)
            ->set('type', 'Conditioning')
            ->call('openExercisePicker')
            ->assertSee('Burpee')
            ->assertSee('Kettlebell Swing')
            ->assertDontSee('Bench Press')
            ->assertDontSee('>Upper Body<', false)
            ->assertDontSee('Plank');

        Livewire::test(LogWorkout::class)
            ->set('type', 'Mobility')
            ->call('openExercisePicker')
            ->assertSee('Cat-Cow')
            ->assertSee("World's Greatest Stretch")
            ->assertDontSee('Bench Press')
            ->assertDontSee('>Upper Body<', false)
            ->assertDontSee('Push-Up');
    }

    public function test_a_strength_session_does_not_list_cardio_exercises(): void
    {
        $user = User::factory()->guest()->create();

        $this->actingAs($user);

        Livewire::test(LogWorkout::class)
            ->set('type', 'Strength - Full Body')
            ->set('exerciseQuery', 'Squat')
            ->call('openExercisePicker')
            ->assertSee('Zercher Squat')
            ->assertSee('Zombie Squat')
            ->set('exerciseQuery', 'Bench Press')
            ->assertSee('Bench Press')
            ->assertDontSee('Rowing')
            ->call('addExercise', Exercise::query()->where('name', 'Rowing')->value('id'))
            ->assertNotFound();
    }

    public function test_a_workout_can_be_copied_as_text(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30 10:00:00'));
        $user = User::factory()->guest()->create();
        $bench = Exercise::query()->where('name', 'Bench Press')->firstOrFail();
        $row = Exercise::query()->where('name', 'Barbell Row')->firstOrFail();
        $custom = Exercise::query()->create([
            'user_id' => $user->id,
            'name' => 'Battle Ropes',
        ]);

        $workout = Workout::factory()->for($user)->create([
            'performed_on' => '2026-09-30',
            'type' => 'Strength - Upper Body',
        ]);
        $block = $workout->blocks()->create(['name' => 'Main', 'position' => 1]);
        $benchEntry = $workout->exercises()->create([
            'exercise_id' => $bench->id,
            'position' => 1,
        ]);
        $benchEntry->sets()->create([
            'position' => 1,
            'weight' => 185,
            'reps' => 5,
        ]);
        $benchEntry->sets()->create([
            'position' => 2,
            'weight' => 205,
            'reps' => 3,
            'actual_weight' => 200,
            'actual_reps' => 3,
        ]);
        $benchEntry->sets()->create(['position' => 3]);
        $workout->exercises()->create([
            'exercise_id' => $custom->id,
            'position' => 2,
        ]);
        $rowEntry = $workout->exercises()->create([
            'exercise_id' => $row->id,
            'workout_block_id' => $block->id,
            'position' => 1,
        ]);
        $rowEntry->sets()->create([
            'position' => 1,
            'weight' => 135,
            'reps' => 8,
        ]);

        $expected = <<<'TEXT'
Wed, Sep 30, 2026
Strength - Upper Body

Bench Press
185x5
205x3

Battle Ropes

Main
Barbell Row
1x8 @ 135
TEXT;

        $this->assertSame($expected, WorkoutText::from($workout));

        $this->actingAs($user);

        $component = Livewire::test(LogWorkout::class)
            ->assertSee('Share')
            ->call('copyShare');

        $script = json_encode($component->effects['xjs'] ?? []);

        $this->assertStringContainsString('navigator.clipboard.writeText', $script);
        $this->assertStringContainsString('Battle Ropes', $script);
        $this->assertStringContainsString('Barbell Row', $script);
    }

    public function test_copied_text_groups_repeated_sets_and_uses_the_exercise_measure(): void
    {
        $user = User::factory()->guest()->create();
        $pushUp = Exercise::query()->where('name', 'Push-Up')->firstOrFail();
        $row = Exercise::query()->where('name', 'Pendlay Row')->first();
        $plank = Exercise::query()->where('name', 'Plank')->firstOrFail();

        if ($row === null) {
            $row = Exercise::query()->create([
                'name' => 'Pendlay Row',
                'measure' => 'reps',
                'approved_at' => now(),
            ]);
        }

        $workout = Workout::factory()->for($user)->create([
            'performed_on' => '2026-09-30',
            'type' => 'Workout',
        ]);
        $push = $workout->exercises()->create([
            'exercise_id' => $pushUp->id,
            'position' => 1,
        ]);

        foreach ([1, 2, 3] as $position) {
            $push->sets()->create([
                'position' => $position,
                'reps' => 10,
            ]);
        }

        $pendlay = $workout->exercises()->create([
            'exercise_id' => $row->id,
            'position' => 2,
        ]);

        foreach ([35, 45, 60, 60, 60] as $index => $weight) {
            $pendlay->sets()->create([
                'position' => $index + 1,
                'weight' => $weight,
                'reps' => 8,
            ]);
        }

        $hold = $workout->exercises()->create([
            'exercise_id' => $plank->id,
            'position' => 3,
        ]);

        foreach ([1, 2, 3, 4] as $position) {
            $hold->sets()->create([
                'position' => $position,
                'reps' => 15,
            ]);
        }

        $expected = <<<'TEXT'
Wed, Sep 30, 2026
Workout

Push-Up
3x10

Pendlay Row
5x8

Plank
4x15s
TEXT;

        $this->assertSame($expected, WorkoutText::from($workout));
    }

    public function test_copied_text_drops_the_load_when_every_set_has_the_same_reps(): void
    {
        $user = User::factory()->guest()->create();
        $bench = Exercise::query()->where('name', 'Bench Press')->firstOrFail();
        $workout = Workout::factory()->for($user)->create([
            'performed_on' => '2026-09-30',
            'type' => 'Strength - Upper Body',
        ]);
        $entry = $workout->exercises()->create([
            'exercise_id' => $bench->id,
            'position' => 1,
        ]);

        $entry->sets()->create([
            'position' => 1,
            'weight' => 115,
            'reps' => 8,
        ]);

        foreach ([2, 3, 4] as $position) {
            $entry->sets()->create([
                'position' => $position,
                'weight' => 115,
                'reps' => 8,
                'actual_weight' => 95,
                'actual_reps' => 8,
            ]);
        }

        $expected = <<<'TEXT'
Wed, Sep 30, 2026
Strength - Upper Body

Bench Press
4x8
TEXT;

        $this->assertSame($expected, WorkoutText::from($workout));
    }

    public function test_a_meter_exercise_is_logged_as_distance(): void
    {
        $user = User::factory()->guest()->create();

        $this->actingAs($user);

        $component = Livewire::test(LogWorkout::class)
            ->call('openExercisePicker')
            ->set('exerciseQuery', 'SkiErg')
            ->set('customMeasure', 'meters')
            ->call('addCustomExercise')
            ->assertSee('Planned Meters')
            ->assertSee('Actual Meters');

        $exercise = Exercise::query()->where('name', 'SkiErg')->firstOrFail();
        $set = WorkoutSet::query()->firstOrFail();

        $component->set("setReps.{$set->id}", '100')->assertHasNoErrors();

        $this->assertSame('meters', $exercise->measure);
        $this->assertSame(100, $set->fresh()->reps);
        $this->assertStringContainsString("SkiErg\n1x100 m", WorkoutText::from($set->workoutExercise->workout));
    }

    public function test_time_and_calorie_exercises_use_their_own_inputs(): void
    {
        $user = User::factory()->guest()->create();
        $plank = Exercise::query()->where('name', 'Plank')->firstOrFail();

        $this->actingAs($user);

        $component = Livewire::test(LogWorkout::class)
            ->set('type', 'Strength - Full Body')
            ->call('addExercise', $plank->id)
            ->assertSee('Planned Time')
            ->assertSee('Actual Time')
            ->assertDontSee('Planned Weight (lbs)');

        $set = WorkoutSet::query()->firstOrFail();

        $component
            ->set("setReps.{$set->id}", '1:30')
            ->assertHasNoErrors()
            ->set("setActualReps.{$set->id}", '1:00')
            ->assertHasNoErrors()
            ->set("setReps.{$set->id}", 'nope')
            ->assertHasErrors(["setReps.{$set->id}"]);

        $set->refresh();
        $this->assertSame(90, $set->reps);
        $this->assertSame(60, $set->actual_reps);
        $this->assertNull($set->weight);

        Livewire::test(LogWorkout::class)
            ->call('openExercisePicker')
            ->set('exerciseQuery', 'Assault Bike')
            ->set('customMeasure', 'calories')
            ->call('addCustomExercise')
            ->assertSee('Planned Calories')
            ->assertSee('Tracked by');

        $bike = Exercise::query()->where('name', 'Assault Bike')->firstOrFail();

        $this->assertSame('calories', $bike->measure);
        $this->assertSame($user->id, $bike->user_id);
    }
}
