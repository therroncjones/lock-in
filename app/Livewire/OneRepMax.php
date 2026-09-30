<?php

namespace App\Livewire;

use App\Models\Exercise;
use App\Models\OneRepMax as OneRepMaxRecord;
use App\Support\CoreLifts;
use App\Support\PlateMath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('One Rep Max')]
class OneRepMax extends Component
{
    use WithPagination;

    private const RecentPerPage = 10;

    /**
     * @var list<string>
     */
    private const LiftNames = [
        'Barbell Back Squat',
        'Bench Press',
        'Conventional Deadlift',
    ];

    /**
     * @var list<int>
     */
    private const Percents = [20, 30, 40, 50, 60, 70, 80, 90, 100];

    /**
     * @var array<int, string>
     */
    public const Bars = [
        44 => "Men's Bar (44 lb)",
        33 => "Women's Bar (33 lb)",
        45 => 'Trap Bar (45 lb)',
        25 => 'EZ Curl (25 lb)',
        0 => 'No bar',
    ];

    /**
     * @var array<int, string>
     */
    public const CommonBars = [
        44 => "Men's (44 lb)",
        33 => "Women's (33 lb)",
        45 => 'Trap (45 lb)',
        25 => 'EZ Curl (25 lb)',
    ];

    /**
     * @var list<string>
     */
    private const PlateSizes = ['45', '35', '25', '15', '10', '5', '2.5'];

    /**
     * @var array<int, string>
     */
    public array $weights = [];

    /**
     * @var array<int, string>
     */
    public array $customPercents = [];

    /**
     * @var array<int, string>
     */
    public array $loads = [];

    public string $barWeight = '44';

    public ?int $openExerciseId = null;

    public bool $showingHowItWorks = false;

    public bool $showingAllEstimates = false;

    public ?int $addingExerciseId = null;

    public string $newWeight = '';

    public string $newDate = '';

    public function mount(): void
    {
        $this->normalizeBar();
        $this->loadWeights();

        foreach ($this->exercises() as $exercise) {
            $this->loads[$exercise->id] ??= '70';
            $this->openExerciseId ??= $exercise->id;
        }

        $lift = (int) request()->query('lift');

        if ($lift > 0 && $this->exercises()->contains('id', $lift)) {
            $this->openExerciseId = $lift;
        }
    }

    public function selectBar(int $weight): void
    {
        $this->barWeight = (string) $weight;
        $this->normalizeBar();
    }

    public function openExercise(int $exerciseId): void
    {
        $this->ownedLift($exerciseId);
        $this->openExerciseId = $exerciseId;
    }

    public function selectPercent(int $exerciseId, string $key): void
    {
        $this->ownedLift($exerciseId);
        $this->loads[$exerciseId] = $key;
    }

    public function toggleHowItWorks(): void
    {
        $this->showingHowItWorks = ! $this->showingHowItWorks;
    }

    public function toggleEstimates(): void
    {
        $this->showingAllEstimates = ! $this->showingAllEstimates;
        $this->resetPage();
    }

    public function updatedBarWeight(): void
    {
        $this->normalizeBar();
    }

    public function updatedCustomPercents(mixed $value, string $key): void
    {
        $this->customPercents[$key] = trim((string) $value);

        $this->validate([
            "customPercents.{$key}" => ['nullable', 'numeric', 'min:1', 'max:100'],
        ]);

        if ($this->customPercents[$key] === '' || ! is_numeric($this->customPercents[$key])) {
            return;
        }

        $percent = (float) $this->customPercents[$key];
        $this->loads[$key] = $this->isStandardPercent($percent) ? (string) (int) $percent : 'custom';
    }

    public function updatedWeights(mixed $value, ?string $key = null): void
    {
        if ($key === null) {
            if (! is_array($value)) {
                return;
            }

            $allowed = Exercise::query()
                ->whereNull('user_id')
                ->whereIn('name', self::LiftNames)
                ->pluck('id');

            foreach (array_keys($value) as $exerciseId) {
                if ($allowed->contains((int) $exerciseId)) {
                    $this->saveWeight((int) $exerciseId);
                }
            }

            return;
        }

        $this->saveWeight((int) $key);
    }

