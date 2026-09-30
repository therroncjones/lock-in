<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\RegistrationResponse as Responsable;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class RegistrationResponse implements Responsable
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        return redirect()->intended(route('home'));
    }
}
