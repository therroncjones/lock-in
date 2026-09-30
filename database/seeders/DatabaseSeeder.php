<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->call([
                RoleSeeder::class,
                ExerciseSeeder::class,
            ]);

            return;
        }

        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            ExerciseSeeder::class,
            TrainingSeeder::class,
        ]);
    }
}
