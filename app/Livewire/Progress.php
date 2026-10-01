<?php

namespace App\Livewire;

use App\Models\Exercise;
use App\Models\OneRepMax;
use App\Models\Run;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use App\Support\SetMeasure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Progress')]
class Progress extends Component
{
    use WithPagination;

    private const SetsPerPage = 10;

    /**
     * @var list<string>
     */
    private const OneRepMaxLifts = [
        'Barbell Back Squat',
        'Bench Press',
        'Conventional Deadlift',
    ];

    /**
     * @var list<string>
     */
    public const Types = Workout::SuggestedTypes;

    /**
     * @var array<string, string>
     */
    public const Ranges = [
        'month' => 'This Month',
        '3m' => '3M',
        '6m' => '6M',
        'year' => 'This Year',
        'all' => 'All',
    ];

    #[Url]
    public string $type = 'Strength - Upper Body';

    #[Url]
    public string $range = 'month';

    #[Url]
    public ?int $exercise = null;

    public bool $showingEverySet = false;

    public function mount(): void
    {
        $this->normalizeFilters();
        $this->ensureExercise();
    }

    public function filterType(string $type): void
    {
        if (! in_array($type, self::Types, true)) {
            return;
        }

        $this->type = $type;
        $this->showingEverySet = false;
        $this->resetPage();
        $this->ensureExercise();
    }

    public function updatedExercise(): void
    {
        $this->showingEverySet = false;
        $this->resetPage();
    }

    public function updatedRange(): void
    {
        $this->showingEverySet = false;
        $this->resetPage();
    }

    public function setRange(string $range): void
    {
        if (! array_key_exists($range, self::Ranges)) {
            return;
        }

        $this->range = $range;
        $this->resetPage();
    }

    public function showEverySet(): void
    {
        $this->showingEverySet = true;
        $this->resetPage();
    }

    public function showRecentSets(): void
    {
        $this->showingEverySet = false;
        $this->resetPage();
    }

    public function render()
    {
        $choices = $this->exerciseChoices();
        $consistency = $choices->isEmpty() ? collect() : $this->consistency();

        return view('livewire.progress', [
            'types' => self::Types,
            'ranges' => self::Ranges,
            'choices' => $choices,
            'summary' => $this->exercise ? $this->summary($this->exercise) : null,
            'sessionBars' => $this->barHeights($consistency->pluck('count')),
            'weekChart' => $this->weekChart($consistency),
            'rangeLabel' => self::Ranges[$this->range],
            'runs' => $this->runsSummary(),
        ]);
    }

    private function normalizeFilters(): void
    {
        if (! in_array($this->type, self::Types, true)) {
            $this->type = self::Types[0];
        }

        if (! array_key_exists($this->range, self::Ranges)) {
            $this->range = 'month';
        }
    }

    private function ensureExercise(): void
    {
        $choices = $this->exerciseChoices();

        if ($choices->contains('id', $this->exercise)) {
            return;
        }

        $this->exercise = $choices->sortByDesc('lastPerformedOn')->first()['id'] ?? null;
    }

