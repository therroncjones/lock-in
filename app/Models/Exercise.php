<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'name', 'group', 'session_types', 'approved_at'])]
class Exercise extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'session_types' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Exercise>  $query
     * @return Builder<Exercise>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('approved_at');
    }

    public function approvalError(): ?string
    {
        if (($this->session_types ?? []) === []) {
            return 'Choose sessions and save before approving.';
        }

        $alreadyShared = static::query()
            ->whereNull('user_id')
            ->whereRaw('lower(name) = ?', [mb_strtolower($this->name)])
            ->whereKeyNot($this->id)
            ->exists();

        if ($alreadyShared) {
            return 'That name is already in the library.';
        }

        return null;
    }

    public function approve(): bool
    {
        if ($this->approvalError() !== null) {
            return false;
        }

        $this->update([
            'user_id' => null,
            'approved_at' => now(),
        ]);

        return true;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Exercise>  $query
     * @return Builder<Exercise>
     */
    public function scopeAvailableTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->whereNull('user_id')->orWhere('user_id', $user->id);
        });
    }
}
