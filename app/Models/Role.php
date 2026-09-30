<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class Role extends Model
{
    public const Administrator = 'Administrator';

    public const Guest = 'Guest';

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
