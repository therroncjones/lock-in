<?php

namespace App\Filament\Auth;

use App\Models\Role;
use App\Models\User;
use Filament\Auth\Pages\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;

class Register extends BaseRegister
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        /** @var User $user */
        $user = parent::handleRegistration($data);

        $hasAdministrator = User::query()
            ->whereKeyNot($user->id)
            ->whereHas('roles', fn ($query) => $query->where('name', Role::Administrator))
            ->exists();

        $role = $hasAdministrator ? Role::Guest : Role::Administrator;

        $user->roles()->sync([
            Role::query()->firstOrCreate(['name' => $role])->id,
        ]);

        return $user;
    }
}
