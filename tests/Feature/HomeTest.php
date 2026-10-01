<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\OneRepMax;
use App\Models\Run;
use App\Models\User;
use App\Models\Workout;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('log'))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_the_home_screen_uses_the_time_of_day_and_a_monday_week(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30 15:00:00'));

        $user = User::factory()->guest()->create([
            'name' => 'Therron Chase Jones',
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Good afternoon, Therron!')
            ->assertSee('Put the work on record')
            ->assertSee('Tap to start logging')
            ->assertSeeInOrder(['Home', 'One Rep Max', 'Strength', 'Runs', 'Progress'])
            ->assertSee('app-sidebar', false)
            ->assertSee('app-main', false)
            ->assertSee('fa-lock', false)
            ->assertDontSee('Lower Body')
            ->assertDontSee('Upper Body')
            ->assertSeeInOrder([
                'data-date="2026-09-28"',
                'data-date="2026-09-29"',
                'data-date="2026-09-30"',
                'data-date="2026-10-01"',
                'data-date="2026-10-02"',
                'data-date="2026-10-03"',
                'data-date="2026-10-04"',
            ], false);
    }

    public function test_the_greeting_changes_with_the_time_of_day(): void
    {
        $user = User::factory()->guest()->create(['name' => 'Therron Jones']);

        Carbon::setTestNow(Carbon::parse('2026-09-29 08:30:00'));
        $this->actingAs($user)->get(route('home'))->assertSee('Good morning, Therron!');

        Carbon::setTestNow(Carbon::parse('2026-09-29 12:00:00'));
        $this->actingAs($user)->get(route('home'))->assertSee('Good afternoon, Therron!');

        Carbon::setTestNow(Carbon::parse('2026-09-29 17:00:00'));
        $this->actingAs($user)->get(route('home'))->assertSee('Good evening, Therron!');
    }

    public function test_this_week_marks_only_the_signed_in_users_completed_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30 09:00:00'));

        $user = User::factory()->guest()->create();
        $someoneElse = User::factory()->guest()->create();

        Workout::factory()->create([
            'user_id' => $user->id,
            'performed_on' => '2026-09-28',
            'completed_at' => now(),
        ]);
        Workout::factory()->incomplete()->create([
            'user_id' => $user->id,
            'performed_on' => '2026-09-29',
        ]);
        Workout::factory()->create([
            'user_id' => $someoneElse->id,
            'performed_on' => '2026-09-30',
            'completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertSee('Tap to start logging')
            ->assertDontSee('View Workout')
            ->assertSee('data-date="2026-09-28" data-completed="true"', false)
            ->assertSee('data-date="2026-09-29" data-completed="false"', false)
            ->assertSee('data-date="2026-09-30" data-completed="false"', false);

        Workout::factory()->create([
            'user_id' => $user->id,
            'performed_on' => '2026-09-30',
            'completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertSee('View Workout')
            ->assertDontSee('Tap to start logging');
    }

    public function test_week_navigation_moves_to_the_previous_monday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30 09:00:00'));

        $user = User::factory()->guest()->create();

        $this->actingAs($user)
            ->get(route('home', ['week' => -1]))
            ->assertOk()
            ->assertSee('data-date="2026-09-21"', false)
            ->assertSee('data-date="2026-09-27"', false)
            ->assertDontSee('data-date="2026-09-28"', false)
            ->assertSee('Sep 21 - Sep 27')
            ->assertDontSee('This week');
    }

    public function test_the_home_screen_asks_for_a_one_rep_max_until_one_is_saved(): void
    {
        $user = User::factory()->guest()->create();
        $someoneElse = User::factory()->guest()->create();
        $bench = Exercise::query()->create([
            'name' => 'Bench Press',
        ]);
        $squat = Exercise::query()->create([
            'name' => 'Barbell Back Squat',
        ]);
        $deadlift = Exercise::query()->create([
            'name' => 'Conventional Deadlift',
        ]);

        OneRepMax::query()->create([
            'user_id' => $someoneElse->id,
            'exercise_id' => $bench->id,
            'recorded_on' => '2026-09-29',
            'weight' => 225,
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Add your one rep max')
            ->assertSee('Bench, squat, and deadlift are not on record yet.')
            ->assertSee('data-one-rep-max-alert="3"', false)
            ->assertSee('ml-auto grid shrink-0', false);

        OneRepMax::query()->create([
            'user_id' => $user->id,
            'exercise_id' => $bench->id,
            'recorded_on' => '2026-09-29',
            'weight' => 185,
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Add your one rep max')
            ->assertSee('Squat and deadlift are not on record yet.')
            ->assertSee('data-one-rep-max-alert="2"', false)
            ->assertDontSee('data-one-rep-max-alert="3"', false);

        OneRepMax::query()->create([
            'user_id' => $user->id,
            'exercise_id' => $squat->id,
            'recorded_on' => '2026-09-29',
            'weight' => 315,
        ]);
        OneRepMax::query()->create([
            'user_id' => $user->id,
            'exercise_id' => $deadlift->id,
            'recorded_on' => '2026-09-29',
            'weight' => 405,
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('Add your one rep max')
            ->assertDontSee('data-one-rep-max-alert=', false);
    }

    public function test_a_new_account_is_asked_for_the_core_lifts_before_the_library_is_seeded(): void
    {
        $admin = User::factory()->administrator()->create();
        $guest = User::factory()->guest()->create();

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Add your one rep max')
            ->assertSee('Bench, squat, and deadlift are not on record yet.')
            ->assertSee('data-one-rep-max-alert="3"', false);

        $this->actingAs($guest)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-one-rep-max-alert="3"', false);

        $this->assertSame(3, Exercise::query()->whereNull('user_id')->whereIn('name', ['Bench Press', 'Barbell Back Squat', 'Conventional Deadlift'])->count());
    }

    public function test_a_run_shows_on_the_home_week_and_in_todays_plan(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30 09:00:00'));
        $user = User::factory()->guest()->create();

        $run = Run::query()->create([
            'user_id' => $user->id,
            'performed_on' => '2026-09-30',
            'type' => 'outdoor',
            'distance_miles' => 3.5,
            'duration_seconds' => 1800,
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-date="2026-09-30" data-completed="false" data-logged="false" data-runs="1"', false)
            ->assertSee('Outdoor Run · 3.5 mi')
            ->assertSee('View run')
            ->assertSee(route('runs', ['run' => $run->id]), false)
            ->assertDontSee(route('log', ['date' => '2026-09-30']), false)
            ->assertSee('Sign out');
    }

    public function test_a_day_with_a_workout_and_a_run_links_to_both(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30 09:00:00'));
        $user = User::factory()->guest()->create();

        Workout::factory()->create([
            'user_id' => $user->id,
            'performed_on' => '2026-09-30',
            'type' => 'Strength - Upper Body',
            'completed_at' => now(),
        ]);
        $run = Run::query()->create([
            'user_id' => $user->id,
            'performed_on' => '2026-09-30',
            'type' => 'outdoor',
            'distance_miles' => 3.5,
            'duration_seconds' => 1800,
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('log', ['date' => '2026-09-30']), false)
            ->assertSee(route('runs', ['run' => $run->id]), false)
            ->assertSee('Open workout')
            ->assertSee('Open run')
            ->assertSee('Open workout for Sep 30', false)
            ->assertSee('Open run for Sep 30', false);
    }

    public function test_an_empty_session_does_not_keep_a_finished_day_open(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30 09:00:00'));
        $user = User::factory()->guest()->create();

        Workout::factory()->create([
            'user_id' => $user->id,
            'performed_on' => '2026-09-30',
            'completed_at' => now(),
        ]);
        Workout::factory()->incomplete()->create([
            'user_id' => $user->id,
            'performed_on' => '2026-09-30',
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-date="2026-09-30" data-completed="true"', false)
            ->assertSee('View Workout');
    }

    public function test_administrators_see_pending_exercises_on_the_home_page(): void
    {
        $admin = User::factory()->administrator()->create();
        $guest = User::factory()->guest()->create();

        Exercise::query()->create([
            'user_id' => $guest->id,
            'name' => 'Battle Ropes',
        ]);

        $this->actingAs($guest)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('needs approval')
            ->assertDontSee('Battle Ropes');

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('1 exercise needs approval')
            ->assertSee('Battle Ropes')
            ->assertSee(url('/admin/exercises'), false)
            ->assertSee('Dashboard')
            ->assertDontSee('>Users<', false);
    }

    public function test_administrators_do_not_see_an_approval_prompt_when_nothing_is_pending(): void
    {
        $admin = User::factory()->administrator()->create();

        Exercise::query()->create([
            'user_id' => null,
            'name' => 'Bench Press',
            'approved_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('needs approval')
            ->assertDontSee('Bench Press');
    }

    public function test_log_and_progress_are_available_from_the_menu(): void
    {
        $user = User::factory()->guest()->create();

        $this->actingAs($user)
            ->get(route('log'))
            ->assertOk()
            ->assertSee('Type of workout')
            ->assertSee('Add Exercise');

        $this->actingAs($user)
            ->get(route('progress'))
            ->assertOk()
            ->assertSee('Log a workout to see progress.');

        $this->actingAs($user)
            ->get(route('one-rep-max'))
            ->assertOk()
            ->assertSee('One Rep Max');

        $this->actingAs($user)
            ->get(route('runs'))
            ->assertOk()
            ->assertSee('Track your runs. Keep building.')
            ->assertSee('Log Run');
    }
}
