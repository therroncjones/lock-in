<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['run_id', 'position', 'distance_miles', 'duration_seconds', 'elevation_feet'])]
class RunSplit extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'distance_miles' => 'decimal:2',
            'duration_seconds' => 'integer',
            'elevation_feet' => 'integer',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }

    public function distanceLabel(): string
    {
        return Run::formatMiles((float) $this->distance_miles, true);
    }

    public function paceLabel(): string
    {
        return Run::formatPace($this->duration_seconds, (float) $this->distance_miles);
    }

    public function elevationLabel(): string
    {
        if ($this->elevation_feet === null) {
            return '—';
        }

        $feet = (int) $this->elevation_feet;

        return ($feet > 0 ? '+' : '').$feet.' ft';
    }
}
