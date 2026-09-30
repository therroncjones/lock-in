<?php

namespace App\Livewire;

use App\Models\Run;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Runs')]
class Runs extends Component
{
    use WithPagination;

    private const HistoryPerPage = 10;

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

    /**
     * @var array<string, string>
     */
    public const Sorts = [
        'newest' => 'Newest First',
        'oldest' => 'Oldest First',
    ];

    #[Url]
    public string $range = 'month';

    #[Url]
    public string $activity = 'all';

    #[Url]
    public string $sort = 'newest';

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $run = null;

    public string $detailTab = 'splits';

    public bool $logging = false;

    public ?int $editingRun = null;

    public string $performedOn = '';

    public string $startedAt = '';

    public string $type = 'outdoor';

    public string $distance = '';

    public string $duration = '';

    public string $elevationGain = '';

    public string $calories = '';

    public string $averageHeartRate = '';

    public string $maxHeartRate = '';

    public string $temperature = '';

    public string $notes = '';

    /**
     * @var list<array{distance: string, duration: string, elevation: string}>
     */
    public array $splits = [];

    public function mount(): void
    {
        $this->normalize();
        $this->ensureSelection();
    }

    public function updatedRange(): void
    {
        $this->resetPage();
        $this->ensureSelection();
    }

    public function updatedActivity(): void
    {
        $this->resetPage();
        $this->ensureSelection();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
        $this->ensureSelection();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->ensureSelection();
    }

    public function selectRun(int $runId): void
    {
        $this->run = $this->ownedRun($runId)->id;
        $this->detailTab = 'splits';
    }

    public function showDetail(string $tab): void
    {
        if (! in_array($tab, ['splits', 'elevation', 'heart'], true)) {
            return;
        }

        $this->detailTab = $tab;
    }

    public function startLog(): void
    {
        $this->resetForm();
        $this->logging = true;
    }

    public function editRun(int $runId): void
    {
        $run = $this->ownedRun($runId);
        $this->editingRun = $run->id;
        $this->performedOn = $run->performed_on->toDateString();
        $this->startedAt = $run->started_at ? Carbon::parse($run->started_at)->format('H:i') : '';
        $this->type = $run->type;
        $this->distance = $run->distanceLabel();
        $this->duration = $run->durationLabel();
        $this->elevationGain = $run->elevation_gain_feet === null ? '' : (string) $run->elevation_gain_feet;
        $this->calories = $run->calories === null ? '' : (string) $run->calories;
        $this->averageHeartRate = $run->average_heart_rate === null ? '' : (string) $run->average_heart_rate;
        $this->maxHeartRate = $run->max_heart_rate === null ? '' : (string) $run->max_heart_rate;
        $this->temperature = $run->temperature_fahrenheit === null ? '' : (string) $run->temperature_fahrenheit;
        $this->notes = $run->notes ?? '';
        $this->splits = $run->splits->map(fn ($split): array => [
            'distance' => $split->distanceLabel(),
            'duration' => Run::formatDuration((int) $split->duration_seconds),
            'elevation' => $split->elevation_feet === null ? '' : (string) $split->elevation_feet,
        ])->all();

        if ($this->splits === []) {
            $this->splits = [$this->blankSplit()];
        }

        $this->logging = true;
    }

    public function cancelLog(): void
    {
        $this->logging = false;
        $this->resetForm();
    }

    public function addSplit(): void
    {
        $this->splits[] = $this->blankSplit();
    }

    public function removeSplit(int $index): void
    {
        unset($this->splits[$index]);
        $this->splits = array_values($this->splits);

        if ($this->splits === []) {
            $this->splits = [$this->blankSplit()];
        }
    }