    /**
     * @return Collection<int, array{id: int, name: string, lastPerformedOn: mixed}>
     */
    private function exerciseChoices(): Collection
    {
        return WorkoutExercise::query()
            ->whereHas('exercise', fn ($query) => $query->whereNotIn('name', array_values(Run::Types)))
            ->whereHas('workout', function ($query): void {
                $query->where('user_id', auth()->id());
                $this->constrainWorkoutType($query);
            })
            ->with(['exercise', 'workout'])
            ->get()
            ->groupBy('exercise_id')
            ->map(function (Collection $entries): array {
                /** @var WorkoutExercise $latest */
                $latest = $entries->sortByDesc(fn (WorkoutExercise $entry) => $entry->workout->performed_on)->first();

                return [
                    'id' => $latest->exercise_id,
                    'name' => $latest->exercise->name,
                    'lastPerformedOn' => $latest->workout->performed_on,
                ];
            })
            ->sortBy('name')
            ->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function summary(int $exerciseId): ?array
    {
        $sets = $this->setsFor($exerciseId);

        if ($sets->isEmpty()) {
            return null;
        }

        $measure = SetMeasure::normalize(Exercise::query()->whereKey($exerciseId)->value('measure'));
        $best = $this->bestSet($sets, $measure);
        $recent = $sets->sort(fn (WorkoutSet $left, WorkoutSet $right): int => $this->compareRecent($left, $right))->values();
        $setPages = $this->showingEverySet ? $this->setsPage($recent) : null;
        $visible = $setPages === null ? $recent->take(6) : $setPages->getCollection();
        $oneRepMax = $this->oneRepMaxSummary($exerciseId);
        $volumeComparison = $this->volumeComparison($sets, $exerciseId, $measure);
        $lastPerformed = $recent->first()->workoutExercise->workout->performed_on;
        $records = $this->oneRepMaxRecords($exerciseId);
        $dailyVolume = $sets
            ->groupBy(fn (WorkoutSet $set): string => $set->workoutExercise->workout->performed_on->toDateString())
            ->sortKeys()
            ->map(fn (Collection $day): float => $this->score($day, $measure))
            ->values();

        return [
            'oneRepMax' => $oneRepMax,
            'maxBars' => $oneRepMax ? $this->barHeights($oneRepMax['points']->pluck('value')) : [],
            'bestSet' => [
                'label' => $this->setLabel($best, $measure),
                'date' => $best->workoutExercise->workout->performed_on->format('M j, Y'),
            ],
            'volume' => $this->displayVolume($sets),
            'volumeBars' => $this->barHeights($dailyVolume),
            'volumeChange' => $volumeComparison['change'],
            'volumeChangeLabel' => $volumeComparison['label'],
            'sessions' => $sets->pluck('workoutExercise.workout_id')->unique()->count(),
            'sessionChange' => $this->sessionDelta($sets),
            'lastPerformed' => $lastPerformed->format('M j, Y'),
            'intensity' => $this->intensity($exerciseId, $best),
            'actuals' => $this->actualComparisons($exerciseId),
            'measure' => $measure,
            'bestCaption' => match ($measure) {
                SetMeasure::Time => 'Longest set logged in this range.',
                SetMeasure::Calories => 'Highest calorie set logged in this range.',
                SetMeasure::Meters => 'Farthest set logged in this range.',
                default => 'Heaviest set logged in this range.',
            },
            'volumeCaption' => match ($measure) {
                SetMeasure::Time => 'Time added up.',
                SetMeasure::Calories => 'Calories added up.',
                SetMeasure::Meters => 'Meters added up.',
                default => 'Weight times reps, added up.',
            },
            'volumeText' => $this->volumeText($sets, $measure),
            'setHeading' => match ($measure) {
                SetMeasure::Time => 'Time',
                SetMeasure::Calories => 'Calories',
                SetMeasure::Meters => 'Meters',
                default => 'Weight × Reps',
            },
            'chartHeading' => match ($measure) {
                SetMeasure::Time => 'Longest Set',
                SetMeasure::Calories => 'Highest Calories',
                SetMeasure::Meters => 'Farthest Set',
                default => 'Heaviest Set',
            },
            'sets' => $visible->map(fn (WorkoutSet $set): array => [
                'id' => $set->id,
                'label' => $this->setLabel($set, $measure),
                'date' => $set->workoutExercise->workout->performed_on->format('M j, Y'),
                'volume' => $this->setVolumeLabel($set, $measure),
                'percent' => $this->setPercent($records, $set),
                'isPr' => $set->id === $best->id,
            ])->values(),
            'hasMoreSets' => $sets->count() > 6,
            'setPages' => $setPages,
            'chart' => $oneRepMax ? $this->chart($oneRepMax['points']) : null,
            'heaviestChart' => $this->chart($this->heaviestPoints($sets, $measure)),
        ];
    }

    /**
     * @return array{weight: ?string, date: ?string, change: ?int, points: Collection<int, array{date: string, month: string, value: int}>}|null
     */
    private function oneRepMaxSummary(int $exerciseId): ?array
    {
        $exercise = Exercise::query()->find($exerciseId);

        if (! $exercise || ! in_array($exercise->name, self::OneRepMaxLifts, true)) {
            return null;
        }

        $start = $this->rangeStart();
        $records = OneRepMax::query()
            ->where('user_id', auth()->id())
            ->where('exercise_id', $exerciseId)
            ->when($start, fn ($query) => $query->whereDate('recorded_on', '>=', $start->toDateString()))
            ->orderBy('recorded_on')
            ->orderBy('id')
            ->get();

        $latest = $records->last();
        $earliest = $records->first();
        $previous = $records->count() > 1 ? $records[$records->count() - 2] : null;
        $change = null;

        if ($records->count() > 1 && (float) $earliest->weight > 0) {
            $change = (int) round((((float) $latest->weight - (float) $earliest->weight) / (float) $earliest->weight) * 100);
        }

        return [
            'weight' => $latest ? $this->displayWeight($latest->weight) : null,
            'date' => $latest?->recorded_on->format('M j, Y'),
            'change' => $change,
            'changeLbs' => $previous ? (int) round((float) $latest->weight - (float) $previous->weight) : null,
            'points' => $records->map(fn (OneRepMax $max): array => [
                'date' => $max->recorded_on->toDateString(),
                'month' => $max->recorded_on->format('M'),
                'value' => (int) round((float) $max->weight),
            ])->values(),
        ];
    }

    /**
     * @return Collection<int, WorkoutSet>
     */
    private function setsFor(int $exerciseId): Collection
    {
        return $this->setsInRange($exerciseId, $this->rangeStart(), null);
    }

    /**
     * @return Collection<int, WorkoutSet>
     */
    private function setsInRange(int $exerciseId, ?Carbon $start, ?Carbon $end): Collection
    {
        return WorkoutSet::query()
            ->where(function ($query): void {
                $query->where('reps', '>', 0)->orWhereNotNull('actual_reps');
            })
            ->whereHas('workoutExercise', function ($query) use ($exerciseId, $start, $end): void {
                $query->where('exercise_id', $exerciseId)
                    ->whereHas('workout', function ($workout) use ($start, $end): void {
                        $workout->where('user_id', auth()->id());
                        $this->constrainWorkoutType($workout);

                        if ($start) {
                            $workout->whereDate('performed_on', '>=', $start->toDateString());
                        }

                        if ($end) {
                            $workout->whereDate('performed_on', '<', $end->toDateString());
                        }
                    });
            })
            ->with('workoutExercise.workout')
            ->get();
    }

    /**
     * @param  Collection<int, WorkoutSet>  $sets
     * @return array{change: ?int, label: string}
     */
    private function volumeComparison(Collection $sets, int $exerciseId, string $measure): array
    {
        if ($this->range === 'all') {
            return $this->splitVolume($sets, $measure);
        }

        if ($this->range === 'month') {
            $priorStart = now()->copy()->subMonthNoOverflow()->startOfMonth()->startOfDay();
            $priorEnd = now()->copy()->subMonthNoOverflow()->addDay()->startOfDay();

            return [
                'change' => $this->percentChange($this->score($sets, $measure), $this->score($this->setsInRange($exerciseId, $priorStart, $priorEnd), $measure)),
                'label' => 'vs last month',
            ];
        }

        $start = $this->rangeStart()?->copy()->startOfDay();

        if ($start === null) {
            return ['change' => null, 'label' => 'vs prior period'];
        }

        $days = max(1, (int) $start->diffInDays(now()->copy()->startOfDay()));
        $prior = $this->score($this->setsInRange($exerciseId, $start->copy()->subDays($days), $start), $measure);

        return [
            'change' => $this->percentChange($this->score($sets, $measure), $prior),
            'label' => $this->range === 'year' ? 'vs last year' : 'vs prior period',
        ];
    }

    /**
     * @param  Collection<int, WorkoutSet>  $sets
     * @return array{change: ?int, label: string}
     */
    private function splitVolume(Collection $sets, string $measure): array
    {
        $first = $sets->min(fn (WorkoutSet $set) => $set->workoutExercise->workout->performed_on);
        $last = $sets->max(fn (WorkoutSet $set) => $set->workoutExercise->workout->performed_on);

        if (! $first instanceof Carbon || ! $last instanceof Carbon || $first->equalTo($last)) {
            return ['change' => null, 'label' => 'vs earlier half'];
        }

        $mid = $first->copy()->addDays((int) floor($first->diffInDays($last) / 2));
        $earlier = $sets->filter(fn (WorkoutSet $set): bool => $set->workoutExercise->workout->performed_on->lte($mid));
        $later = $sets->reject(fn (WorkoutSet $set): bool => $set->workoutExercise->workout->performed_on->lte($mid));

        return [
            'change' => $this->percentChange($this->score($later, $measure), $this->score($earlier, $measure)),
            'label' => 'vs earlier half',
        ];
    }

    private function percentChange(float $current, float $prior): ?int
    {
        if ($prior <= 0) {
            return null;
        }

        return (int) round((($current - $prior) / $prior) * 100);
    }

    /**
     * @param  Collection<int, float|int>  $values
     * @return list<float>
     */
    private function barHeights(Collection $values): array
    {
        $values = $values->take(-12)->values();
        $max = (float) $values->max();

        if ($max <= 0) {
            return [];
        }

        return $values->map(fn (mixed $value): float => round(((float) $value / $max) * 100, 1))->all();
    }

    /**
     * @param  Collection<int, WorkoutSet>  $sets
     * @return array{change: int, label: string}|null
     */
    private function sessionDelta(Collection $sets): ?array
    {
        if ($this->range !== 'all') {
            return null;
        }

        $first = $sets->min(fn (WorkoutSet $set) => $set->workoutExercise->workout->performed_on);
        $last = $sets->max(fn (WorkoutSet $set) => $set->workoutExercise->workout->performed_on);

        if (! $first instanceof Carbon || ! $last instanceof Carbon || $first->equalTo($last)) {
            return null;
        }

        $mid = $first->copy()->addDays((int) floor($first->diffInDays($last) / 2));
        $earlier = $sets
            ->filter(fn (WorkoutSet $set): bool => $set->workoutExercise->workout->performed_on->lte($mid))
            ->pluck('workoutExercise.workout_id')
            ->unique()
            ->count();
        $later = $sets
            ->reject(fn (WorkoutSet $set): bool => $set->workoutExercise->workout->performed_on->lte($mid))
            ->pluck('workoutExercise.workout_id')
            ->unique()
            ->count();

        if ($earlier === 0) {
            return null;
        }

        return [
            'change' => $later - $earlier,
            'label' => 'vs earlier half',
        ];
    }

    /**
     * @return Collection<int, OneRepMax>
     */
    private function oneRepMaxRecords(int $exerciseId): Collection
    {
        return OneRepMax::query()
            ->where('user_id', auth()->id())
            ->where('exercise_id', $exerciseId)
            ->orderBy('recorded_on')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, OneRepMax>  $records
     */
    private function setPercent(Collection $records, WorkoutSet $set): ?int
    {
        $weight = $this->performedWeight($set);

        if ($weight === null || $weight === '' || (float) $weight <= 0 || $records->isEmpty()) {
            return null;
        }

        $date = $set->workoutExercise->workout->performed_on;
        $max = $records->filter(fn (OneRepMax $record): bool => $record->recorded_on->lte($date))->last();

        if (! $max instanceof OneRepMax || (float) $max->weight <= 0) {
            return null;
        }

        return (int) round(((float) $weight / (float) $max->weight) * 100);
    }

    private function setVolumeLabel(WorkoutSet $set, string $measure): string
    {
        if (! SetMeasure::tracksWeight($measure)) {
            return SetMeasure::describe($measure, $this->performedAmount($set)) ?? '—';
        }

        $weight = $this->performedWeight($set);

        if ($weight === null || $weight === '') {
            return '—';
        }

        return $this->displayVolume(collect([$set]));
    }

    /**
     * @return array{percent: int, detail: string}|null
     */
    private function intensity(int $exerciseId, WorkoutSet $best): ?array
    {
        $exercise = Exercise::query()->find($exerciseId);

        if (! $exercise || ! in_array($exercise->name, self::OneRepMaxLifts, true)) {
            return null;
        }

        $weight = $this->performedWeight($best);

        if ($weight === null || $weight === '' || (float) $weight <= 0) {
            return null;
        }

        $max = OneRepMax::query()
            ->where('user_id', auth()->id())
            ->where('exercise_id', $exerciseId)
            ->whereDate('recorded_on', '<=', $best->workoutExercise->workout->performed_on->toDateString())
            ->orderByDesc('recorded_on')
            ->orderByDesc('id')
            ->first();

        if ($max === null || (float) $max->weight <= 0) {
            return null;
        }

        return [
            'percent' => (int) round(((float) $weight / (float) $max->weight) * 100),
            'detail' => $this->displayWeight($weight).' lbs of '.$this->displayWeight($max->weight),
        ];
    }

    /**
     * @return list<array{actual: string, planned: string, delta: string, direction: int, status: string, date: string}>
     */
    private function actualComparisons(int $exerciseId): array
    {
        $start = $this->rangeStart();
        $latest = WorkoutExercise::query()
            ->where('exercise_id', $exerciseId)
            ->whereHas('workout', function ($workout) use ($start): void {
                $workout->where('user_id', auth()->id());
                $this->constrainWorkoutType($workout);

                if ($start) {
                    $workout->whereDate('performed_on', '>=', $start->toDateString());
                }
            })
            ->with(['sets', 'workout', 'exercise'])
            ->get()
            ->sortByDesc(fn (WorkoutExercise $entry) => $entry->workout->performed_on)
            ->first();

        if (! $latest instanceof WorkoutExercise) {
            return [];
        }

        $sets = $latest->sets
            ->filter(fn (WorkoutSet $set): bool => $set->actual_weight !== null || $set->actual_reps !== null)
            ->sortBy('position');

        $measure = SetMeasure::normalize($latest->exercise->measure);

        if ($sets->isNotEmpty()) {
            return $sets
                ->map(fn (WorkoutSet $set): array => $this->compareActual(
                    $set->actual_weight,
                    $set->actual_reps,
                    $set,
                    $latest->workout->performed_on->format('M j, Y'),
                    $measure,
                ))
                ->values()
                ->all();
        }

        if ($latest->actual_weight === null && $latest->actual_reps === null) {
            return [];
        }

        $planned = $latest->sets
            ->filter(fn (WorkoutSet $set): bool => (int) $set->reps > 0)
            ->sort(function (WorkoutSet $left, WorkoutSet $right): int {
                $weight = $this->weightValue($right) <=> $this->weightValue($left);

                return $weight !== 0 ? $weight : ($right->reps <=> $left->reps);
            })
            ->first();

        return [
            $this->compareActual(
                $latest->actual_weight,
                $latest->actual_reps,
                $planned instanceof WorkoutSet ? $planned : null,
                $latest->workout->performed_on->format('M j, Y'),
                $measure,
            ),
        ];
    }

    /**
     * @return array{actual: string, planned: string, delta: string, direction: int, status: string, date: string}
     */
    private function compareActual(mixed $actualWeight, mixed $actualReps, ?WorkoutSet $planned, string $date, string $measure = SetMeasure::Reps): array
    {
        $notes = [];
        $direction = 0;
        $measure = SetMeasure::normalize($measure);

        if (SetMeasure::tracksWeight($measure) && $actualWeight !== null && $actualWeight !== '' && $planned instanceof WorkoutSet && $planned->weight !== null && $planned->weight !== '') {
            $delta = (float) $actualWeight - (float) $planned->weight;
            $notes[] = $this->signedAmount($delta, 'lbs');
            $direction = $delta <=> 0;
        }

        if ($actualReps !== null && $planned instanceof WorkoutSet && $planned->reps !== null) {
            $delta = (int) $actualReps - (int) $planned->reps;
            $notes[] = SetMeasure::tracksWeight($measure)
                ? $this->signedAmount($delta, abs($delta) === 1 ? 'rep' : 'reps')
                : SetMeasure::signedDelta($measure, $delta);

            if ($direction === 0) {
                $direction = $delta <=> 0;
            }
        }

        return [
            'actual' => $this->actualLabel($actualWeight, $actualReps, $measure),
            'planned' => $planned instanceof WorkoutSet ? ($this->plannedLabel($planned, $measure) ?? 'No planned set') : 'No planned set',
            'delta' => $notes === [] ? 'Logged' : implode(', ', $notes),
            'direction' => $direction,
            'status' => $direction > 0 ? 'Ahead' : ($direction < 0 ? 'Under plan' : 'On Track'),
            'date' => $date,
        ];
    }

    /**
     * @return array{count: int, miles: string, pace: string, runs: list<array{id: int, date: string, label: string, distance: string, pace: string, splits: list<string>, href: string}>}|null
     */
    private function runsSummary(): ?array
    {
        if ($this->type !== 'Cardio') {
            return null;
        }

        $start = $this->rangeStart();
        $runs = Run::query()
            ->where('user_id', auth()->id())
            ->when($start, fn ($query) => $query->whereDate('performed_on', '>=', $start->toDateString()))
            ->with('splits')
            ->orderByDesc('performed_on')
            ->orderByDesc('id')
            ->get();

        if ($runs->isEmpty()) {
            return null;
        }

        $miles = (float) $runs->sum('distance_miles');
        $milesLabel = rtrim(rtrim(number_format($miles, 2, '.', ''), '0'), '.');

        return [
            'count' => $runs->count(),
            'miles' => $milesLabel === '' ? '0' : $milesLabel,
            'pace' => Run::formatPace((int) $runs->sum('duration_seconds'), $miles),
            'runs' => $runs->take(5)->map(function (Run $run): array {
                return [
                    'id' => $run->id,
                    'date' => $run->performed_on->format('M j, Y'),
                    'label' => $run->typeLabel(),
                    'distance' => $run->distanceLabel(),
                    'pace' => $run->paceLabel().' /mi',
                    'splits' => collect($run->splitRows())
                        ->map(fn (array $split): string => $split['distance'].' mi · '.$split['pace'].' /mi')
                        ->all(),
                    'href' => route('runs', ['run' => $run->id]),
                ];
            })->all(),
        ];
    }

    private function actualLabel(mixed $weight, mixed $reps, string $measure = SetMeasure::Reps): string
    {
        return SetMeasure::describe($measure, $reps, $weight) ?? '—';
    }

    private function signedAmount(float|int $delta, string $unit): string
    {
        $number = fmod((float) $delta, 1.0) === 0.0 ? (string) (int) $delta : $this->displayWeight(abs($delta));

        if (is_string($number) && str_starts_with($number, '-') === false && (float) $delta < 0) {
            $number = '-'.$number;
        }

        if ((float) $delta > 0) {
            $number = '+'.$number;
        }

        return $number.' '.$unit;
    }

    /**
     * @param  Collection<int, WorkoutSet>  $sets
     * @return Collection<int, array{date: string, month: string, value: int}>
     */
    private function heaviestPoints(Collection $sets, string $measure): Collection
    {
        return $sets
            ->filter(function (WorkoutSet $set) use ($measure): bool {
                if (! SetMeasure::tracksWeight($measure)) {
                    return $this->performedAmount($set) > 0;
                }

                $weight = $this->performedWeight($set);

                return $weight !== null && $weight !== '';
            })
            ->groupBy(fn (WorkoutSet $set): string => $set->workoutExercise->workout->performed_on->toDateString())
            ->map(function (Collection $daySets, string $date) use ($measure): array {
                /** @var WorkoutSet $top */
                $top = $daySets->sortByDesc(function (WorkoutSet $set) use ($measure): float {
                    if (! SetMeasure::tracksWeight($measure)) {
                        return (float) $this->performedAmount($set);
                    }

                    return (float) $this->performedWeight($set);
                })->first();

                $value = SetMeasure::tracksWeight($measure)
                    ? (float) $this->performedWeight($top)
                    : (float) $this->performedAmount($top);

                return [
                    'date' => $date,
                    'month' => Carbon::parse($date)->format('M'),
                    'value' => (int) round($value),
                ];
            })
            ->sortBy('date')
            ->values();
    }

    /**
     * @return Collection<int, array{label: string, count: int}>
     */
    private function consistency(): Collection
    {
        $end = now()->copy()->startOfWeek(Carbon::MONDAY);
        $rangeStart = $this->rangeStart();
        $first = ($rangeStart ?? now()->copy()->startOfYear())->copy()->startOfWeek(Carbon::MONDAY);
        $earliest = $end->copy()->subWeeks(39);

        if ($first->lt($earliest)) {
            $first = $earliest;
        }

        $workouts = Workout::query()
            ->where('user_id', auth()->id())
            ->tap(fn ($query) => $this->constrainWorkoutType($query))
            ->whereNotNull('completed_at')
            ->whereDate('performed_on', '>=', $first->toDateString())
            ->whereDate('performed_on', '<=', $end->copy()->addDays(6)->toDateString())
            ->get();

        $weeks = collect();

        for ($cursor = $first->copy(); $cursor->lte($end); $cursor->addWeek()) {
            $weekStart = $cursor->copy();
            $weekEnd = $weekStart->copy()->addDays(6);
            $count = $workouts->filter(function (Workout $workout) use ($weekStart, $weekEnd): bool {
                return $workout->performed_on->gte($weekStart) && $workout->performed_on->lte($weekEnd);
            })->count();

            $weeks->push([
                'label' => $weekStart->format('M j'),
                'month' => $weekStart->copy()->addDays(3)->format('M'),
                'count' => $count,
            ]);
        }

        return $weeks;
    }

    /**
     * @param  Collection<int, array{label: string, month: string, count: int}>  $weeks
     * @return array<string, mixed>|null
     */
    private function weekChart(Collection $weeks): ?array
    {
        if ($weeks->isEmpty()) {
            return null;
        }

        $width = 320;
        $height = 150;
        $left = 24;
        $right = 18;
        $top = 8;
        $bottom = 22;
        $max = max(4, (int) $weeks->max('count'));
        $plotWidth = $width - $left - $right;
        $plotHeight = $height - $top - $bottom;
        $groups = [];

        foreach ($weeks as $week) {
            $groups[$week['month']][] = $week;
        }

        $column = $plotWidth / max(1, count($groups));
        $bars = [];
        $labels = [];
        $monthIndex = 0;

        foreach ($groups as $month => $monthWeeks) {
            $center = $left + (($monthIndex + 0.5) * $column);
            $labels[] = [
                'x' => round($center, 1),
                'text' => $month,
            ];

            $active = array_values(array_filter(
                $monthWeeks,
                fn (array $week): bool => $week['count'] > 0,
            ));
            $count = count($active);

            if ($count > 0) {
                $gap = 2;
                $available = max(4, $column - 10);
                $barWidth = min(10, ($available - ($gap * max(0, $count - 1))) / $count);
                $barWidth = max(3, $barWidth);
                $total = ($count * $barWidth) + (($count - 1) * $gap);
                $origin = $center - ($total / 2);

                foreach ($active as $index => $week) {
                    $barHeight = ($week['count'] / $max) * $plotHeight;
                    $bars[] = [
                        'x' => round($origin + ($index * ($barWidth + $gap)), 1),
                        'y' => round($top + $plotHeight - $barHeight, 1),
                        'width' => round($barWidth, 1),
                        'height' => max(2, round($barHeight, 1)),
                        'label' => $week['label'].', '.$week['count'],
                    ];
                }
            }

            $monthIndex++;
        }

        $ticks = collect(range(0, 4))->map(function (int $step) use ($max, $top, $plotHeight): array {
            return [
                'label' => (string) (int) round($max * $step / 4),
                'y' => round($top + ($plotHeight * (1 - ($step / 4))), 1),
            ];
        });

        return [
            'width' => $width,
            'height' => $height,
            'left' => $left,
            'right' => $right,
            'ticks' => $ticks,
            'bars' => $bars,
            'labels' => $labels,
            'baseline' => $top + $plotHeight,
        ];
    }

    private function setsPage(Collection $sets): LengthAwarePaginator
    {
        $last = max(1, (int) ceil($sets->count() / self::SetsPerPage));

        if ((int) $this->getPage() > $last) {
            $this->setPage($last);
        }

        $page = max(1, (int) $this->getPage());

        return new LengthAwarePaginator(
            $sets->forPage($page, self::SetsPerPage)->values(),
            $sets->count(),
            self::SetsPerPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );
    }

    private function rangeStart(): ?Carbon
    {
        return match ($this->range) {
            'month' => now()->startOfMonth(),
            '3m' => now()->subDays(90),
            '6m' => now()->subDays(180),
            'year' => now()->startOfYear(),
            default => null,
        };
    }

    /**
     * @param  Collection<int, WorkoutSet>  $sets
     */
    private function bestSet(Collection $sets, string $measure): WorkoutSet
    {
        return $sets->sort(function (WorkoutSet $left, WorkoutSet $right) use ($measure): int {
            if (! SetMeasure::tracksWeight($measure)) {
                $amount = $this->performedAmount($right) <=> $this->performedAmount($left);

                return $amount !== 0 ? $amount : $this->compareRecent($left, $right);
            }

            $weight = $this->weightValue($right) <=> $this->weightValue($left);

            if ($weight !== 0) {
                return $weight;
            }

            $reps = $this->performedReps($right) <=> $this->performedReps($left);

            if ($reps !== 0) {
                return $reps;
            }

            return $this->compareRecent($left, $right);
        })->first();
    }

    private function compareRecent(WorkoutSet $left, WorkoutSet $right): int
    {
        $date = $right->workoutExercise->workout->performed_on <=> $left->workoutExercise->workout->performed_on;

        if ($date !== 0) {
            return $date;
        }

        return $right->id <=> $left->id;
    }

    /**
     * @param  Collection<int, array{date: string, month: string, value: int}>  $points
     * @return array<string, mixed>|null
     */
    private function chart(Collection $points): ?array
    {
        if ($points->isEmpty()) {
            return null;
        }

        $width = 320;
        $height = 180;
        $left = 36;
        $right = 16;
        $top = 28;
        $bottom = 28;
        $plotWidth = $width - $left - $right;
        $plotHeight = $height - $top - $bottom;
        $min = (int) $points->min('value');
        $max = (int) $points->max('value');

        if ($min === $max) {
            $min = max(0, $min - 20);
            $max += 20;
        } else {
            $pad = (int) ceil(($max - $min) * 0.2);
            $min = max(0, (int) (floor(($min - $pad) / 10) * 10));
            $max = (int) (ceil(($max + $pad) / 10) * 10);
        }

        $span = max(1, $max - $min);
        $count = $points->count();
        $dots = $points->values()->map(function (array $point, int $index) use ($count, $left, $plotWidth, $top, $plotHeight, $min, $span): array {
            $x = $count === 1 ? $left + ($plotWidth / 2) : $left + ($plotWidth * $index / ($count - 1));
            $y = $top + ($plotHeight * (1 - (($point['value'] - $min) / $span)));

            return [
                'x' => round($x, 1),
                'y' => round($y, 1),
                'month' => $point['month'],
            ];
        });

        $ticks = collect(range(0, 4))->map(function (int $step) use ($min, $span, $top, $plotHeight): array {
            $value = (int) round($min + ($span * $step / 4));

            return [
                'label' => (string) $value,
                'y' => round($top + ($plotHeight * (1 - ($step / 4))), 1),
            ];
        });

        $labels = [];
        $lastMonth = null;

        foreach ($dots as $dot) {
            if ($dot['month'] === $lastMonth) {
                continue;
            }

            $labels[] = [
                'x' => $dot['x'],
                'text' => $dot['month'],
            ];
            $lastMonth = $dot['month'];
        }

        $baseline = $top + $plotHeight;
        $line = $dots->map(fn (array $dot): string => $dot['x'].','.$dot['y'])->implode(' ');
        $area = '';

        if ($dots->count() > 1) {
            $area = $dots->first()['x'].','.$baseline.' '.$line.' '.$dots->last()['x'].','.$baseline;
        }

        $lastDot = $dots->last();
        $endLabel = $points->last()['value'].' lbs';
        $pillWidth = 10 + (strlen($endLabel) * 6.2);
        $pillX = min($width - $pillWidth - 2, max(2, $lastDot['x'] - ($pillWidth / 2)));

        return [
            'width' => $width,
            'height' => $height,
            'left' => $left,
            'right' => $right,
            'ticks' => $ticks,
            'dots' => $dots,
            'labels' => $labels,
            'line' => $line,
            'area' => $area,
            'end' => [
                'x' => round($pillX, 1),
                'y' => round(max(2, $lastDot['y'] - 22), 1),
                'width' => round($pillWidth, 1),
                'label' => $endLabel,
                'textX' => round($pillX + ($pillWidth / 2), 1),
            ],
        ];
    }

    private function weightValue(WorkoutSet $set): float
    {
        $weight = $this->performedWeight($set);

        if ($weight === null || $weight === '') {
            return -1;
        }

        return (float) $weight;
    }

    private function performedWeight(WorkoutSet $set): mixed
    {
        if ($set->actual_weight !== null && $set->actual_weight !== '') {
            return $set->actual_weight;
        }

        return $set->weight;
    }

    private function performedReps(WorkoutSet $set): int
    {
        if ($set->actual_reps !== null) {
            return (int) $set->actual_reps;
        }

        return (int) $set->reps;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Workout>  $query
     */
    private function constrainWorkoutType($query): void
    {
        $types = Workout::typesIncludedIn($this->type);

        if (count($types) === 1) {
            $query->where('type', $types[0]);

            return;
        }

        $query->whereIn('type', $types);
    }

    /**
     * @param  Collection<int, WorkoutSet>  $sets
     */
    /**
     * @param  Collection<int, WorkoutSet>  $sets
     */
    private function rawVolume(Collection $sets): float
    {
        return $sets->sum(function (WorkoutSet $set): float {
            $weight = $this->performedWeight($set);

            if ($weight === null || $weight === '') {
                return 0;
            }

            return (float) $weight * $this->performedReps($set);
        });
    }

    /**
     * @param  Collection<int, WorkoutSet>  $sets
     */
    private function displayVolume(Collection $sets): string
    {
        $volume = $this->rawVolume($sets);
        $decimals = fmod($volume, 1.0) === 0.0 ? 0 : 2;

        return number_format($volume, $decimals);
    }

    /**
     * @param  Collection<int, WorkoutSet>  $sets
     */
    private function score(Collection $sets, string $measure): float
    {
        if (SetMeasure::tracksWeight($measure)) {
            return $this->rawVolume($sets);
        }

        return $sets->sum(fn (WorkoutSet $set): float => (float) $this->performedAmount($set));
    }

    /**
     * @param  Collection<int, WorkoutSet>  $sets
     */
    private function volumeText(Collection $sets, string $measure): string
    {
        if ($measure === SetMeasure::Time) {
            return SetMeasure::formatDuration((int) round($this->score($sets, $measure)));
        }

        if ($measure === SetMeasure::Calories) {
            $count = (int) round($this->score($sets, $measure));

            return number_format($count).' '.($count === 1 ? 'calorie' : 'calories');
        }

        if ($measure === SetMeasure::Meters) {
            return number_format((int) round($this->score($sets, $measure))).' m';
        }

        return $this->displayVolume($sets).' lbs';
    }

    private function performedAmount(WorkoutSet $set): int
    {
        if ($set->actual_reps !== null) {
            return (int) $set->actual_reps;
        }

        return (int) $set->reps;
    }

    private function plannedLabel(WorkoutSet $set, string $measure = SetMeasure::Reps): ?string
    {
        return SetMeasure::describe($measure, $set->reps, $set->weight);
    }

    private function setLabel(WorkoutSet $set, string $measure = SetMeasure::Reps): string
    {
        $amount = SetMeasure::tracksWeight($measure) ? $this->performedReps($set) : $this->performedAmount($set);

        return SetMeasure::describe($measure, $amount, $this->performedWeight($set)) ?? '—';
    }

    private function displayWeight(mixed $weight): string
    {
        if ($weight === null || $weight === '') {
            return '';
        }

        $number = (float) $weight;

        if (fmod($number, 1.0) === 0.0) {
            return (string) (int) $number;
        }

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }
}
