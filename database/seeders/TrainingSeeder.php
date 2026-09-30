<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\OneRepMax;
use App\Models\Run;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutBlock;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TrainingSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $this->call(ExerciseSeeder::class);

        $user = User::query()->where('email', UserSeeder::GUEST_EMAIL)->first();

        if ($user === null) {
            return;
        }

        $exercises = Exercise::query()->whereNull('user_id')->pluck('id', 'name');

        DB::transaction(function () use ($user, $exercises): void {
            $this->seedOneRepMaxes($user, $exercises);
            $this->seedCalendar($user, $exercises);
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<string, int>  $exercises
     */
    private function seedOneRepMaxes(User $user, $exercises): void
    {
        $history = [
            'Barbell Back Squat' => [365, 375, 385, 395, 410, 420, 435, 445, 455],
            'Bench Press' => [165, 170, 175, 180, 185, 190, 195, 200, 200],
            'Conventional Deadlift' => [315, 335, 345, 365, 375, 385, 395, 410, 415],
        ];

        foreach ($history as $name => $weights) {
            foreach ($weights as $index => $weight) {
                OneRepMax::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'exercise_id' => $exercises[$name],
                        'recorded_on' => Carbon::create(2026, $index + 1, 1)->toDateString(),
                    ],
                    ['weight' => $weight],
                );
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<string, int>  $exercises
     */
    private function seedCalendar(User $user, $exercises): void
    {
        $cursor = Carbon::parse('2026-01-05');
        $end = Carbon::parse('2026-09-28');
        $week = 0;

        while ($cursor->lte($end)) {
            $this->seedSession($user, $exercises, $cursor->copy(), 'Strength - Lower Body', $this->lowerBlocks($week));

            $tuesday = $cursor->copy()->addDay();
            if ($tuesday->lte($end)) {
                $this->seedRun($user, $tuesday, $week, 'easy');
            }

            $wednesday = $cursor->copy()->addDays(2);
            if ($wednesday->lte($end)) {
                $this->seedSession($user, $exercises, $wednesday, 'Strength - Upper Body', $this->upperBlocks($week));
            }

            $thursday = $cursor->copy()->addDays(3);
            if ($thursday->lte($end) && $week % 2 === 0) {
                $this->seedSession($user, $exercises, $thursday, 'Bodyweight', $this->bodyweightBlocks());
            } elseif ($thursday->lte($end)) {
                $this->seedSession($user, $exercises, $thursday, 'Cardio', $this->cardioBlocks());
            }

            $friday = $cursor->copy()->addDays(4);
            if ($friday->lte($end)) {
                $this->seedSession($user, $exercises, $friday, 'Strength - Upper Body', $this->pullBlocks($week));
            }

            $saturday = $cursor->copy()->addDays(5);
            if ($saturday->lte($end)) {
                $this->seedRun($user, $saturday, $week, $week % 5 === 4 ? 'walk' : 'long');
            }

            $sunday = $cursor->copy()->addDays(6);
            if ($sunday->lte($end) && $week % 4 === 0) {
                $this->seedSession($user, $exercises, $sunday, 'Mobility', $this->mobilityBlocks());
            }

            $cursor->addWeek();
            $week++;
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<string, int>  $exercises
     * @param  array<string, list<array{name: string, sets: list<array{0: ?int, 1: int}>, actual?: array{0: int, 1: int}}>>  $blocks
     */
    private function seedSession(User $user, $exercises, Carbon $date, string $type, array $blocks): void
    {
        if (Workout::query()->where('user_id', $user->id)->whereDate('performed_on', $date)->exists()) {
            return;
        }

        $workout = Workout::query()->create([
            'user_id' => $user->id,
            'performed_on' => $date->toDateString(),
            'type' => $type,
            'completed_at' => $date->copy()->setTime(18, 15),
        ]);

        $position = 1;
        $blockPosition = 1;

        foreach ($blocks as $name => $movements) {
            $block = WorkoutBlock::query()->create([
                'workout_id' => $workout->id,
                'name' => $name,
                'position' => $blockPosition,
            ]);
            $blockPosition++;

            foreach ($movements as $movement) {
                $entry = WorkoutExercise::query()->create([
                    'workout_id' => $workout->id,
                    'workout_block_id' => $block->id,
                    'exercise_id' => $exercises[$movement['name']],
                    'position' => $position,
                    'actual_weight' => $movement['actual'][0] ?? null,
                    'actual_reps' => $movement['actual'][1] ?? null,
                ]);

                foreach ($movement['sets'] as $index => $set) {
                    WorkoutSet::query()->create([
                        'workout_exercise_id' => $entry->id,
                        'position' => $index + 1,
                        'weight' => $set[0],
                        'reps' => $set[1],
                        'completed' => true,
                    ]);
                }

                $position++;
            }
        }
    }

    /**
     * @return array<string, list<array{name: string, sets: list<array{0: ?int, 1: int}>}>>
     */
    private function lowerBlocks(int $week): array
    {
        $squat = $this->load($week, 225, 365);
        $front = $this->load($week, 135, 225);
        $rdl = $this->load($week, 155, 245);

        return [
            'Block A' => [
                ['name' => 'Barbell Back Squat', 'sets' => [[$squat, 5], [$squat, 5], [$squat, 5], [$squat, 3]]],
                ['name' => 'Front Squat', 'sets' => [[$front, 5], [$front, 5], [$front, 5]]],
            ],
            'Block B' => [
                ['name' => 'Romanian Deadlift', 'sets' => [[$rdl, 8], [$rdl, 8], [$rdl, 8]]],
                ['name' => 'Walking Lunge', 'sets' => [[null, 12], [null, 12], [null, 10]]],
            ],
        ];
    }

    /**
     * @return array<string, list<array{name: string, sets: list<array{0: ?int, 1: int}>, actual?: array{0: int, 1: int}}>>
     */
    private function upperBlocks(int $week): array
    {
        $bench = $this->load($week, 135, 185);
        $row = $this->load($week, 115, 165);
        $press = $this->load($week, 75, 115);
        $pulldown = $this->load($week, 90, 140);
        $movement = [
            'name' => 'Bench Press',
            'sets' => [[$bench, 5], [$bench, 5], [$bench, 5], [$bench - 10, 8]],
        ];

        if ($week % 2 === 0) {
            $movement['actual'] = [$bench - 5, 5];
        }

        return [
            'Block A' => [
                $movement,
                ['name' => 'Barbell Row', 'sets' => [[$row, 8], [$row, 8], [$row, 6]]],
            ],
            'Block B' => [
                ['name' => 'Overhead Press', 'sets' => [[$press, 5], [$press, 5], [$press, 5]]],
                ['name' => 'Lat Pulldown', 'sets' => [[$pulldown, 10], [$pulldown, 10], [$pulldown, 8]]],
            ],
        ];
    }

    /**
     * @return array<string, list<array{name: string, sets: list<array{0: ?int, 1: int}>}>>
     */
    private function pullBlocks(int $week): array
    {
        $deadlift = $this->load($week, 245, 385);
        $thrust = $this->load($week, 185, 315);

        return [
            'Block A' => [
                ['name' => 'Conventional Deadlift', 'sets' => [[$deadlift, 5], [$deadlift, 3], [$deadlift, 2]]],
                ['name' => 'Hip Thrust', 'sets' => [[$thrust, 8], [$thrust, 8], [$thrust, 6]]],
            ],
            'Block B' => [
                ['name' => 'Pull-Up', 'sets' => [[null, 8], [null, 6], [null, 5]]],
            ],
        ];
    }

    /**
     * @return array<string, list<array{name: string, sets: list<array{0: ?int, 1: int}>}>>
     */
    private function bodyweightBlocks(): array
    {
        return [
            'Block A' => [
                ['name' => 'Push-Up', 'sets' => [[null, 15], [null, 12], [null, 10]]],
                ['name' => 'Dip', 'sets' => [[null, 10], [null, 8], [null, 8]]],
                ['name' => 'Hanging Leg Raise', 'sets' => [[null, 12], [null, 10], [null, 8]]],
            ],
        ];
    }

    /**
     * @return array<string, list<array{name: string, sets: list<array{0: ?int, 1: int}>}>>
     */
    private function cardioBlocks(): array
    {
        return [
            'Block A' => [
                ['name' => 'Rowing', 'sets' => [[null, 20], [null, 15], [null, 12]]],
                ['name' => 'Jump Rope', 'sets' => [[null, 50], [null, 40]]],
            ],
        ];
    }

    /**
     * @return array<string, list<array{name: string, sets: list<array{0: ?int, 1: int}>}>>
     */
    private function mobilityBlocks(): array
    {
        return [
            'Block A' => [
                ['name' => 'Dead Bug', 'sets' => [[null, 10], [null, 10], [null, 8]]],
                ['name' => 'Plank', 'sets' => [[null, 45], [null, 30]]],
            ],
        ];
    }

    private function seedRun(User $user, Carbon $date, int $week, string $kind): void
    {
        if (Run::query()->where('user_id', $user->id)->whereDate('performed_on', $date)->exists()) {
            return;
        }

        $type = match ($kind) {
            'walk' => 'walk',
            'easy' => $week % 4 === 3 ? 'treadmill' : 'outdoor',
            default => 'outdoor',
        };
        $distance = match ($kind) {
            'walk' => 2.0,
            'easy' => 3 + (($week % 4) * 0.25),
            default => 5 + (min($week, 32) * 0.08),
        };
        $pace = match ($kind) {
            'walk' => 17 * 60 + 30,
            'easy' => (10 * 60 + 40) - min($week, 30) * 2,
            default => (10 * 60 + 55) - min($week, 30),
        };
        $distance = round($distance, 2);
        $notes = [
            'Easy effort. Felt smooth.',
            'Legs were heavy from lifting.',
            'Negative split. Felt strong.',
            'Cool morning, kept it comfortable.',
            'Last mile opened up.',
        ];
        $temperatures = [1 => 36, 2 => 42, 3 => 52, 4 => 61, 5 => 70, 6 => 78, 7 => 84, 8 => 82, 9 => 68];
        $averageHeartRate = $kind === 'walk' ? 112 : 152 + ($week % 8);
        $fullMiles = (int) floor($distance);
        $remainder = round($distance - $fullMiles, 2);
        $splits = [];

        for ($mile = 1; $mile <= $fullMiles; $mile++) {
            $splits[] = [
                'distance' => 1,
                'pace' => $pace + ((($mile + $week) % 3) - 1) * 8,
                'elevation' => $type === 'treadmill' ? 0 : ((($week + $mile) % 5) - 2) * 6,
            ];
        }

        if ($remainder > 0) {
            $splits[] = [
                'distance' => $remainder,
                'pace' => $pace - 6,
                'elevation' => $type === 'treadmill' ? 0 : -4,
            ];
        }

        $duration = 0;
        $gain = 0;

        foreach ($splits as $split) {
            $duration += (int) round($split['distance'] * $split['pace']);
            $gain += max(0, $split['elevation']);
        }

        $run = Run::query()->create([
            'user_id' => $user->id,
            'performed_on' => $date->toDateString(),
            'started_at' => $kind === 'long' || $kind === 'walk' ? '07:05:00' : '06:20:00',
            'type' => $type,
            'distance_miles' => $distance,
            'duration_seconds' => $duration,
            'elevation_gain_feet' => $type === 'treadmill' ? null : $gain,
            'calories' => (int) round($distance * ($kind === 'walk' ? 80 : 105)),
            'average_heart_rate' => $averageHeartRate,
            'max_heart_rate' => $averageHeartRate + ($kind === 'walk' ? 18 : 24),
            'temperature_fahrenheit' => $type === 'treadmill' ? 68 : $temperatures[$date->month],
            'notes' => $notes[$week % count($notes)],
        ]);

        foreach ($splits as $index => $split) {
            $run->splits()->create([
                'position' => $index + 1,
                'distance_miles' => $split['distance'],
                'duration_seconds' => (int) round($split['distance'] * $split['pace']),
                'elevation_feet' => $type === 'treadmill' ? null : $split['elevation'],
            ]);
        }
    }

    private function load(int $week, int $start, int $end): int
    {
        $value = $start + (($end - $start) * min($week, 38) / 38);

        return (int) (round($value / 5) * 5);
    }
}
