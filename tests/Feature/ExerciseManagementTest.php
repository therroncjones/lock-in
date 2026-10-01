<?php

namespace Tests\Feature;

use App\Filament\Resources\Exercises\ExerciseResource;
use App\Filament\Resources\Exercises\Pages\CreateExercise;
use App\Filament\Resources\Exercises\Pages\ListExercises;
use App\Livewire\LogWorkout;
use App\Models\Exercise;
use App\Models\User;
use Database\Seeders\ExerciseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExerciseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExerciseSeeder::class);
    }

    public function test_guests_cannot_manage_exercises(): void
    {
        $guest = User::factory()->guest()->create();

        $this->actingAs($guest);

        Livewire::test(ListExercises::class)
            ->assertForbidden();

        Livewire::test(CreateExercise::class)
            ->assertForbidden();
    }

    public function test_administrators_can_add_an_exercise_for_a_session(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin);

        Livewire::test(CreateExercise::class)
            ->fillForm([
                'name' => 'Hip Airplane',
                'group' => 'Mobility',
                'session_types' => ['Mobility'],
                'user_id' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $exercise = Exercise::query()->where('name', 'Hip Airplane')->first();

        $this->assertNotNull($exercise);
        $this->assertNull($exercise->user_id);
        $this->assertSame(['Mobility'], $exercise->session_types);

        Livewire::test(ListExercises::class)
            ->assertSuccessful()
            ->searchTable('Hip Airplane')
            ->assertSee('Hip Airplane');

        $guest = User::factory()->guest()->create();

        $this->actingAs($guest);

        Livewire::test(LogWorkout::class)
            ->set('type', 'Mobility')
            ->call('openExercisePicker')
            ->assertSee('Hip Airplane')
            ->assertDontSee('Bench Press');

        Livewire::test(LogWorkout::class)
            ->set('type', 'Strength - Full Body')
            ->call('openExercisePicker')
            ->assertDontSee('Hip Airplane')
            ->set('exerciseQuery', 'Bench Press')
            ->assertSee('Bench Press');
    }

    public function test_the_shared_library_includes_the_expanded_catalog(): void
    {
        $airSquat = Exercise::query()->where('name', 'Air Squat')->first();
        $arnold = Exercise::query()->where('name', 'Arnold Press')->first();
        $crunch = Exercise::query()->where('name', 'Crunch')->first();
        $snatch = Exercise::query()->where('name', 'Snatch')->first();
        $jefferson = Exercise::query()->where('name', 'Jefferson Curl')->first();

        $this->assertNotNull($airSquat);
        $this->assertSame('Lower Body', $airSquat->group);
        $this->assertContains('Bodyweight', $airSquat->session_types);
        $this->assertContains('Strength - Lower Body', $airSquat->session_types);
        $this->assertContains('Strength - Full Body', $airSquat->session_types);
        $this->assertSame('Upper Body', $arnold?->group);
        $this->assertSame('Core', $crunch?->group);
        $this->assertSame('reps', Exercise::query()->where('name', 'Bench Press')->first()?->measure);
        $this->assertSame('time', Exercise::query()->where('name', 'Plank')->first()?->measure);
        $this->assertSame('time', Exercise::query()->where('name', 'Rowing')->first()?->measure);
        $this->assertSame('Full Body', $snatch?->group);
        $this->assertSame('Mobility', $jefferson?->group);
        $this->assertNull(Exercise::query()->where('name', 'Rowing Machine')->first());
        $this->assertNotNull(Exercise::query()->where('name', 'Rowing')->first());
    }

    public function test_a_custom_exercise_stays_pending_until_an_administrator_approves_it(): void
    {
        $admin = User::factory()->administrator()->create();
        $guest = User::factory()->guest()->create();

        $this->assertNull(ExerciseResource::getNavigationBadge());

        $exercise = Exercise::query()->create([
            'user_id' => $guest->id,
            'name' => 'Battle Ropes',
        ]);

        $this->assertFalse($exercise->approve());
        $exercise->update(['session_types' => ['Conditioning']]);

        $this->actingAs($admin);
        $this->assertSame('1', ExerciseResource::getNavigationBadge());

        Livewire::test(ListExercises::class)
            ->searchTable('Battle Ropes')
            ->assertSee('Battle Ropes')
            ->assertSee('Pending');

        $this->assertTrue($exercise->fresh()->approve());
        $exercise->refresh();

        $this->assertNull($exercise->user_id);
        $this->assertNotNull($exercise->approved_at);
        $this->assertNull(ExerciseResource::getNavigationBadge());
    }

    public function test_the_exercise_list_remembers_its_filters(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin);

        Livewire::test(ListExercises::class)
            ->filterTable('group', 'Upper Body');

        Livewire::test(ListExercises::class)
            ->assertSet('tableFilters.group.value', 'Upper Body');
    }
}