    public function openNewMax(int $exerciseId): void
    {
        $this->ownedLift($exerciseId);
        $this->addingExerciseId = $exerciseId;
        $this->openExerciseId = $exerciseId;
        $this->newDate = now()->toDateString();
        $this->newWeight = '';
        $this->resetErrorBag(['newWeight', 'newDate']);
    }

    public function deleteMax(int $maxId): void
    {
        $max = OneRepMaxRecord::query()
            ->where('user_id', auth()->id())
            ->findOrFail($maxId);
        $exerciseId = $max->exercise_id;
        $max->delete();

        $this->redirect(route('one-rep-max', ['lift' => $exerciseId]));
    }

    public function closeNewMax(): void
    {
        $this->addingExerciseId = null;
        $this->newWeight = '';
        $this->newDate = '';
        $this->resetErrorBag(['newWeight', 'newDate']);
    }

    public function saveNewMax(): void
    {
        $exercise = $this->ownedLift((int) $this->addingExerciseId);
        $this->newWeight = trim($this->newWeight);

        $this->validate([
            'newDate' => ['required', 'date', 'before_or_equal:today'],
            'newWeight' => ['required', 'numeric', 'gt:0', 'max:2000'],
        ]);

        $date = Carbon::parse($this->newDate)->toDateString();
        $existing = OneRepMaxRecord::query()
            ->where('user_id', auth()->id())
            ->where('exercise_id', $exercise->id)
            ->whereDate('recorded_on', $date)
            ->first();

        if ($existing) {
            $existing->update(['weight' => $this->newWeight]);
        } else {
            OneRepMaxRecord::query()->create([
                'user_id' => auth()->id(),
                'exercise_id' => $exercise->id,
                'recorded_on' => $date,
                'weight' => $this->newWeight,
            ]);
        }

        $this->redirect(route('one-rep-max', ['lift' => $exercise->id]));
    }

    public function saveWeight(int $exerciseId): void
    {
        $exercise = $this->ownedLift($exerciseId);

        $this->weights[$exerciseId] = trim((string) ($this->weights[$exerciseId] ?? ''));

        $this->validate([
            "weights.{$exerciseId}" => ['nullable', 'numeric', 'min:0', 'max:2000'],
        ]);

        $today = now()->toDateString();

        if ($this->weights[$exerciseId] === '') {
            OneRepMaxRecord::query()
                ->where('user_id', auth()->id())
                ->where('exercise_id', $exercise->id)
                ->whereDate('recorded_on', $today)
                ->delete();

            $this->weights[$exerciseId] = $this->displayWeight($this->latestMax($exercise->id)?->weight);

            return;
        }

        $latest = $this->latestMax($exercise->id);

        if ($latest && $latest->recorded_on->toDateString() !== $today && $this->displayWeight($latest->weight) === $this->displayWeight($this->weights[$exerciseId])) {
            $this->weights[$exerciseId] = $this->displayWeight($latest->weight);

            return;
        }

        $max = OneRepMaxRecord::query()
            ->where('user_id', auth()->id())
            ->where('exercise_id', $exercise->id)
            ->whereDate('recorded_on', $today)
            ->first();

        if ($max) {
            $max->update([
                'weight' => $this->weights[$exerciseId],
            ]);
        } else {
            $max = OneRepMaxRecord::query()->create([
                'user_id' => auth()->id(),
                'exercise_id' => $exercise->id,
                'recorded_on' => $today,
                'weight' => $this->weights[$exerciseId],
            ]);
        }

        $max->refresh();
        $this->weights[$exerciseId] = $this->displayWeight($max->weight);
    }

    public function render()
    {
        $this->normalizeBar();
        $exercises = $this->exercises();
        $histories = $this->histories($exercises);
        $openId = $exercises->contains('id', $this->openExerciseId)
            ? $this->openExerciseId
            : $exercises->first()?->id;
        $loads = [];

        if ($openId) {
            $rows = $this->rowsFor($openId);
            $loads[$openId] = [
                'rows' => $rows,
                'selected' => $this->selectedRow($openId, $rows),
            ];
        }

        $recent = $this->recent($exercises, $histories);

        return view('livewire.one-rep-max', [
            'exercises' => $exercises,
            'bars' => self::Bars,
            'commonBars' => self::CommonBars,
            'loadTables' => $loads,
            'assessments' => $this->assessments($exercises, $histories),
            'recent' => $recent,
            'openId' => $openId,
            'addingExercise' => $exercises->firstWhere('id', $this->addingExerciseId),
        ]);
    }

