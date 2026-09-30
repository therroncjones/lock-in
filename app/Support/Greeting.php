<?php

namespace App\Support;

class Greeting
{
    public static function forHour(int $hour): string
    {
        return match (true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
    }
}
