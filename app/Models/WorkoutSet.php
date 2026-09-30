<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['workout_exercise_id', 'position', 'weight', 'reps', 'actual_weight', 'actual_reps', 'completed'])]
class WorkoutSet extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'reps' => 'integer',
            'actual_weight' => 'decimal:2',
            'actual_reps' => 'integer',
            'completed' => 'boolean',
        ];
    }

    public function workoutExercise(): BelongsTo
    {
        return $this->belongsTo(WorkoutExercise::class);
    }
}
