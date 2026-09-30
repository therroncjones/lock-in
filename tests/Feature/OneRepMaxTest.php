<?php

namespace Tests\Feature;

use App\Livewire\OneRepMax;
use App\Models\Exercise;
use App\Models\OneRepMax as OneRepMaxRecord;
use App\Models\User;
use Database\Seeders\ExerciseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OneRepMaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExerciseSeeder::class);
    }

    public function test_a_user_can_set_and_clear_a_one_rep_max(): void
    {
        $user = User::factory()->guest()->create();
        $bench = Exercise::query()->where('name', 'Bench Press')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(OneRepMax::class)
            ->assertSee('Bench Press')
            ->set('weights.'.$bench->id, '225')
            ->assertHasNoErrors();

        $max = OneRepMaxRecord::query()->firstOrFail();
        $this->assertSame($user->id, $max->user_id);
        $this->assertSame($bench->id, $max->exercise_id);
        $this->assertSame('225.00', $max->weight);
        $this->assertSame(now()->toDateString(), $max->recorded_on->toDateString());

        Livewire::test(OneRepMax::class)
            ->assertSet('weights.'.$bench->id, '225')
            ->set('weights.'.$bench->id, '')
            ->assertHasNoErrors();

        $this->assertSame(0, OneRepMaxRecord::query()->count());
    }

    public function test_a_weight_saves_when_the_whole_list_is_updated(): void
    {
        $user = User::factory()->guest()->create();
        $squat = Exercise::query()->where('name', 'Barbell Back Squat')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(OneRepMax::class)
            ->set('weights', [$squat->id => '315'])
            ->assertHasNoErrors();

        $max = OneRepMaxRecord::query()->firstOrFail();
        $this->assertSame($user->id, $max->user_id);
        $this->assertSame($squat->id, $max->exercise_id);
        $this->assertSame('315.00', $max->weight);
    }

    public function test_a_later_day_keeps_the_earlier_one_rep_max(): void
    {
        $user = User::factory()->guest()->create();
        $bench = Exercise::query()->where('name', 'Bench Press')->firstOrFail();

        $this->actingAs($user);

        $this->travelTo('2026-09-28');

        Livewire::test(OneRepMax::class)
            ->set('weights.'.$bench->id, '200')
            ->assertHasNoErrors();

        $this->travelTo('2026-09-29');

        Livewire::test(OneRepMax::class)
            ->assertSet('weights.'.$bench->id, '200')
            ->set('weights.'.$bench->id, '225')
            ->assertHasNoErrors()
            ->assertSet('weights.'.$bench->id, '225');

        $records = OneRepMaxRecord::query()->orderBy('recorded_on')->get();
        $this->assertCount(2, $records);
        $this->assertSame('2026-09-28', $records[0]->recorded_on->toDateString());
        $this->assertSame('200.00', $records[0]->weight);
        $this->assertSame('2026-09-29', $records[1]->recorded_on->toDateString());
        $this->assertSame('225.00', $records[1]->weight);

        Livewire::test(OneRepMax::class)
            ->set('weights.'.$bench->id, '230')
            ->assertHasNoErrors();

        $records = OneRepMaxRecord::query()->orderBy('recorded_on')->get();
        $this->assertCount(2, $records);
        $this->assertSame('200.00', $records[0]->weight);
        $this->assertSame('2026-09-28', $records[0]->recorded_on->toDateString());
        $this->assertSame('230.00', $records[1]->weight);
        $this->assertSame('2026-09-29', $records[1]->recorded_on->toDateString());

        Livewire::test(OneRepMax::class)
            ->set('weights.'.$bench->id, '')
            ->assertHasNoErrors()
            ->assertSet('weights.'.$bench->id, '200');

        $remaining = OneRepMaxRecord::query()->firstOrFail();
        $this->assertSame('200.00', $remaining->weight);
        $this->assertSame('2026-09-28', $remaining->recorded_on->toDateString());
    }

    public function test_one_rep_maxes_stay_with_the_user_who_set_them(): void
    {
        $user = User::factory()->guest()->create();
        $other = User::factory()->guest()->create();
        $squat = Exercise::query()->where('name', 'Barbell Back Squat')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(OneRepMax::class)->set('weights.'.$squat->id, '315');

        $this->actingAs($other);

        Livewire::test(OneRepMax::class)->assertSet('weights.'.$squat->id, null);

        $custom = Exercise::query()->create([
            'user_id' => $user->id,
            'name' => 'Sled Push',
        ]);

        Livewire::test(OneRepMax::class)
            ->set('weights.'.$custom->id, '200')
            ->assertNotFound();

        $this->assertSame(1, OneRepMaxRecord::query()->count());
    }

    public function test_each_lift_lists_percentages_and_the_plates_for_the_chosen_bar(): void
    {
        $user = User::factory()->guest()->create();
        $bench = Exercise::query()->where('name', 'Bench Press')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(OneRepMax::class)
            ->call('openExercise', $bench->id)
            ->assertDontSee('20%')
            ->set('weights.'.$bench->id, '225')
            ->assertSee('20%')
            ->assertSee('100%')
            ->assertSee('157.5')
            ->assertSee('grid grid-cols-2 items-end gap-3', false)
            ->assertSee('Plate Calculator')
            ->assertSee('45 lb')
            ->assertSee('× 1')
            ->set('barWeight', '0')
            ->assertDontSee('Plate Calculator')
            ->set('barWeight', '44')
            ->set('customPercents.'.$bench->id, '72.5')
            ->assertSee('72.5%')
            ->assertSee('163.13');
    }

    public function test_a_new_max_can_be_added_for_a_chosen_date(): void
    {
        $user = User::factory()->guest()->create();
        $bench = Exercise::query()->where('name', 'Bench Press')->firstOrFail();

        $this->actingAs($user);
        $this->travelTo('2026-09-29');

        Livewire::test(OneRepMax::class)
            ->call('openNewMax', $bench->id)
            ->set('newDate', '2026-09-01')
            ->set('newWeight', '200')
            ->call('saveNewMax')
            ->assertRedirect(route('one-rep-max', ['lift' => $bench->id]));

        $this->get(route('one-rep-max', ['lift' => $bench->id]))
            ->assertOk()
            ->assertSee('Assessed Sep 1, 2026');

        Livewire::test(OneRepMax::class)
            ->call('openNewMax', $bench->id)
            ->set('newDate', '2026-09-29')
            ->set('newWeight', '225')
            ->call('saveNewMax')
            ->assertRedirect(route('one-rep-max', ['lift' => $bench->id]));

        $this->get(route('one-rep-max', ['lift' => $bench->id]))
            ->assertOk()
            ->assertSee('Assessed Sep 29, 2026')
            ->assertSee('225');

        Livewire::test(OneRepMax::class)
            ->set('showingAllEstimates', true)
            ->assertSee('Sep 1, 2026');

        Livewire::test(OneRepMax::class)
            ->call('openNewMax', $bench->id)
            ->set('newDate', '2026-09-29')
            ->set('newWeight', '230')
            ->call('saveNewMax')
            ->assertRedirect(route('one-rep-max', ['lift' => $bench->id]));

        Livewire::test(OneRepMax::class)
            ->call('openNewMax', $bench->id)
            ->set('newDate', '2026-09-30')
            ->set('newWeight', '240')
            ->call('saveNewMax')
            ->assertHasErrors('newDate');

        $records = OneRepMaxRecord::query()->orderBy('recorded_on')->get();
        $this->assertCount(2, $records);
        $this->assertSame('200.00', $records[0]->weight);
        $this->assertSame('2026-09-01', $records[0]->recorded_on->toDateString());
        $this->assertSame('230.00', $records[1]->weight);
        $this->assertSame('2026-09-29', $records[1]->recorded_on->toDateString());
    }

    public function test_only_the_three_main_lifts_are_shown_in_order(): void
    {
        $user = User::factory()->guest()->create();
        $row = Exercise::query()->where('name', 'Barbell Row')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(OneRepMax::class)
            ->assertDontSee('Search exercises')
            ->assertDontSee('Save to History')
            ->assertDontSee('Calculation method')
            ->assertDontSee('Mixed Plates')
            ->assertDontSee('Tip')
            ->assertDontSee('Common % Breakdown')
            ->assertDontSee('Barbell Row')
            ->assertDontSee('Incline Bench Press')
            ->assertDontSee('Exercise Library')
            ->assertSee('Add a known max')
            ->assertSeeInOrder([
                'Barbell Back Squat',
                'Bench Press',
                'Conventional Deadlift',
            ])
            ->set('weights.'.$row->id, '185')
            ->assertNotFound();
    }

    public function test_view_all_recent_maxes_is_paginated(): void
    {
        $user = User::factory()->guest()->create();
        $bench = Exercise::query()->where('name', 'Bench Press')->firstOrFail();

        $this->actingAs($user);

        foreach (range(1, 11) as $day) {
            OneRepMaxRecord::query()->create([
                'user_id' => $user->id,
                'exercise_id' => $bench->id,
                'recorded_on' => sprintf('2026-01-%02d', $day),
                'weight' => 300 + $day,
            ]);
        }

        Livewire::test(OneRepMax::class)
            ->assertDontSee('Next')
            ->assertSee('Jan 11, 2026')
            ->assertDontSee('Jan 1, 2026')
            ->call('toggleEstimates')
            ->assertSee('Next')
            ->assertSee('Jan 11, 2026')
            ->assertDontSee('Jan 1, 2026')
            ->call('nextPage')
            ->assertSee('Jan 1, 2026')
            ->assertDontSee('Jan 11, 2026');
    }

    public function test_a_saved_max_can_be_deleted(): void
    {
        $user = User::factory()->guest()->create();
        $bench = Exercise::query()->where('name', 'Bench Press')->firstOrFail();
        $max = OneRepMaxRecord::query()->create([
            'user_id' => $user->id,
            'exercise_id' => $bench->id,
            'recorded_on' => '2026-09-29',
            'weight' => 185,
        ]);

        $this->actingAs($user);

        Livewire::test(OneRepMax::class)
            ->assertSee('Recent maxes')
            ->call('deleteMax', $max->id)
            ->assertRedirect(route('one-rep-max', ['lift' => $bench->id]));

        $this->assertModelMissing($max);

        $this->get(route('home'))
            ->assertSee('Bench, squat, and deadlift are not on record yet.')
            ->assertSee('data-one-rep-max-alert="3"', false);
    }
}
