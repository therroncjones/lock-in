<?php

namespace App\Support;

class SetMeasure
{
    public const Reps = 'reps';

    public const Time = 'time';

    public const Calories = 'calories';

    public const Meters = 'meters';

    /**
     * @var array<string, string>
     */
    public const Labels = [
        self::Reps => 'Reps',
        self::Time => 'Time',
        self::Calories => 'Calories',
        self::Meters => 'Meters',
    ];

    /**
     * Library exercises scored by duration. Everything else stays on reps
     * until an administrator or the person who added it chooses otherwise.
     *
     * @var list<string>
     */
    private const TimedExercises = [
        'Bar Hang',
        'Copenhagen Plank',
        'Cycling',
        'Farmer Carry',
        'Front Hold',
        'Hollow Hold',
        'Jump Rope',
        'Kneeling Plank',
        'Kneeling Side Plank',
        'One-Handed Bar Hang',
        'Plank',
        'Rowing',
        'Side Plank',
        'Weighted Plank',
    ];

    public static function normalize(?string $measure): string
    {
        return array_key_exists($measure ?? '', self::Labels) ? $measure : self::Reps;
    }

    public static function forExerciseName(string $name): string
    {
        return in_array($name, self::TimedExercises, true) ? self::Time : self::Reps;
    }

    public static function tracksWeight(?string $measure): bool
    {
        return self::normalize($measure) === self::Reps;
    }

    public static function label(?string $measure): string
    {
        return self::Labels[self::normalize($measure)];
    }

    public static function present(?string $measure, mixed $amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        $amount = (int) $amount;

        return match (self::normalize($measure)) {
            self::Time => self::formatDuration($amount),
            default => (string) $amount,
        };
    }

    /**
     * @return array{value: int|null, error: string|null}
     */
    public static function parse(?string $measure, string $input): array
    {
        $input = trim($input);
        $measure = self::normalize($measure);

        if ($input === '') {
            return ['value' => null, 'error' => null];
        }

        if ($measure === self::Time) {
            $seconds = self::parseDuration($input);

            if ($seconds === null) {
                return ['value' => null, 'error' => 'Enter a time like 1:30.'];
            }

            if ($seconds > 65535) {
                return ['value' => null, 'error' => 'That time is too long for one set.'];
            }

            return ['value' => $seconds, 'error' => null];
        }

        if (! preg_match('/^\d+$/', $input)) {
            return ['value' => null, 'error' => match ($measure) {
                self::Calories => 'Enter calories as a whole number.',
                self::Meters => 'Enter meters as a whole number.',
                default => 'Enter reps as a whole number.',
            }];
        }

        $amount = (int) $input;
        $max = match ($measure) {
            self::Calories => 20000,
            self::Meters => 65535,
            default => 500,
        };

        if ($amount > $max) {
            return ['value' => null, 'error' => match ($measure) {
                self::Calories => 'Enter 20,000 calories or fewer.',
                self::Meters => 'Enter 65,535 meters or fewer.',
                default => 'Enter 500 reps or fewer.',
            }];
        }

        return ['value' => $amount, 'error' => null];
    }

    public static function describe(?string $measure, mixed $amount, mixed $weight = null): ?string
    {
        $measure = self::normalize($measure);
        $hasAmount = $amount !== null && $amount !== '';
        $hasWeight = $weight !== null && $weight !== '' && self::tracksWeight($measure);

        if (! $hasAmount && ! $hasWeight) {
            return null;
        }

        if ($measure === self::Time) {
            return self::formatDuration((int) $amount);
        }

        if ($measure === self::Calories) {
            $count = (int) $amount;

            return $count.' '.($count === 1 ? 'calorie' : 'calories');
        }

        if ($measure === self::Meters) {
            return ((int) $amount).' m';
        }

        $count = $hasAmount ? (int) $amount : null;

        if ($hasWeight && $count !== null) {
            return self::formatWeight($weight).' × '.$count;
        }

        if ($hasWeight) {
            return self::formatWeight($weight).' lbs';
        }

        return $count.' '.($count === 1 ? 'rep' : 'reps');
    }

    public static function signedDelta(?string $measure, int $delta): string
    {
        $measure = self::normalize($measure);
        $sign = $delta > 0 ? '+' : ($delta < 0 ? '-' : '');

        if ($measure === self::Time) {
            return $sign.self::formatDuration(abs($delta));
        }

        $unit = match ($measure) {
            self::Calories => abs($delta) === 1 ? 'calorie' : 'calories',
            self::Meters => 'm',
            default => abs($delta) === 1 ? 'rep' : 'reps',
        };

        return $sign.abs($delta).' '.$unit;
    }

    public static function formatDuration(int $seconds): string
    {
        $seconds = abs($seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remain = $seconds % 60;

        if ($hours > 0) {
            return $hours.':'.str_pad((string) $minutes, 2, '0', STR_PAD_LEFT).':'.str_pad((string) $remain, 2, '0', STR_PAD_LEFT);
        }

        return $minutes.':'.str_pad((string) $remain, 2, '0', STR_PAD_LEFT);
    }

    private static function parseDuration(string $input): ?int
    {
        if (preg_match('/^\d+$/', $input)) {
            return (int) $input;
        }

        if (preg_match('/^(\d+):([0-5]\d)$/', $input, $matches)) {
            return ((int) $matches[1] * 60) + (int) $matches[2];
        }

        if (preg_match('/^(\d+):([0-5]\d):([0-5]\d)$/', $input, $matches)) {
            return ((int) $matches[1] * 3600) + ((int) $matches[2] * 60) + (int) $matches[3];
        }

        return null;
    }

    private static function formatWeight(mixed $weight): string
    {
        $number = (float) $weight;

        if (fmod($number, 1.0) === 0.0) {
            return (string) (int) $number;
        }

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }
}
