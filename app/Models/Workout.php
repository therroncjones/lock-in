<?php

namespace App\Models;

use Database\Factories\WorkoutFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'performed_on', 'type', 'completed_at'])]
class Workout extends Model
{
    /**
     * @var list<string>
     */
    public const SuggestedTypes = [
        'Strength - Upper Body',
        'Strength - Lower Body',
        'Strength - Full Body',
        'Cardio',
        'Bodyweight',
        'Conditioning',
        'Mobility',
    ];

    /**
     * @var list<string>
     */
    public const StrengthTypes = [
        'Strength - Upper Body',
        'Strength - Lower Body',
        'Strength - Full Body',
    ];

    /**
     * Strength - Full Body progress includes every strength session.
     *
     * @return list<string>
     */
    public static function typesIncludedIn(string $type): array
    {
        if ($type === 'Strength - Full Body') {
            return self::StrengthTypes;
        }

        return [$type];
    }

    /**
     * Body-part groups shown as filters for a strength session.
     *
     * @var array<string, list<string>>
     */
    public const TypeGroups = [
        'Strength - Upper Body' => ['Upper Body', 'Core'],
        'Strength - Lower Body' => ['Lower Body', 'Core'],
        'Strength - Full Body' => ['Lower Body', 'Upper Body', 'Core', 'Full Body'],
    ];

    /** @use HasFactory<WorkoutFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'performed_on' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class)->orderBy('position');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(WorkoutBlock::class)->orderBy('name');
    }
}
