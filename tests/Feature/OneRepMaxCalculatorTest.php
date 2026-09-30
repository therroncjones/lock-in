<?php

namespace Tests\Feature;

use App\Livewire\OneRepMaxCalculator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OneRepMaxCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_open_the_calculator(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Enter a one rep max to see the loads.')
            ->assertSee('One rep max weight', false)
            ->assertSee('One Rep Max Calculator')
            ->assertSee('More than a calculator.')
            ->assertSee('Sign Up')
            ->assertSee('Log your workouts')
            ->assertSee('See real progress')
            ->assertSee('Stay consistent')
            ->assertSee(route('filament.admin.auth.register'), false)
            ->assertSee('property="og:image"', false)
            ->assertSee('/share.jpg', false)
            ->assertDontSee('Add a known max');

        $this->get(route('filament.admin.auth.login'))
            ->assertOk()
            ->assertDontSee('One rep max calculator');

        $this->get(route('filament.admin.auth.register'))
            ->assertOk()
            ->assertDontSee('One rep max calculator');
    }

    public function test_the_calculator_uses_the_entered_weight(): void
    {
        Livewire::test(OneRepMaxCalculator::class)
            ->assertDontSee('20%')
            ->set('max', '225')
            ->assertSee('20%')
            ->assertSee('100%')
            ->assertSee('157.5')
            ->assertSee('Plate Calculator')
            ->assertSee('45 lb')
            ->assertSee('× 1')
            ->set('barWeight', '0')
            ->assertDontSee('Plate Calculator')
            ->set('barWeight', '44')
            ->set('customPercent', '72.5')
            ->assertSee('72.5%')
            ->assertSee('163.13');
    }

    public function test_a_signed_in_user_sees_the_app_on_home(): void
    {
        $user = User::factory()->guest()->create(['name' => 'Therron Jones']);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Put the work on record')
            ->assertSee('Home')
            ->assertDontSee('Enter a one rep max to see the loads.');
    }
}
