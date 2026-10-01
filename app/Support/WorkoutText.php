<?php

namespace App\Support;

use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Illuminate\Support\Collection;

class WorkoutText
{
    public static function from(Workout $workout): string
    {
        $workout->loadMissing(['exercises.exercise', 'exercises.sets', 'blocks']);

        if ($workout->exercises->isEmpty()) {
            return '';
        }

        $date = $workout->performed_on->format('D, M j, Y');
        $type = trim((string) $workout->type);
        $sections = [];

        $loose = $workout->exercises->whereNull('workout_block_id')->sortBy('position')->values();

        if ($loose->isNotEmpty()) {
            $sections[] = self::section(null, $loose);
        }

        foreach ($workout->blocks as $block) {
            $entries = $workout->exercises
                ->where('workout_block_id', $block->id)
                ->sortBy('position')
                ->values();

            if ($entries->isEmpty()) {
                continue;
            }

            $sections[] = self::section($block->name, $entries);
        }

        $heading = $date."\n".($type !== '' ? $type : 'Workout');

        return $heading."\n\n".implode("\n\n", $sections);
    }

    /**
     * @param  Collection<int, WorkoutExercise>  $entries
     */
    private static function section(?string $name, Collection $entries): string
    {
        $exercises = [];

        foreach ($entries as $entry) {
            $exercises[] = implode("\n", self::exerciseLines($entry));
        }

        $body = implode("\n\n", $exercises);

        if ($name === null || $name === '') {
            return $body;
        }

        return $name."\n".$body;
    }

    /**
     * @return list<string>
     */
    private static function exerciseLines(WorkoutExercise $entry): array
    {
        $measure = SetMeasure::normalize($entry->exercise->measure);
        $sets = $entry->sets
            ->filter(fn (WorkoutSet $set): bool => self::hasData($set, $measure))
            ->values();
        $lines = [$entry->exercise->name];

        if ($sets->isEmpty()) {
            return $lines;
        }

        $collapsed = self::collapsed($sets, $measure);

        if ($collapsed !== null) {
            array_push($lines, ...$collapsed);

            return $lines;
        }

        foreach ($sets as $set) {
            $line = self::setLine($set, $measure);

            if ($line === '') {
                continue;
            }

            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * @param  Collection<int, WorkoutSet>  $sets
     * @return list<string>|null
     */
    private static function collapsed(Collection $sets, string $measure): ?array
    {
        $count = $sets->count();
        $amounts = $sets->map(fn (WorkoutSet $set): ?int => self::amount($set, $measure))->all();
        $weights = $sets->map(fn (WorkoutSet $set): ?string => self::planWeight($set, $measure))->all();
        $sameAmount = count(array_unique($amounts, SORT_REGULAR)) === 1;
        $sameWeight = count(array_unique($weights, SORT_REGULAR)) === 1;

        if ($sameAmount && $amounts[0] !== null && $count > 1) {
            return [self::scheme($count, $amounts[0], null, $measure)];
        }

        if ($sameAmount && $sameWeight) {
            return [self::scheme($count, $amounts[0], $weights[0], $measure)];
        }

        if ($sameWeight && ! in_array(null, $amounts, true)) {
            $pieces = array_map(fn (int $amount): string => self::amountLabel($amount, $measure), $amounts);

            return [self::scheme($count, null, $weights[0], $measure, implode('/', $pieces))];
        }

        return null;
    }

    private static function scheme(int $count, ?int $amount, ?string $weight, string $measure, ?string $amounts = null): string
    {
        $body = $amounts ?? ($amount === null ? '' : self::amountLabel($amount, $measure));
        $line = $count.'x'.$body;

        if ($weight !== null) {
            $line .= ' @ '.$weight;
        }

        return $line;
    }

    private static function setLine(WorkoutSet $set, string $measure): string
    {
        $amount = self::amount($set, $measure);
        $weight = self::planWeight($set, $measure);

        if (! SetMeasure::tracksWeight($measure)) {
            return $amount === null ? '' : self::amountLabel($amount, $measure);
        }

        if ($weight !== null && $amount !== null) {
            return $weight.'x'.$amount;
        }

        if ($amount !== null) {
            return $amount.' '.($amount === 1 ? 'rep' : 'reps');
        }

        return $weight.' lbs';
    }

    private static function hasData(WorkoutSet $set, string $measure): bool
    {
        if (SetMeasure::tracksWeight($measure)) {
            return self::filled($set->weight) || $set->reps !== null || self::filled($set->actual_weight) || $set->actual_reps !== null;
        }

        return $set->reps !== null || $set->actual_reps !== null;
    }

    private static function amount(WorkoutSet $set, string $measure): ?int
    {
        if (SetMeasure::tracksWeight($measure)) {
            return $set->reps === null ? null : (int) $set->reps;
        }

        return $set->reps === null ? null : (int) $set->reps;
    }

    private static function planWeight(WorkoutSet $set, string $measure): ?string
    {
        if (! SetMeasure::tracksWeight($measure) || ! self::filled($set->weight)) {
            return null;
        }

        return self::number($set->weight);
    }

    private static function amountLabel(int $amount, string $measure): string
    {
        if ($measure === SetMeasure::Time) {
            return $amount < 60 ? $amount.'s' : SetMeasure::formatDuration($amount);
        }

        if ($measure === SetMeasure::Calories) {
            return $amount.' cal';
        }

        if ($measure === SetMeasure::Meters) {
            return $amount.' m';
        }

        return (string) $amount;
    }

    private static function filled(mixed $weight): bool
    {
        return $weight !== null && $weight !== '';
    }

    private static function number(mixed $weight): string
    {
        $number = (float) $weight;

        if (fmod($number, 1.0) === 0.0) {
            return (string) (int) $number;
        }

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }
}