    /**
     * @param  Collection<int, Exercise>  $exercises
     * @param  array<int, list<array{date: string, weight: string, raw: float, sort: float, change: ?array{text: string, up: bool}}>>  $histories
     * @return array<int, array{date: ?string, weight: ?string, change: ?array{text: string, up: bool}, history: list<array{date: string, weight: string, raw: float, sort: float, change: ?array{text: string, up: bool}}>}>
     */
    private function assessments(Collection $exercises, array $histories): array
    {
        $assessments = [];

        foreach ($exercises as $exercise) {
            $history = $histories[$exercise->id] ?? [];

            $assessments[$exercise->id] = [
                'date' => $history[0]['date'] ?? null,
                'weight' => $history[0]['weight'] ?? null,
                'change' => $history[0]['change'] ?? null,
                'history' => $history,
            ];
        }

        return $assessments;
    }

    /**
     * @param  Collection<int, Exercise>  $exercises
     * @return array<int, list<array{date: string, weight: string, raw: float, sort: float, change: ?array{text: string, up: bool}}>>
     */
    private function histories(Collection $exercises): array
    {
        $records = OneRepMaxRecord::query()
            ->where('user_id', auth()->id())
            ->whereIn('exercise_id', $exercises->pluck('id'))
            ->orderByDesc('recorded_on')
            ->orderByDesc('id')
            ->get()
            ->groupBy('exercise_id');

        $histories = [];

        foreach ($exercises as $exercise) {
            $rows = ($records->get($exercise->id) ?? collect())->values();
            $entries = [];

            foreach ($rows as $index => $max) {
                $previous = $rows[$index + 1] ?? null;
                $entries[] = [
                    'id' => $max->id,
                    'date' => $max->recorded_on->format('M j, Y'),
                    'weight' => $this->displayWeight($max->weight),
                    'raw' => (float) $max->weight,
                    'sort' => $max->recorded_on->timestamp + ($max->id / 100000),
                    'change' => $this->changeBetween(
                        (float) $max->weight,
                        $previous instanceof OneRepMaxRecord ? (float) $previous->weight : null,
                    ),
                ];
            }

            $histories[$exercise->id] = $entries;
        }

        return $histories;
    }

