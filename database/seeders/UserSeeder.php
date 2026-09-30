<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@example.com';

    public const GUEST_EMAIL = 'guest@example.com';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $this->user('Admin', self::ADMIN_EMAIL, Role::Administrator);
        $this->user('Guest', self::GUEST_EMAIL, Role::Guest);
    }

    private function user(string $name, string $email, string $role): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
            ],
        );

        $user->roles()->syncWithoutDetaching([
            Role::query()->firstOrCreate(['name' => $role])->id,
        ]);

        return $user;
    }
}
