<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Exercises\ExerciseResource;
use App\Models\Exercise;
use App\Models\Run;
use App\Models\Workout;
use App\Support\CoreLifts;
use App\Support\Greeting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $weekOffset = max(-520, min(520, $request->integer('week')));
        $monday = now()->copy()->startOfWeek(Carbon::MONDAY)->addWeeks($weekOffset)->startOfDay();
        $days = collect(range(0, 6))
            ->map(fn (int $offset): Carbon => $monday->copy()->addDays($offset));

        $workouts = Workout::query()
            ->whereBelongsTo($request->user())
            ->whereDate('performed_on', '>=', $days->first()->toDateString())
            ->whereDate('performed_on', '<=', $days->last()->toDateString())
            ->with('exercises.exercise')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Workout $workout): string => $workout->performed_on->toDateString());

        $runs = Run::query()
            ->whereBelongsTo($request->user())
            ->whereDate('performed_on', '>=', $days->first()->toDateString())
            ->whereDate('performed_on', '<=', $days->last()->toDateString())
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Run $run): string => $run->performed_on->toDateString());

        $week = $days->map(function (Carbon $day) use ($workouts, $runs): array {
            $key = $day->toDateString();
            $dayWorkouts = $workouts->get($key, collect());
            $dayRuns = $runs->get($key, collect());

            $counted = $this->countedWorkouts($dayWorkouts);

            return [
                'date' => $day,
                'logged' => $counted->isNotEmpty(),
                'completed' => $counted->isNotEmpty() && $counted->every(fn (Workout $workout): bool => $workout->completed_at !== null),
                'runs' => $dayRuns->count(),
                'href' => $this->dayHref($day, $counted, $dayRuns),
                'workoutHref' => $this->workoutHref($day, $counted, $dayRuns),
                'runHref' => $this->runHref($dayRuns),
                'summary' => $this->daySummary($counted, $dayRuns),
            ];
        });

        $todayWorkouts = Workout::query()
            ->whereBelongsTo($request->user())
            ->whereDate('performed_on', now()->toDateString())
            ->with('exercises.exercise')
            ->orderBy('id')
            ->get();
        $todayRuns = Run::query()
            ->whereBelongsTo($request->user())
            ->whereDate('performed_on', now()->toDateString())
            ->orderBy('id')
            ->get();

        $pendingExercises = $request->user()->isAdministrator()
            ? Exercise::query()->pending()->orderBy('name')->get()
            : collect();

        return view('home', [
            'greeting' => Greeting::forHour(now()->hour),
            'firstName' => trim(Str::before($request->user()->name, ' ')),
            'pendingExercises' => $pendingExercises,
            'pendingExercisesUrl' => ExerciseResource::getUrl('index'),
            'week' => $week,
            'weekOffset' => $weekOffset,
            'weekRange' => $weekOffset === 0
                ? null
                : $days->first()->format('M j').' - '.$days->last()->format('M j'),
            'todayIsComplete' => $this->countedWorkouts($todayWorkouts)->contains(fn (Workout $workout): bool => $workout->completed_at !== null)
                && $this->countedWorkouts($todayWorkouts)->every(fn (Workout $workout): bool => $workout->completed_at !== null),
            'todayHref' => $this->dayHref(now(), $this->countedWorkouts($todayWorkouts), $todayRuns),
            'todayWorkoutHref' => $this->workoutHref(now(), $this->countedWorkouts($todayWorkouts), $todayRuns),
            'todayRunHref' => $this->runHref($todayRuns),
            'todayRunOnly' => $this->countedWorkouts($todayWorkouts)->isEmpty() && $todayRuns->isNotEmpty(),
            'todayPlan' => $this->todayPlan($this->countedWorkouts($todayWorkouts), $todayRuns),
            'missingOneRepMax' => CoreLifts::missingSentence($request->user()),
        ]);
    }

    /**
     * @param  Collection<int, Workout>  $workouts
     * @return Collection<int, Workout>
     */
    private function countedWorkouts(Collection $workouts): Collection
    {
        return $workouts
            ->filter(fn (Workout $workout): bool => $workout->exercises->isNotEmpty() || $workout->completed_at !== null)
            ->values();
    }

    /**
     * @param  Collection<int, Workout>  $workouts
     * @param  Collection<int, Run>  $runs
     */
    private function dayHref(Carbon $day, Collection $workouts, Collection $runs): string
    {
        if ($workouts->isEmpty() && $runs->isNotEmpty()) {
            return route('runs', ['run' => $runs->sortByDesc('id')->first()->id]);
        }

        return route('log', ['date' => $day->toDateString()]);
    }

    /**
     * @param  Collection<int, Workout>  $workouts
     * @param  Collection<int, Run>  $runs
     */
    private function workoutHref(Carbon $day, Collection $workouts, Collection $runs): ?string
    {
        if ($workouts->isEmpty() && $runs->isNotEmpty()) {
            return null;
        }

        return route('log', ['date' => $day->toDateString()]);
    }

    /**
     * @param  Collection<int, Run>  $runs
     */
    private function runHref(Collection $runs): ?string
    {
        if ($runs->isEmpty()) {
            return null;
        }

        return route('runs', ['run' => $runs->sortByDesc('id')->first()->id]);
    }

    /**
     * @param  Collection<int, Workout>  $workouts
     * @param  Collection<int, Run>  $runs
     */
    private function daySummary(Collection $workouts, Collection $runs): ?string
    {
        $parts = $workouts
            ->map(fn (Workout $workout): string => $workout->type ?: 'Workout')
            ->values();

        if ($runs->isNotEmpty()) {
            $parts->push($runs->count() === 1 ? 'Run' : $runs->count().' runs');
        }

        if ($parts->isEmpty()) {
            return null;
        }

        return $parts->take(3)->implode(' · ');
    }

    /**
     * @param  Collection<int, Workout>  $workouts
     * @param  Collection<int, Run>  $runs
     * @return list<string>
     */
    private function todayPlan(Collection $workouts, Collection $runs): array
    {
        $lines = [];

        foreach ($workouts as $workout) {
            $names = $workout->exercises
                ->sortBy('position')
                ->map(fn ($entry): string => $entry->exercise->name)
                ->filter()
                ->values();
            $label = $workout->type ?: 'Workout';
            $lines[] = $names->isEmpty()
                ? $label
                : $label.': '.$names->take(3)->implode(', ').($names->count() > 3 ? ' +'.($names->count() - 3) : '');
        }

        foreach ($runs as $run) {
            $miles = rtrim(rtrim(number_format((float) $run->distance_miles, 2, '.', ''), '0'), '.');
            $lines[] = (Run::Types[$run->type] ?? 'Run').' · '.$miles.' mi';
        }

        return $lines;
    }
}
