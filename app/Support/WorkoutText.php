<?php

namespace App\Support;

use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use App\Support\SetMeasure;

class WorkoutText
{
    public static function from(Workout $workout): string
    {
        $workout->loadMissing(['exercises.exercise', 'exercises.sets', 'blocks']);

        if ($workout->exercises->isEmpty()) {
            return '';
        }

        $type = trim((string) $workout->type);
        $pieces = [($type !== '' ? $type : 'Workout').' · '.$workout->performed_on->format('D, M j')];

        foreach ($workout->exercises->whereNull('workout_block_id')->sortBy('position') as $entry) {
            $pieces[] = self::exercise($entry);
        }

        foreach ($workout->blocks as $block) {
            $entries = $workout->exercises
                ->where('workout_block_id', $block->id)
                ->sortBy('position')
                ->values();

            if ($entries->isEmpty()) {
                continue;
            }

            foreach ($entries as $index => $entry) {
                $text = self::exercise($entry);
                $pieces[] = $index === 0 ? $block->name."\n".$text : $text;
            }
        }

        return implode("\n\n", $pieces);
    }

    private static function exercise(WorkoutExercise $entry): string
    {
        $lines = [$entry->exercise->name];

        foreach ($entry->sets as $set) {
            $line = self::setLine($set, $entry->exercise->measure);

            if ($line !== null) {
                $lines[] = $line;
            }
        }

        return implode("\n", $lines);
    }

    private static function setLine(WorkoutSet $set, ?string $measure): ?string
    {
        if (! SetMeasure::tracksWeight($measure)) {
            $plan = SetMeasure::describe($measure, $set->reps);
            $actual = SetMeasure::describe($measure, $set->actual_reps);
        } else {
            $plan = self::pair($set->weight, $set->reps);
            $actual = self::pair($set->actual_weight, $set->actual_reps);
        }

        if ($plan === null && $actual === null) {
            return null;
        }

        if ($plan === null) {
            return 'did '.$actual;
        }

        if ($actual === null) {
            return $plan;
        }

        return $plan.' · did '.$actual;
    }

    private static function pair(mixed $weight, mixed $reps): ?string
    {
        $hasWeight = $weight !== null && $weight !== '';
        $hasReps = $reps !== null && $reps !== '';

        if (! $hasWeight && ! $hasReps) {
            return null;
        }

        if ($hasWeight && $hasReps) {
            return self::weight($weight).' × '.(int) $reps;
        }

        if ($hasWeight) {
            return self::weight($weight).' lbs';
        }

        $count = (int) $reps;

        return $count.' '.($count === 1 ? 'rep' : 'reps');
    }

    private static function weight(mixed $weight): string
    {
        $number = (float) $weight;

        if (fmod($number, 1.0) === 0.0) {
            return (string) (int) $number;
        }

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }
}
