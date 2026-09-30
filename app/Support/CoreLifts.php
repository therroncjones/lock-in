<?php

namespace App\Support;

use App\Models\Exercise;
use App\Models\OneRepMax;
use App\Models\User;
use Illuminate\Support\Collection;

class CoreLifts
{
    /**
     * @var list<string>
     */
    public const Names = [
        'Barbell Back Squat',
        'Bench Press',
        'Conventional Deadlift',
    ];

    /**
     * @var array<string, string>
     */
    private const Labels = [
        'Bench Press' => 'bench',
        'Barbell Back Squat' => 'squat',
        'Conventional Deadlift' => 'deadlift',
    ];

    public static function missingCount(User $user): int
    {
        return self::missing($user)->count();
    }

    public static function missingSentence(User $user): ?string
    {
        $labels = self::missing($user)
            ->map(fn (Exercise $exercise): string => self::Labels[$exercise->name])
            ->values();

        if ($labels->isEmpty()) {
            return null;
        }

        if ($labels->count() === 1) {
            return ucfirst($labels->first()).' is not on record yet.';
        }

        if ($labels->count() === 2) {
            return ucfirst($labels->first()).' and '.$labels->last().' are not on record yet.';
        }

        return ucfirst($labels[0]).', '.$labels[1].', and '.$labels[2].' are not on record yet.';
    }

    /**
     * @return Collection<int, Exercise>
     */
    private static function missing(User $user): Collection
    {
        $exercises = Exercise::query()
            ->whereNull('user_id')
            ->whereIn('name', array_keys(self::Labels))
            ->get()
            ->sortBy(fn (Exercise $exercise): int => array_search($exercise->name, array_keys(self::Labels), true))
            ->values();

        $recorded = OneRepMax::query()
            ->whereBelongsTo($user)
            ->whereIn('exercise_id', $exercises->pluck('id'))
            ->pluck('exercise_id')
            ->unique();

        return $exercises
            ->reject(fn (Exercise $exercise): bool => $recorded->contains($exercise->id))
            ->values();
    }
}
