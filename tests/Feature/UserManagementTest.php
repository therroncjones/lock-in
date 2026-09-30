<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_open_the_dashboard_but_not_manage_users(): void
    {
        $guest = User::factory()->guest()->create();

        $this->actingAs($guest);

        Livewire::test(Dashboard::class)
            ->assertSuccessful();

        Livewire::test(ListUsers::class)
            ->assertForbidden();
    }

    public function test_administrators_can_manage_users_and_roles(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin);

        $component = Livewire::test(CreateUser::class)
            ->assertSuccessful()
            ->fillForm([
                'name' => 'Pat Guest',
                'email' => 'pat@example.com',
                'password' => 'password',
                'roles' => [Role::query()->where('name', Role::Guest)->value('id')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'pat@example.com')->first();

        $this->assertNotNull($user);
        $this->assertFalse($user->isAdministrator());
        $this->assertTrue($user->hasRole(Role::Guest));

        $component->assertRedirect();

        Livewire::test(ListUsers::class)
            ->assertSuccessful()
            ->assertSee('Pat Guest')
            ->assertSee($admin->name);

        Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()])
            ->assertSuccessful()
            ->assertSee('Pat Guest');
    }
}