    public function saveRun(): void
    {
        $this->validate([
            'performedOn' => ['required', 'date'],
            'startedAt' => [Rule::excludeIf(fn (): bool => trim($this->startedAt) === ''), 'date_format:H:i'],
            'type' => ['required', Rule::in(array_keys(Run::Types))],
            'distance' => ['required', 'numeric', 'gt:0', 'max:999'],
            'duration' => ['required', 'string', 'max:20'],
            'elevationGain' => [Rule::excludeIf(fn (): bool => trim($this->elevationGain) === ''), 'integer', 'min:0', 'max:100000'],
            'calories' => [Rule::excludeIf(fn (): bool => trim($this->calories) === ''), 'integer', 'min:0', 'max:20000'],
            'averageHeartRate' => [Rule::excludeIf(fn (): bool => trim($this->averageHeartRate) === ''), 'integer', 'min:1', 'max:250'],
            'maxHeartRate' => [Rule::excludeIf(fn (): bool => trim($this->maxHeartRate) === ''), 'integer', 'min:1', 'max:250'],
            'temperature' => [Rule::excludeIf(fn (): bool => trim($this->temperature) === ''), 'integer', 'min:-40', 'max:130'],
            'notes' => [Rule::excludeIf(fn (): bool => trim($this->notes) === ''), 'string', 'max:2000'],
        ]);

        $duration = $this->parseDuration($this->duration);

        if ($duration === null) {
            $this->addError('duration', 'Use minutes and seconds, like 32:18.');

            return;
        }

        $splitRows = $this->validatedSplits();

        if ($splitRows === null) {
            return;
        }

        $attributes = [
            'user_id' => auth()->id(),
            'performed_on' => $this->performedOn,
            'started_at' => $this->startedAt === '' ? null : $this->startedAt.':00',
            'type' => $this->type,
            'distance_miles' => $this->distance,
            'duration_seconds' => $duration,
            'elevation_gain_feet' => $this->blankToNull($this->elevationGain),
            'calories' => $this->blankToNull($this->calories),
            'average_heart_rate' => $this->blankToNull($this->averageHeartRate),
            'max_heart_rate' => $this->blankToNull($this->maxHeartRate),
            'temperature_fahrenheit' => $this->blankToNull($this->temperature),
            'notes' => trim($this->notes) === '' ? null : trim($this->notes),
        ];

        if ($this->editingRun) {
            $run = $this->ownedRun($this->editingRun);
            $run->update($attributes);
            $run->splits()->delete();
        } else {
            $run = Run::query()->create($attributes);
        }

        foreach ($splitRows as $position => $split) {
            $run->splits()->create([
                'position' => $position + 1,
                'distance_miles' => $split['distance'],
                'duration_seconds' => $split['duration'],
                'elevation_feet' => $split['elevation'],
            ]);
        }

        if ($this->editingRun === null) {
            $this->resetPage();
        }

        $this->run = $run->id;
        $this->cancelLog();
    }

    public function deleteRun(): void
    {
        if ($this->editingRun === null) {
            return;
        }

        $this->ownedRun($this->editingRun)->delete();
        $this->run = null;
        $this->cancelLog();
        $this->resetPage();
        $this->ensureSelection();
    }

    public function render()
    {
        $this->normalize();
        $filtered = $this->filteredRuns();
        $this->clampHistoryPage($filtered->count());
        $runs = $this->historyPage($filtered);
        $selected = $filtered->firstWhere('id', $this->run) ?? $filtered->first();

        return view('livewire.runs', [
            'types' => Run::Types,
            'ranges' => self::Ranges,
            'sorts' => self::Sorts,
            'runs' => $runs,
            'selected' => $selected,
            'summary' => $this->summary($this->rangedRuns()),
        ]);
    }

    private function normalize(): void
    {
        if (! array_key_exists($this->range, self::Ranges)) {
            $this->range = 'month';
        }

        if ($this->activity !== 'all' && ! array_key_exists($this->activity, Run::Types)) {
            $this->activity = 'all';
        }

        if (! array_key_exists($this->sort, self::Sorts)) {
            $this->sort = 'newest';
        }
    }

    private function ensureSelection(): void
    {
        $ids = $this->filteredRuns()->pluck('id');

        if ($this->run !== null && $ids->contains($this->run)) {
            return;
        }

        $this->run = $ids->first();
    }

    /**
     * @return Collection<int, Run>
     */
    private function rangedRuns(): Collection
    {
        [$start, $end] = $this->rangeBounds();

        return Run::query()
            ->where('user_id', auth()->id())
            ->when($start, fn ($query) => $query->whereDate('performed_on', '>=', $start->toDateString()))
            ->when($end, fn ($query) => $query->whereDate('performed_on', '<=', $end->toDateString()))
            ->with('splits')
            ->get();
    }

    /**
     * @return Collection<int, Run>
     */
    private function filteredRuns(): Collection
    {
        $term = strtolower(trim($this->search));
        $direction = $this->sort === 'oldest' ? 'asc' : 'desc';

        return $this->rangedRuns()
            ->when($this->activity !== 'all', fn (Collection $runs) => $runs->where('type', $this->activity))
            ->when($term !== '', function (Collection $runs) use ($term): Collection {
                return $runs->filter(function (Run $run) use ($term): bool {
                    return str_contains(strtolower($run->typeLabel()), $term)
                        || str_contains(strtolower($run->notes ?? ''), $term);
                })->values();
            })
            ->sortBy([
                ['performed_on', $direction],
                ['started_at', $direction],
                ['id', $direction],
            ])
            ->values();
    }

