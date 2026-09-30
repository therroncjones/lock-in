<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workout_id', 'workout_block_id', 'exercise_id', 'position', 'actual_weight', 'actual_reps'])]
class WorkoutExercise extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actual_weight' => 'decimal:2',
            'actual_reps' => 'integer',
        ];
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(WorkoutBlock::class, 'workout_block_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function sets(): HasMany
    {
        return $this->hasMany(WorkoutSet::class)->orderBy('position');
    }
}
