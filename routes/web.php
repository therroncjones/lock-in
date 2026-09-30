<?php

use App\Http\Controllers\HomeController;
use App\Livewire\LogWorkout;
use App\Livewire\OneRepMax;
use App\Livewire\Progress;
use App\Livewire\Runs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    if ($request->user()) {
        return app(HomeController::class)($request);
    }

    return view('calculator');
})->name('home');

Route::get('/one-rep-max-calculator', function (Request $request) {
    return redirect()->route('home', $request->query());
});

Route::middleware('auth')->group(function () {

    Route::get('/log', LogWorkout::class)->name('log');

    Route::get('/progress', Progress::class)->name('progress');

    Route::get('/one-rep-max', OneRepMax::class)->name('one-rep-max');

    Route::get('/runs', Runs::class)->name('runs');

    Route::post('/logout', function () {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    })->name('logout');
});