    /**
     * @param  Collection<int, Exercise>  $exercises
     * @param  array<int, list<array{date: string, weight: string, raw: float, sort: float, change: ?array{text: string, up: bool}}>>  $histories
     */
    private function recent(Collection $exercises, array $histories): LengthAwarePaginator
    {
        $rows = collect();

        foreach ($exercises as $exercise) {
            $entries = $histories[$exercise->id] ?? [];

            if ($entries === []) {
                continue;
            }

            foreach ($this->showingAllEstimates ? $entries : array_slice($entries, 0, 1) as $entry) {
                $rows->push([
                    'id' => $entry['id'],
                    'name' => $exercise->name,
                    'date' => $entry['date'],
                    'weight' => $entry['weight'],
                    'change' => $entry['change'],
                    'sort' => $entry['sort'],
                ]);
            }
        }

        $rows = $rows->sortByDesc('sort')->values();
        $perPage = $this->showingAllEstimates ? self::RecentPerPage : max(1, $rows->count());
        $last = max(1, (int) ceil($rows->count() / $perPage));
        $page = min($last, max(1, (int) $this->getPage()));

        if ($this->showingAllEstimates && (int) $this->getPage() !== $page) {
            $this->setPage($page);
        }

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );
    }

    /**
     * @return ?array{text: string, up: bool}
     */
    private function changeBetween(?float $current, ?float $previous): ?array
    {
        if ($current === null || $previous === null) {
            return null;
        }

        $delta = round($current - $previous, 2);

        if ($delta == 0.0) {
            return null;
        }

        return [
            'text' => ($delta > 0 ? '+' : '-').$this->displayWeight(abs($delta)).' lbs',
            'up' => $delta > 0,
        ];
    }

    private function ownedLift(int $exerciseId): Exercise
    {
        return Exercise::query()
            ->availableTo(auth()->user())
            ->whereNull('user_id')
            ->whereIn('name', self::LiftNames)
            ->findOrFail($exerciseId);
    }

    /**
     * @return Collection<int, Exercise>
     */
    private function exercises(): Collection
    {
        CoreLifts::ensure();

        $exercises = Exercise::query()
            ->availableTo(auth()->user())
            ->whereNull('user_id')
            ->whereIn('name', self::LiftNames)
            ->get();

        return new Collection(
            collect(self::LiftNames)
                ->map(fn (string $name) => $exercises->first(fn (Exercise $exercise) => $exercise->name === $name))
                ->filter()
                ->values()
                ->all()
        );
    }

    private function isStandardPercent(float $percent): bool
    {
        return fmod($percent, 1.0) === 0.0 && in_array((int) $percent, self::Percents, true);
    }

    private function normalizeBar(): void
    {
        if ($this->barWeight === '') {
            $this->barWeight = '44';
        }

        if (! array_key_exists((int) $this->barWeight, self::Bars)) {
            $this->barWeight = '44';
        }
    }

    /**
     * @return list<array{key: string, label: string, target: string, loaded: string, plates: array<string, int>, plateCounts: list<array{label: string, count: int}>}>
     */
    private function rowsFor(int $exerciseId): array
    {
        $max = (float) ($this->weights[$exerciseId] ?? 0);

        if ($max <= 0) {
            return [];
        }

        $rows = [];

        foreach (self::Percents as $percent) {
            $rows[] = $this->loadRow($max, (float) $percent, (string) $percent);
        }

        $custom = trim((string) ($this->customPercents[$exerciseId] ?? ''));

        if ($custom !== '' && is_numeric($custom)) {
            $percent = (float) $custom;

            if ($percent >= 1 && $percent <= 100 && ! $this->isStandardPercent($percent)) {
                $rows[] = $this->loadRow($max, $percent, 'custom');
            }
        }

        return $rows;
    }

    /**
     * @param  list<array{key: string, label: string, target: string, loaded: string, plates: array<string, int>, plateCounts: list<array{label: string, count: int}>}>  $rows
     * @return array{key: string, label: string, target: string, loaded: string, plates: array<string, int>, plateCounts: list<array{label: string, count: int}>}|null
     */
    private function selectedRow(int $exerciseId, array $rows): ?array
    {
        $key = (string) ($this->loads[$exerciseId] ?? '70');

        foreach ($rows as $row) {
            if ($row['key'] === $key) {
                return $row;
            }
        }

        foreach ($rows as $row) {
            if ($row['key'] === '70') {
                return $row;
            }
        }

        return $rows[0] ?? null;
    }

    /**
     * @return array{key: string, label: string, target: string, loaded: string, plates: array<string, int>, plateCounts: list<array{label: string, count: int}>}
     */
    private function loadRow(float $max, float $percent, string $key): array
    {
        $target = PlateMath::target($max, $percent);
        $plates = PlateMath::platesPerSide($target, (int) $this->barWeight);

        return [
            'key' => $key,
            'label' => $this->displayWeight($percent).'%',
            'target' => $this->displayWeight($target),
            'loaded' => $this->displayWeight(PlateMath::loaded($target, (int) $this->barWeight)),
            'plates' => $plates,
            'plateCounts' => $this->plateCounts($plates),
        ];
    }

    /**
     * @param  array<string, int>  $plates
     * @return list<array{label: string, count: int}>
     */
    private function plateCounts(array $plates): array
    {
        $counts = [];

        foreach (self::PlateSizes as $size) {
            $count = $plates[$size.' lb'] ?? 0;

            if ($count < 1) {
                continue;
            }

            $counts[] = [
                'size' => $size,
                'label' => $size.' lb',
                'count' => $count,
            ];
        }

        return $counts;
    }

    private function loadWeights(): void
    {
        $this->weights = [];

        $maxes = OneRepMaxRecord::query()
            ->where('user_id', auth()->id())
            ->orderBy('recorded_on')
            ->orderBy('id')
            ->get();

        foreach ($maxes as $max) {
            $this->weights[$max->exercise_id] = $this->displayWeight($max->weight);
        }
    }

    private function latestMax(int $exerciseId): ?OneRepMaxRecord
    {
        return OneRepMaxRecord::query()
            ->where('user_id', auth()->id())
            ->where('exercise_id', $exerciseId)
            ->orderByDesc('recorded_on')
            ->orderByDesc('id')
            ->first();
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
