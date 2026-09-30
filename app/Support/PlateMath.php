<?php

namespace App\Support;

class PlateMath
{
    /**
     * @var list<float>
     */
    private const Plates = [45.0, 35.0, 25.0, 15.0, 10.0, 5.0, 2.5];

    public static function target(float $max, float $percent): float
    {
        if ($max <= 0 || $percent <= 0) {
            return 0.0;
        }

        return round($max * ($percent / 100), 2);
    }

    public static function loaded(float $target, int $bar): float
    {
        if ($bar <= 0 || $target <= 0) {
            return max(0.0, $target);
        }

        if ($target <= $bar) {
            return (float) $bar;
        }

        $loadable = ceil(($target - $bar) / 5) * 5;

        return $bar + $loadable;
    }

    /**
     * @return array<string, int>
     */
    public static function platesPerSide(float $target, int $bar): array
    {
        if ($bar <= 0 || $target <= 0) {
            return [];
        }

        $remaining = (self::loaded($target, $bar) - $bar) / 2;

        if ($remaining <= 0) {
            return [];
        }

        $breakdown = [];

        foreach (self::Plates as $plate) {
            $count = (int) floor(($remaining + 1e-9) / $plate);

            if ($count < 1) {
                continue;
            }

            $label = fmod($plate, 1.0) === 0.0 ? (string) (int) $plate : (string) $plate;
            $breakdown[$label.' lb'] = $count;
            $remaining -= $plate * $count;
        }

        return $breakdown;
    }
}