    private function clampHistoryPage(int $total): void
    {
        $last = max(1, (int) ceil($total / self::HistoryPerPage));

        if ((int) $this->getPage() > $last) {
            $this->setPage($last);
        }
    }

    /**
     * @param  Collection<int, Run>  $runs
     */
    private function historyPage(Collection $runs): LengthAwarePaginator
    {
        $page = max(1, (int) $this->getPage());

        return new LengthAwarePaginator(
            $runs->forPage($page, self::HistoryPerPage)->values(),
            $runs->count(),
            self::HistoryPerPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );
    }

    /**
     * @param  Collection<int, Run>  $runs
     * @return array<string, mixed>
     */
    private function summary(Collection $runs): array
    {
        $distance = (float) $runs->sum(fn (Run $run): float => (float) $run->distance_miles);
        $seconds = (int) $runs->sum('duration_seconds');
        $calories = (int) $runs->sum(fn (Run $run): int => (int) ($run->calories ?? 0));
        $hasCalories = $runs->contains(fn (Run $run): bool => $run->calories !== null);
        $prior = $this->priorRuns();
        $comparison = $runs;

        if ($this->range === 'all' && $prior->isNotEmpty()) {
            $priorIds = $prior->pluck('id');
            $comparison = $runs->reject(fn (Run $run): bool => $priorIds->contains($run->id))->values();
        }

        $currentDistance = (float) $comparison->sum(fn (Run $run): float => (float) $run->distance_miles);
        $currentSeconds = (int) $comparison->sum('duration_seconds');
        $currentCalories = (int) $comparison->sum(fn (Run $run): int => (int) ($run->calories ?? 0));
        $priorDistance = (float) $prior->sum(fn (Run $run): float => (float) $run->distance_miles);
        $priorSeconds = (int) $prior->sum('duration_seconds');
        $priorCalories = (int) $prior->sum(fn (Run $run): int => (int) ($run->calories ?? 0));

        return [
            'distance' => Run::formatMiles($distance).' mi',
            'time' => Run::formatDuration($seconds),
            'pace' => $distance > 0 ? Run::formatPace($seconds, $distance).' /mi' : '—',
            'calories' => $hasCalories ? number_format($calories) : '—',
            'distanceChange' => $this->percentChange($currentDistance, $priorDistance),
            'timeChange' => $this->percentChange((float) $currentSeconds, (float) $priorSeconds),
            'paceChange' => $this->percentChange(
                $currentDistance > 0 ? $currentSeconds / $currentDistance : 0,
                $priorDistance > 0 ? $priorSeconds / $priorDistance : 0,
            ),
            'calorieChange' => $this->percentChange((float) $currentCalories, (float) $priorCalories),
            'changeLabel' => match ($this->range) {
                'month' => 'vs last month',
                'year' => 'vs last year',
                default => 'vs prior period',
            },
            'distanceBars' => $this->barsFor($runs, fn (Collection $month): float => (float) $month->sum(fn (Run $run): float => (float) $run->distance_miles)),
            'timeBars' => $this->barsFor($runs, fn (Collection $month): float => (float) $month->sum('duration_seconds')),
            'paceBars' => $this->barsFor($runs, function (Collection $month): float {
                $miles = (float) $month->sum(fn (Run $run): float => (float) $run->distance_miles);

                return $miles > 0 ? (float) $month->sum('duration_seconds') / $miles : 0;
            }),
            'calorieBars' => $this->barsFor($runs, fn (Collection $month): float => (float) $month->sum(fn (Run $run): int => (int) ($run->calories ?? 0))),
        ];
    }

