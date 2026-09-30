<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'performed_on',
    'started_at',
    'type',
    'distance_miles',
    'duration_seconds',
    'elevation_gain_feet',
    'calories',
    'average_heart_rate',
    'max_heart_rate',
    'temperature_fahrenheit',
    'notes',
])]
class Run extends Model
{
    /**
     * @var array<string, string>
     */
    public const Types = [
        'outdoor' => 'Outdoor Run',
        'treadmill' => 'Treadmill Run',
        'walk' => 'Walk',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'performed_on' => 'date',
            'distance_miles' => 'decimal:2',
            'duration_seconds' => 'integer',
            'elevation_gain_feet' => 'integer',
            'calories' => 'integer',
            'average_heart_rate' => 'integer',
            'max_heart_rate' => 'integer',
            'temperature_fahrenheit' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function splits(): HasMany
    {
        return $this->hasMany(RunSplit::class)->orderBy('position');
    }

    public function typeLabel(): string
    {
        return self::Types[$this->type] ?? $this->type;
    }

    public function distanceLabel(): string
    {
        return self::formatMiles((float) $this->distance_miles);
    }

    public function durationLabel(): string
    {
        return self::formatDuration((int) $this->duration_seconds);
    }

    public function paceLabel(): string
    {
        return self::formatPace((int) $this->duration_seconds, (float) $this->distance_miles);
    }

    public function startedAtLabel(): ?string
    {
        if ($this->started_at === null || $this->started_at === '') {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($this->started_at)->format('g:i A');
    }

    /**
     * @return list<array{distance: string, pace: string, time: string, elevation: string, bar: int}>
     */
    public function splitRows(): array
    {
        $elapsed = 0;
        $rows = [];

        foreach ($this->splits as $split) {
            $elapsed += (int) $split->duration_seconds;
            $paceSeconds = (float) $split->distance_miles > 0
                ? (int) round($split->duration_seconds / (float) $split->distance_miles)
                : null;

            $rows[] = [
                'distance' => $split->distanceLabel(),
                'pace' => $split->paceLabel(),
                'time' => self::formatDuration($elapsed),
                'elevation' => $split->elevationLabel(),
                'paceSeconds' => $paceSeconds,
            ];
        }

        $paces = collect($rows)->pluck('paceSeconds')->filter(fn (?int $pace): bool => $pace !== null);
        $fastest = $paces->min();
        $slowest = $paces->max();

        foreach ($rows as $index => $row) {
            $bar = 100;

            if ($row['paceSeconds'] !== null && $fastest !== null && $slowest !== null && $slowest > $fastest) {
                $bar = (int) round(100 - (($row['paceSeconds'] - $fastest) / ($slowest - $fastest)) * 45);
            }

            $rows[$index]['bar'] = $bar;
            unset($rows[$index]['paceSeconds']);
        }

        return $rows;
    }

    public static function formatMiles(float $miles, bool $trim = false): string
    {
        $formatted = number_format($miles, 2, '.', '');

        if (! $trim) {
            return $formatted;
        }

        return rtrim(rtrim($formatted, '0'), '.');
    }

    public static function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remain = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $remain);
        }

        return sprintf('%d:%02d', $minutes, $remain);
    }

    public static function formatPace(int $seconds, float $miles): string
    {
        if ($seconds <= 0 || $miles <= 0) {
            return '—';
        }

        $perMile = (int) round($seconds / $miles);

        return intdiv($perMile, 60).':'.str_pad((string) ($perMile % 60), 2, '0', STR_PAD_LEFT);
    }
}
