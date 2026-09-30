<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\OneRepMax;
use App\Models\Role;
use App\Models\Run;
use App\Models\User;
use App\Models\Workout;
use Database\Seeders\TrainingSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_seeding_creates_roles_and_exercises(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--force' => true, '--no-interaction' => true]);

        $this->assertSame(0, User::query()->count());
        $this->assertEqualsCanonicalizing(
            [Role::Administrator, Role::Guest],
            Role::query()->pluck('name')->all(),
        );
        $this->assertSame(0, Workout::query()->count());
        $this->assertSame(0, Run::query()->count());
        $this->assertSame(0, OneRepMax::query()->count());
        $this->assertTrue(
            Exercise::query()->where('name', 'Bench Press')->whereNull('user_id')->whereNotNull('approved_at')->exists()
        );
    }

    public function test_the_user_and_training_seeders_do_nothing_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', [
            '--class' => UserSeeder::class,
            '--force' => true,
            '--no-interaction' => true,
        ]);
        $this->artisan('db:seed', [
            '--class' => TrainingSeeder::class,
            '--force' => true,
            '--no-interaction' => true,
        ]);

        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, Exercise::query()->count());
        $this->assertSame(0, Workout::query()->count());
    }
}