    /**
     * @return Collection<int, Run>
     */
    private function priorRuns(): Collection
    {
        $bounds = $this->priorBounds();

        if ($bounds === null) {
            $runs = $this->rangedRuns()->sortBy('performed_on')->values();
            $first = $runs->first()?->performed_on;
            $last = $runs->last()?->performed_on;

            if (! $first instanceof Carbon || ! $last instanceof Carbon || $first->equalTo($last)) {
                return collect();
            }

            $mid = $first->copy()->addDays((int) floor($first->diffInDays($last) / 2));

            return $runs->filter(fn (Run $run): bool => $run->performed_on->lte($mid))->values();
        }

        [$start, $end] = $bounds;

        return Run::query()
            ->where('user_id', auth()->id())
            ->whereDate('performed_on', '>=', $start->toDateString())
            ->whereDate('performed_on', '<', $end->toDateString())
            ->get();
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function rangeBounds(): array
    {
        return match ($this->range) {
            'month' => [now()->copy()->startOfMonth()->startOfDay(), now()->endOfDay()],
            '3m' => [now()->subDays(90)->startOfDay(), now()->endOfDay()],
            '6m' => [now()->subDays(180)->startOfDay(), now()->endOfDay()],
            'year' => [now()->startOfYear(), now()->endOfDay()],
            default => [null, null],
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function priorBounds(): ?array
    {
        return match ($this->range) {
            'month' => [now()->copy()->subMonthNoOverflow()->startOfMonth()->startOfDay(), now()->copy()->subMonthNoOverflow()->addDay()->startOfDay()],
            '3m' => [now()->subDays(180)->startOfDay(), now()->subDays(90)->startOfDay()],
            '6m' => [now()->subDays(360)->startOfDay(), now()->subDays(180)->startOfDay()],
            'year' => [now()->copy()->subYear()->startOfYear(), now()->copy()->subYear()->addDay()->startOfDay()],
            default => null,
        };
    }

    private function percentChange(float $current, float $prior): ?int
    {
        if ($prior <= 0 || $current <= 0) {
            return null;
        }

        return (int) round((($current - $prior) / $prior) * 100);
    }

    /**
     * @param  callable(Collection<int, Run>): float  $value
     * @return list<float>
     */
    private function barsFor(Collection $runs, callable $value): array
    {
        $values = $runs
            ->groupBy(fn (Run $run): string => $run->performed_on->format('Y-m'))
            ->sortKeys()
            ->map(fn (Collection $month): float => $value($month))
            ->take(-8)
            ->values();

        $max = (float) $values->max();

        if ($max <= 0) {
            return [];
        }

        return $values->map(fn (mixed $point): float => round(((float) $point / $max) * 100, 1))->all();
    }

    /**
     * @return list<array{distance: string, duration: int, elevation: ?int}>|null
     */
    private function validatedSplits(): ?array
    {
        $rows = [];

        foreach ($this->splits as $index => $split) {
            $distance = trim((string) ($split['distance'] ?? ''));
            $duration = trim((string) ($split['duration'] ?? ''));
            $elevation = trim((string) ($split['elevation'] ?? ''));

            if ($distance === '' && $duration === '' && $elevation === '') {
                continue;
            }

            $seconds = $this->parseDuration($duration);

            if ($distance === '' || ! is_numeric($distance) || (float) $distance <= 0 || $seconds === null) {
                $this->addError('splits.'.$index, 'Each split needs a distance and a time, like 10:16.');

                return null;
            }

            if ($elevation !== '' && preg_match('/^-?\d+$/', $elevation) !== 1) {
                $this->addError('splits.'.$index, 'Elevation is a whole number of feet.');

                return null;
            }

            $rows[] = [
                'distance' => $distance,
                'duration' => $seconds,
                'elevation' => $elevation === '' ? null : (int) $elevation,
            ];
        }

        return $rows;
    }

    private function parseDuration(string $value): ?int
    {
        $value = trim($value);

        if (preg_match('/^(\d+):([0-5]\d)$/', $value, $matches) === 1) {
            return ((int) $matches[1] * 60) + (int) $matches[2];
        }

        if (preg_match('/^(\d+):([0-5]\d):([0-5]\d)$/', $value, $matches) === 1) {
            return ((int) $matches[1] * 3600) + ((int) $matches[2] * 60) + (int) $matches[3];
        }

        return null;
    }

    private function blankToNull(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array{distance: string, duration: string, elevation: string}
     */
    private function blankSplit(): array
    {
        return ['distance' => '', 'duration' => '', 'elevation' => ''];
    }

    private function resetForm(): void
    {
        $this->editingRun = null;
        $this->performedOn = now()->toDateString();
        $this->startedAt = '';
        $this->type = 'outdoor';
        $this->distance = '';
        $this->duration = '';
        $this->elevationGain = '';
        $this->calories = '';
        $this->averageHeartRate = '';
        $this->maxHeartRate = '';
        $this->temperature = '';
        $this->notes = '';
        $this->splits = [$this->blankSplit()];
        $this->resetErrorBag();
    }

    private function ownedRun(int $runId): Run
    {
        return Run::query()
            ->where('user_id', auth()->id())
            ->with('splits')
            ->findOrFail($runId);
    }
}
