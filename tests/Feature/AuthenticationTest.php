<?php

namespace Tests\Feature;

use App\Filament\Auth\Register;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrators_and_guests_can_sign_in(): void
    {
        $admin = User::factory()->administrator()->create([
            'email' => 'admin@example.com',
        ]);
        $guest = User::factory()->guest()->create([
            'email' => 'guest@example.com',
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $admin->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($admin);

        auth()->logout();

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $guest->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($guest);
    }

    public function test_signing_out_returns_to_the_public_home(): void
    {
        $user = User::factory()->guest()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Enter a one rep max to see the loads.');
    }

    public function test_users_without_a_role_cannot_sign_in(): void
    {
        $user = User::factory()->create([
            'email' => 'nobody@example.com',
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
            ])
            ->call('authenticate');

        $this->assertGuest();
    }

    public function test_the_first_registered_user_is_an_administrator_and_later_users_are_guests(): void
    {
        Livewire::test(Register::class)
            ->fillForm([
                'name' => 'First Owner',
                'email' => 'first@example.com',
                'password' => 'password',
                'passwordConfirmation' => 'password',
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $first = User::query()->where('email', 'first@example.com')->first();

        $this->assertNotNull($first);
        $this->assertTrue($first->isAdministrator());
        $this->assertFalse($first->isGuest());

        auth()->logout();

        Livewire::test(Register::class)
            ->fillForm([
                'name' => 'Second Person',
                'email' => 'second@example.com',
                'password' => 'password',
                'passwordConfirmation' => 'password',
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $second = User::query()->where('email', 'second@example.com')->first();

        $this->assertNotNull($second);
        $this->assertFalse($second->isAdministrator());
        $this->assertTrue($second->isGuest());
    }

    public function test_the_user_seeder_creates_an_administrator_and_a_guest(): void
    {
        $this->seed(UserSeeder::class);

        $admin = User::query()->where('email', UserSeeder::ADMIN_EMAIL)->first();
        $guest = User::query()->where('email', UserSeeder::GUEST_EMAIL)->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole(Role::Administrator));
        $this->assertNotNull($guest);
        $this->assertTrue($guest->hasRole(Role::Guest));
    }
}
