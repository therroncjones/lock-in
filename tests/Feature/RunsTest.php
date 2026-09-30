<?php

namespace Tests\Feature;

use App\Livewire\Runs;
use App\Models\Run;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RunsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('runs'))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_a_run_can_be_logged_and_shown_apart_from_workouts(): void
    {
        $user = User::factory()->guest()->create();
        $this->actingAs($user);
        $this->travelTo('2026-09-29 12:00:00');

        Livewire::test(Runs::class)
            ->assertSee('No runs in this range.')
            ->call('startLog')
            ->set('performedOn', '2026-09-29')
            ->set('startedAt', '06:28')
            ->set('type', 'outdoor')
            ->set('distance', '3.21')
            ->set('duration', '32:18')
            ->set('elevationGain', '120')
            ->set('calories', '328')
            ->set('averageHeartRate', '161')
            ->set('maxHeartRate', '186')
            ->set('temperature', '68')
            ->set('notes', 'Great run. Felt strong.')
            ->set('splits', [
                ['distance' => '1', 'duration' => '10:16', 'elevation' => '12'],
                ['distance' => '0.21', 'duration' => '2:03', 'elevation' => '-6'],
            ])
            ->call('saveRun')
            ->assertSee('Tue, Sep 29, 2026')
            ->assertSee('6:28 AM')
            ->assertSee('Outdoor Run')
            ->assertSee('3.21')
            ->assertSee('32:18')
            ->assertSee('10:04')
            ->assertSee('328')
            ->assertSee('161')
            ->assertSee('186')
            ->assertSee('120 ft')
            ->assertSee('68°F')
            ->assertSee('Great run. Felt strong.')
            ->assertSee('10:16')
            ->assertSee('+12 ft')
            ->assertSee('-6 ft')
            ->assertDontSee('No runs in this range.');

        $this->assertSame(1, Run::query()->count());
        $this->assertSame(2, Run::query()->first()->splits()->count());

        Livewire::test(Runs::class)
            ->set('activity', 'walk')
            ->assertDontSee('Great run. Felt strong.')
            ->assertSee('No runs in this range.');
    }

    public function test_runs_stay_with_the_user_who_logged_them(): void
    {
        $user = User::factory()->guest()->create();
        $other = User::factory()->guest()->create();

        Run::query()->create([
            'user_id' => $other->id,
            'performed_on' => '2026-09-29',
            'type' => 'outdoor',
            'distance_miles' => 5,
            'duration_seconds' => 3000,
            'notes' => 'Someone else ran',
        ]);

        $this->actingAs($user);

        Livewire::test(Runs::class)
            ->assertDontSee('Someone else ran')
            ->assertSee('No runs in this range.');
    }

    public function test_this_month_keeps_only_runs_from_the_current_month(): void
    {
        $user = User::factory()->guest()->create();
        $this->actingAs($user);
        $this->travelTo('2026-09-29');

        foreach (['2026-08-20', '2026-09-15'] as $date) {
            Run::query()->create([
                'user_id' => $user->id,
                'performed_on' => $date,
                'type' => 'outdoor',
                'distance_miles' => 3,
                'duration_seconds' => 1800,
            ]);
        }

        Livewire::test(Runs::class)
            ->assertSet('range', 'month')
            ->assertSee('This Month')
            ->set('range', 'month')
            ->assertSee('Tue, Sep 15, 2026')
            ->assertDontSee('Thu, Aug 20, 2026')
            ->assertSee('vs last month');
    }

    public function test_run_history_shows_ten_runs_per_page(): void
    {
        $user = User::factory()->guest()->create();
        $this->actingAs($user);
        $this->travelTo('2026-09-29');

        foreach (range(1, 11) as $day) {
            Run::query()->create([
                'user_id' => $user->id,
                'performed_on' => sprintf('2026-09-%02d', $day),
                'type' => 'outdoor',
                'distance_miles' => 3,
                'duration_seconds' => 1800,
                'notes' => 'history-page-'.$day,
            ]);
        }

        Livewire::test(Runs::class)
            ->assertSet('range', 'month')
            ->assertSee('Thu, Sep 10, 2026')
            ->assertDontSee('Tue, Sep 1, 2026')
            ->call('nextPage')
            ->assertSee('Tue, Sep 1, 2026')
            ->assertDontSee('Thu, Sep 10, 2026');
    }

    public function test_a_run_can_be_edited(): void
    {
        $user = User::factory()->guest()->create();
        $this->actingAs($user);
        $this->travelTo('2026-09-29 12:00:00');

        $run = Run::query()->create([
            'user_id' => $user->id,
            'performed_on' => '2026-09-29',
            'started_at' => '06:28:00',
            'type' => 'outdoor',
            'distance_miles' => 3.21,
            'duration_seconds' => 1938,
        ]);

        Livewire::test(Runs::class)
            ->call('editRun', $run->id)
            ->set('distance', '4.00')
            ->call('saveRun')
            ->assertSee('4.00')
            ->assertDontSee('3.21');

        $this->assertSame('4.00', $run->fresh()->distance_miles);
    }
}
