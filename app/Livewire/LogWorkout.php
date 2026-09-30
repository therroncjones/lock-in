<?php

namespace App\Livewire;

use App\Models\Exercise;
use App\Models\OneRepMax;
use App\Models\Run;
use App\Support\WorkoutText;
use App\Models\Workout;
use App\Models\WorkoutBlock;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Carbon\Carbon;
use Database\Seeders\ExerciseSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Strength')]
class LogWorkout extends Component
{
    #[Url]
    public string $date = '';

    #[Url]
    public string $view = 'day';

    #[Url(as: 'session')]
    public ?int $workoutId = null;

    public string $month = '';

    public string $type = '';

    public string $exerciseQuery = '';

    public string $exerciseGroup = '';

    public bool $showExercisePicker = false;

    public ?int $targetBlockId = null;

    /**
     * @var array<int, string>
     */
    public array $blockNames = [];

    /**
     * @var array<int, bool>
     */
    public array $expanded = [];

    /**
     * @var array<int, bool>
     */
    public array $expandedBlocks = [];

    /**
     * @var array<int, string>
     */
    public array $setWeights = [];

    /**
     * @var array<int, string>
     */
    public array $setReps = [];

    /**
     * @var array<int, string>
     */
    public array $setActualWeights = [];

    /**
     * @var array<int, string>
     */
    public array $setActualReps = [];

    /**
     * @var array<int, string>
     */
    public array $pendingNames = [];

    public function mount(): void
    {
        $this->normalizeDate();
        $this->normalizeView();
        $this->hydrateFromWorkout();
    }

    public function updatedDate(): void
    {
        $this->normalizeDate();
        $this->workoutId = null;
        $this->hydrateFromWorkout();
    }

    public function showToday(): void
    {
        $today = now()->toDateString();

        if ($this->date === $today && $this->view === 'day') {
            return;
        }

        $this->selectDay($today);
    }

    public function showDay(): void
    {
        $this->view = 'day';
    }

    public function showMonth(): void
    {
        $this->view = 'month';
        $this->month = Carbon::parse($this->date)->format('Y-m');
    }

    public function previousMonth(): void
    {
        $this->moveMonth(-1);
    }

    public function nextMonth(): void
    {
        $this->moveMonth(1);
    }

    public function selectDay(string $day): void
    {
        $this->date = $day;
        $this->normalizeDate();
        $this->workoutId = null;
        $this->view = 'day';
        $this->expanded = [];
        $this->expandedBlocks = [];
        $this->showExercisePicker = false;
        $this->resetErrorBag();
        $this->hydrateFromWorkout();
    }

    public function updatedType(): void
    {
        if ($this->changesAreLocked()) {
            $this->hydrateFromWorkout();

            return;
        }

        $this->persistType();
    }

    public function selectSession(int $workoutId): void
    {
        $workout = Workout::query()
            ->whereKey($workoutId)
            ->where('user_id', auth()->id())
            ->whereDate('performed_on', $this->date)
            ->firstOrFail();

        $this->workoutId = $workout->id;
        $this->expanded = [];
        $this->expandedBlocks = [];
        $this->showExercisePicker = false;
        $this->resetErrorBag();
        $this->hydrateFromWorkout();
    }

    public function startSession(): void
    {
        if ($this->changesAreLocked()) {
            return;
        }

        $workout = Workout::query()->create([
            'user_id' => auth()->id(),
            'performed_on' => $this->date,
            'completed_at' => null,
        ]);

        $this->workoutId = $workout->id;
        $this->type = '';
        $this->expanded = [];
        $this->expandedBlocks = [];
        $this->showExercisePicker = false;
        $this->resetErrorBag();
        $this->hydrateFromWorkout();
    }

    public function deleteSession(): void
    {
        $workout = $this->currentWorkout();

        if ($workout === null) {
            return;
        }

        $workout->delete();
        $this->workoutId = null;
        $this->expanded = [];
        $this->expandedBlocks = [];
        $this->showExercisePicker = false;
        $this->resetErrorBag();
        $this->hydrateFromWorkout();
    }

    public function setType(string $type): void
    {
        if ($this->changesAreLocked()) {
            return;
        }

        if (! in_array($type, Workout::SuggestedTypes, true)) {
            return;
        }

        $this->type = $this->type === $type ? '' : $type;
        $this->persistType();
        $this->keepExerciseGroupInRange();
    }

    public function openExercisePicker(?int $blockId = null): void
    {
        if ($this->changesAreLocked()) {
            return;
        }

        if ($blockId !== null) {
            $this->ownedBlock($blockId);
        }

        $this->targetBlockId = $blockId;
        $this->showExercisePicker = true;
    }

    public function closeExercisePicker(): void
    {
        $this->showExercisePicker = false;
        $this->targetBlockId = null;
        $this->exerciseQuery = '';
        $this->exerciseGroup = '';
        $this->resetErrorBag('exerciseQuery');
    }

    public function addBlock(): void
    {
        if ($this->changesAreLocked()) {
            return;
        }

        $workout = $this->ensureWorkout();
        $position = ((int) $workout->blocks()->max('position')) + 1;

        $block = $workout->blocks()->create([
            'name' => $this->nextBlockName($workout),
            'position' => $position,
        ]);

        $this->blockNames[$block->id] = $block->name;
        $this->expandedBlocks = [$block->id => true];
    }

    public function toggleBlock(int $blockId): void
    {
        $this->ownedBlock($blockId);

        $opening = ! ($this->expandedBlocks[$blockId] ?? false);
        $this->expandedBlocks = $opening ? [$blockId => true] : [];
    }

    public function updatedBlockNames(mixed $value, string $key): void
    {
        if ($this->changesAreLocked()) {
            $this->hydrateFromWorkout();

            return;
        }

        $block = $this->ownedBlock((int) $key);
        $this->blockNames[$block->id] = trim((string) $value);

        $this->validate([
            "blockNames.{$block->id}" => ['required', 'string', 'max:100'],
        ]);

        $block->update(['name' => $this->blockNames[$block->id]]);
    }

    public function removeBlock(int $blockId): void
    {
        if ($this->changesAreLocked()) {
            return;
        }

        $block = $this->ownedBlock($blockId);
        $workout = $block->workout;

        $block->delete();
        unset($this->blockNames[$blockId], $this->expandedBlocks[$blockId]);

        $this->renumberExercises($workout);
        $this->hydrateFromWorkout();
    }

    public function assignExercise(int $workoutExerciseId, string $blockId): void
    {
        if ($this->changesAreLocked()) {
            return;
        }

        $entry = $this->ownedExercise($workoutExerciseId);
        $target = $blockId === '' ? null : (int) $blockId;

        if ($target !== null) {
            $block = $this->ownedBlock($target);
            abort_unless($block->workout_id === $entry->workout_id, 404);
        }

        $entry->update(['workout_block_id' => $target]);
        $this->renumberExercises($entry->workout);
        $this->hydrateFromWorkout();
    }

    public function filterGroup(string $group): void
    {
        if ($group === 'all') {
            $this->exerciseGroup = '';

            return;
        }

        if (! in_array($group, $this->pickerGroups(), true)) {
            return;
        }

        $this->exerciseGroup = $this->exerciseGroup === $group ? '' : $group;
    }

    public function addExercise(int $exerciseId): void
    {
        if ($this->changesAreLocked()) {
            return;
        }

        $exercise = Exercise::query()
            ->availableTo(auth()->user())
            ->findOrFail($exerciseId);

        $type = $this->sessionTypeFilter();
        $groups = $this->groupsForSession();
        $assigned = $exercise->session_types ?? [];
        $matchesType = $type !== null && in_array($type, $assigned, true);
        $matchesGroup = $groups !== null && $exercise->group !== null && in_array($exercise->group, $groups, true);
        $customPrivate = $exercise->user_id !== null && $assigned === [];

        if (($type !== null || $groups !== null) && ! $matchesType && ! $matchesGroup && ! $customPrivate) {
            abort(404);
        }

        $workout = $this->ensureWorkout();

        if ($workout->exercises()->where('exercise_id', $exercise->id)->exists()) {
            $this->exerciseQuery = '';

            return;
        }

        $blockId = $this->blockIdForCurrentWorkout($workout);

        $entry = DB::transaction(function () use ($workout, $exercise, $blockId): WorkoutExercise {
            $positionQuery = $workout->exercises();

            if ($blockId === null) {
                $positionQuery->whereNull('workout_block_id');
            } else {
                $positionQuery->where('workout_block_id', $blockId);
            }

            $entry = $workout->exercises()->create([
                'exercise_id' => $exercise->id,
                'workout_block_id' => $blockId,
                'position' => ((int) $positionQuery->max('position')) + 1,
            ]);

            $entry->sets()->create(['position' => 1]);

            return $entry;
        });

        $this->expanded = [$entry->id => true];
        $this->exerciseQuery = '';
        $this->hydrateFromWorkout();
    }

    public function addCustomExercise(): void
    {
        if ($this->changesAreLocked()) {
            return;
        }

        $this->exerciseQuery = trim($this->exerciseQuery);

        $this->validate([
            'exerciseQuery' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $name = $this->exerciseQuery;

        $existing = Exercise::query()
            ->availableTo(auth()->user())
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing instanceof Exercise) {
            $this->addExercise($existing->id);

            return;
        }

        $exercise = Exercise::query()->create([
            'user_id' => auth()->id(),
            'name' => $name,
            'approved_at' => null,
        ]);

        $this->addExercise($exercise->id);
    }

    public function toggleExercise(int $workoutExerciseId): void
    {
        $this->ownedExercise($workoutExerciseId);

        $opening = ! ($this->expanded[$workoutExerciseId] ?? false);
        $this->expanded = $opening ? [$workoutExerciseId => true] : [];
    }

    public function toggleSetCompleted(int $setId): void
    {
        if ($this->changesAreLocked()) {
            return;
        }

        $set = $this->ownedSet($setId);
        $set->update(['completed' => ! $set->completed]);
    }

    public function removeExercise(int $workoutExerciseId): void
    {
        if ($this->changesAreLocked()) {
            return;
        }

        $entry = $this->ownedExercise($workoutExerciseId);
        $entry->load('exercise');
        $workout = $entry->workout;
        $exercise = $entry->exercise;

        $entry->delete();
        unset($this->expanded[$workoutExerciseId]);
        $this->withdrawPendingExercise($exercise);

        $this->renumberExercises($workout);
        $this->clearCompletionIfEmpty();
        $this->hydrateFromWorkout();
    }

    public function updatedPendingNames(mixed $value, string $key): void
    {
        if ($this->changesAreLocked()) {
            return;
        }

        $exercise = Exercise::query()
            ->whereKey((int) $key)
            ->where('user_id', auth()->id())
            ->whereNull('approved_at')
            ->first();

        if (! $exercise instanceof Exercise) {
            return;
        }

        $name = trim((string) $value);

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $this->pendingNames[$exercise->id] = $exercise->name;
            $this->addError('pendingNames.'.$exercise->id, 'Use 2 to 100 characters.');

            return;
        }

        $taken = Exercise::query()
            ->availableTo(auth()->user())
            ->whereKeyNot($exercise->id)
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($taken) {
            $this->pendingNames[$exercise->id] = $exercise->name;
            $this->addError('pendingNames.'.$exercise->id, 'That exercise is already in the library.');

            return;
        }

        $exercise->update(['name' => $name]);
        $this->resetErrorBag('pendingNames.'.$exercise->id);
    }

    public function complete(): void
    {
        $this->persistType();

        $workout = $this->currentWorkout();

        if ($workout === null || ! $workout->exercises()->exists()) {
            $this->addError('complete', 'Add an exercise before completing this workout.');

            return;
        }

        if ($workout->completed_at === null) {
            $workout->update(['completed_at' => now()]);
        }

        $this->showExercisePicker = false;
    }

    public function markIncomplete(): void
    {
        $workout = $this->currentWorkout();

        if ($workout?->completed_at === null) {
            return;
        }

        $workout->update(['completed_at' => null]);
    }

    public function addSet(int $workoutExerciseId): void
    {
        if ($this->changesAreLocked()) {
            return;
        }

        $entry = $this->ownedExercise($workoutExerciseId);
        $position = ((int) $entry->sets()->max('position')) + 1;

        $entry->sets()->create(['position' => $position]);
        $this->hydrateFromWorkout();
    }

    public function removeSet(int $setId): void
    {
        if ($this->changesAreLocked()) {
            return;
        }

        $set = $this->ownedSet($setId);
        $entry = $set->workoutExercise;

        $set->delete();

        $entry->sets()->orderBy('position')->get()->each(function (WorkoutSet $set, int $index): void {
            $set->update(['position' => $index + 1]);
        });

        $this->hydrateFromWorkout();
    }

    public function updatedSetWeights(mixed $value, string $key): void
    {
        $this->saveSet((int) $key);
    }

    public function updatedSetReps(mixed $value, string $key): void
    {
        $this->saveSet((int) $key);
    }

    public function saveSet(int $setId): void
    {
        if ($this->changesAreLocked()) {
            $this->hydrateFromWorkout();

            return;
        }

        $set = $this->ownedSet($setId);

        $this->setWeights[$setId] = trim((string) ($this->setWeights[$setId] ?? ''));
        $this->setReps[$setId] = trim((string) ($this->setReps[$setId] ?? ''));

        $this->validate([
            "setWeights.{$setId}" => ['nullable', 'numeric', 'min:0', 'max:2000'],
            "setReps.{$setId}" => ['nullable', 'integer', 'min:0', 'max:500'],
        ]);

        $set->update([
            'weight' => $this->setWeights[$setId] === '' ? null : $this->setWeights[$setId],
            'reps' => $this->setReps[$setId] === '' ? null : $this->setReps[$setId],
        ]);

        $set->refresh();
        $this->setWeights[$setId] = $this->displayWeight($set->weight);
        $this->setReps[$setId] = $set->reps === null ? '' : (string) $set->reps;
    }

    public function updatedSetActualWeights(mixed $value, string $key): void
    {
        $this->saveSetActuals((int) $key);
    }

    public function updatedSetActualReps(mixed $value, string $key): void
    {
        $this->saveSetActuals((int) $key);
    }

    public function saveSetActuals(int $setId): void
    {
        if ($this->changesAreLocked()) {
            $this->hydrateFromWorkout();

            return;
        }

        $set = $this->ownedSet($setId);

        $this->setActualWeights[$setId] = trim((string) ($this->setActualWeights[$setId] ?? ''));
        $this->setActualReps[$setId] = trim((string) ($this->setActualReps[$setId] ?? ''));

        $this->validate([
            "setActualWeights.{$setId}" => ['nullable', 'numeric', 'min:0', 'max:2000'],
            "setActualReps.{$setId}" => ['nullable', 'integer', 'min:0', 'max:500'],
        ]);

        $set->update([
            'actual_weight' => $this->setActualWeights[$setId] === '' ? null : $this->setActualWeights[$setId],
            'actual_reps' => $this->setActualReps[$setId] === '' ? null : $this->setActualReps[$setId],
        ]);

        $set->refresh();
        $this->setActualWeights[$setId] = $this->displayWeight($set->actual_weight);
        $this->setActualReps[$setId] = $set->actual_reps === null ? '' : (string) $set->actual_reps;
    }

    public function copyShare(): void
    {
        $workout = $this->currentWorkout();

        if ($workout === null) {
            return;
        }

        $text = WorkoutText::from($workout);

        if ($text === '') {
            return;
        }

        $encoded = json_encode($text, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        $this->js(<<<JS
            navigator.clipboard.writeText({$encoded}).then(() => {
                const label = document.querySelector('[data-share-label]');
                if (! label) {
                    return;
                }
                label.textContent = 'Copied';
                setTimeout(() => { label.textContent = 'Share'; }, 2000);
            })
        JS);
    }

    public function render()
    {
        $workout = $this->currentWorkout();
        $workoutExercises = $workout === null
            ? collect()
            : $workout->exercises()->with(['exercise', 'sets'])->orderBy('position')->get();
        $exerciseChoices = $this->exerciseChoices($workout);
        $recentChoices = $this->recentChoices($exerciseChoices);
        $recentIds = $recentChoices->pluck('id')->all();

        return view('livewire.log-workout', [
            'calendar' => $this->view === 'month' ? $this->calendar() : [],
            'sessions' => $this->dayWorkouts(),
            'currentSessionId' => $workout?->id,
            'monthLabel' => $this->monthLabel(),
            'workoutExercises' => $workoutExercises,
            'looseExercises' => $workoutExercises->whereNull('workout_block_id')->values(),
            'blocks' => $workout === null
                ? collect()
                : $workout->blocks()->reorder()->orderBy('name')->get(),
            'targetBlockName' => $this->targetBlockId === null
                ? null
                : $workout?->blocks()->whereKey($this->targetBlockId)->value('name'),
            'exerciseChoices' => $exerciseChoices->reject(fn (Exercise $choice): bool => in_array($choice->id, $recentIds, true))->values(),
            'recentChoices' => $recentChoices,
            'suggestedTypes' => Workout::SuggestedTypes,
            'groups' => $this->pickerGroups(),
            'showsGroupLabels' => ! $this->usesDedicatedExerciseList(),
            'showsRunNote' => $this->showsRunNote(),
            'canAddCustomExercise' => $this->canAddCustomExercise(),
            'formattedDate' => Carbon::parse($this->date)->format('D, M j, Y'),
            'canShare' => $workoutExercises->isNotEmpty(),
            'isComplete' => $workout?->completed_at !== null,
            'createdAt' => $workout?->created_at?->format('M j, Y g:i A'),
            'updatedAt' => $workout?->updated_at?->format('M j, Y g:i A'),
        ]);
    }

    private function changesAreLocked(): bool
    {
        return $this->currentWorkout()?->completed_at !== null;
    }

    private function persistType(): void
    {
        if ($this->changesAreLocked()) {
            return;
        }

        $this->validate([
            'type' => ['nullable', 'string', 'max:100'],
        ]);

        $type = trim($this->type);

        if ($type !== '' && ! in_array($type, Workout::SuggestedTypes, true)) {
            $this->hydrateFromWorkout();

            return;
        }

        if ($type === '' && $this->currentWorkout() === null) {
            return;
        }

        $this->ensureWorkout()->update([
            'type' => $type === '' ? null : $type,
        ]);
        $this->keepExerciseGroupInRange();
    }

    private function ensureWorkout(): Workout
    {
        $workout = $this->currentWorkout();

        if ($workout instanceof Workout) {
            return $workout;
        }

        $workout = Workout::query()->create([
            'user_id' => auth()->id(),
            'performed_on' => $this->date,
            'completed_at' => null,
        ]);
        $this->workoutId = $workout->id;

        return $workout;
    }

    /**
     * @return Collection<int, Workout>
     */
    private function dayWorkouts(): Collection
    {
        return Workout::query()
            ->where('user_id', auth()->id())
            ->whereDate('performed_on', $this->date)
            ->orderBy('id')
            ->get();
    }

    private function currentWorkout(): ?Workout
    {
        $workouts = $this->dayWorkouts();

        if ($this->workoutId !== null) {
            $selected = $workouts->firstWhere('id', $this->workoutId);

            if ($selected instanceof Workout) {
                return $selected;
            }
        }

        return $workouts->last();
    }

    private function hydrateFromWorkout(): void
    {
        $workout = $this->currentWorkout();
        $workout?->load(['exercises.exercise', 'exercises.sets', 'blocks']);
        $this->type = $workout?->type ?? '';
        $this->setWeights = [];
        $this->setReps = [];
        $this->setActualWeights = [];
        $this->setActualReps = [];
        $this->blockNames = [];
        $this->pendingNames = [];

        foreach ($workout?->blocks ?? [] as $block) {
            $this->blockNames[$block->id] = $block->name;
        }

        foreach ($workout?->exercises ?? [] as $exercise) {
            if ($exercise->exercise?->approved_at === null && $exercise->exercise?->user_id === auth()->id()) {
                $this->pendingNames[$exercise->exercise->id] = $exercise->exercise->name;
            }

            foreach ($exercise->sets as $set) {
                $this->setWeights[$set->id] = $this->displayWeight($set->weight);
                $this->setReps[$set->id] = $set->reps === null ? '' : (string) $set->reps;
                $this->setActualWeights[$set->id] = $this->displayWeight($set->actual_weight);
                $this->setActualReps[$set->id] = $set->actual_reps === null ? '' : (string) $set->actual_reps;
            }
        }

        if ($this->expanded === [] && $workout !== null) {
            $first = $workout->exercises->sortBy('position')->first();

            if ($first !== null) {
                $this->expanded[$first->id] = true;
            }
        }

        if ($this->expandedBlocks === [] && $workout !== null) {
            $firstBlock = $workout->blocks->sortBy('name')->first();

            if ($firstBlock !== null) {
                $this->expandedBlocks[$firstBlock->id] = true;
            }
        }
    }

    private function clearCompletionIfEmpty(): void
    {
        $workout = $this->currentWorkout();

        if ($workout !== null && ! $workout->exercises()->exists() && $workout->completed_at !== null) {
            $workout->update(['completed_at' => null]);
        }
    }

    private function nextBlockName(Workout $workout): string
    {
        $used = $workout->blocks()->pluck('name')->all();

        foreach (range('A', 'Z') as $letter) {
            $name = 'Block '.$letter;

            if (! in_array($name, $used, true)) {
                return $name;
            }
        }

        return 'Block '.($workout->blocks()->count() + 1);
    }

    private function blockIdForCurrentWorkout(Workout $workout): ?int
    {
        if ($this->targetBlockId === null) {
            return null;
        }

        $block = $this->ownedBlock($this->targetBlockId);

        return $block->workout_id === $workout->id ? $block->id : null;
    }

    private function renumberExercises(Workout $workout): void
    {
        $workout->unsetRelation('exercises');
        $workout->load('exercises');

        $workout->exercises
            ->groupBy(fn (WorkoutExercise $entry): int => $entry->workout_block_id ?? 0)
            ->each(function ($entries): void {
                $entries->sortBy('position')->values()->each(function (WorkoutExercise $entry, int $index): void {
                    $position = $index + 1;

                    if ($entry->position !== $position) {
                        $entry->update(['position' => $position]);
                    }
                });
            });
    }

    private function ownedBlock(int $blockId): WorkoutBlock
    {
        return WorkoutBlock::query()
            ->whereKey($blockId)
            ->whereHas('workout', fn ($query) => $query->where('user_id', auth()->id()))
            ->firstOrFail();
    }

    private function ownedExercise(int $workoutExerciseId): WorkoutExercise
    {
        return WorkoutExercise::query()
            ->whereKey($workoutExerciseId)
            ->whereHas('workout', fn ($query) => $query->where('user_id', auth()->id()))
            ->firstOrFail();
    }

    private function ownedSet(int $setId): WorkoutSet
    {
        return WorkoutSet::query()
            ->whereKey($setId)
            ->whereHas('workoutExercise.workout', fn ($query) => $query->where('user_id', auth()->id()))
            ->firstOrFail();
    }

    private function sessionTypeFilter(): ?string
    {
        $type = trim($this->type);

        if (! in_array($type, Workout::SuggestedTypes, true)) {
            return null;
        }

        return $type;
    }

    private function usesDedicatedExerciseList(): bool
    {
        return in_array(trim($this->type), ['Cardio', 'Bodyweight', 'Conditioning', 'Mobility'], true);
    }

    /**
     * @return list<string>|null
     */
    private function groupsForSession(): ?array
    {
        $type = trim($this->type);

        if ($type === '' || $this->sessionTypeFilter() !== null) {
            return null;
        }

        $library = array_keys(ExerciseSeeder::library());

        return in_array($type, $library, true) ? [$type] : $library;
    }

    /**
     * @return list<string>
     */
    private function pickerGroups(): array
    {
        $type = trim($this->type);

        if (isset(Workout::TypeGroups[$type])) {
            return Workout::TypeGroups[$type];
        }

        if ($this->usesDedicatedExerciseList()) {
            return [];
        }

        $library = array_keys(ExerciseSeeder::library());
        $allowed = $this->groupsForSession();

        if ($allowed === null) {
            return array_values(array_diff($library, ['Mobility']));
        }

        return array_values(array_intersect($library, $allowed));
    }

    private function showsRunNote(): bool
    {
        return trim($this->type) === 'Cardio' || in_array('Cardio', $this->pickerGroups(), true);
    }

    private function keepExerciseGroupInRange(): void
    {
        if ($this->exerciseGroup !== '' && ! in_array($this->exerciseGroup, $this->pickerGroups(), true)) {
            $this->exerciseGroup = '';
        }
    }

    private function withdrawPendingExercise(?Exercise $exercise): void
    {
        if (! $exercise instanceof Exercise || $exercise->approved_at !== null || $exercise->user_id !== auth()->id()) {
            return;
        }

        $stillUsed = WorkoutExercise::query()->where('exercise_id', $exercise->id)->exists()
            || OneRepMax::query()->where('exercise_id', $exercise->id)->exists();

        if ($stillUsed) {
            return;
        }

        unset($this->pendingNames[$exercise->id]);
        $exercise->delete();
    }

    /**
     * @param  Collection<int, Exercise>  $choices
     * @return \Illuminate\Support\Collection<int, Exercise>
     */
    private function recentChoices(Collection $choices): \Illuminate\Support\Collection
    {
        if (trim($this->exerciseQuery) !== '') {
            return collect();
        }

        $recentIds = WorkoutExercise::query()
            ->select('workout_exercises.exercise_id')
            ->join('workouts', 'workouts.id', '=', 'workout_exercises.workout_id')
            ->where('workouts.user_id', auth()->id())
            ->groupBy('workout_exercises.exercise_id')
            ->orderByRaw('max(workouts.performed_on) desc')
            ->orderByRaw('max(workout_exercises.id) desc')
            ->limit(8)
            ->pluck('workout_exercises.exercise_id');

        return $recentIds
            ->map(fn (mixed $id): ?Exercise => $choices->firstWhere('id', $id))
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, Exercise>
     */
    private function exerciseChoices(?Workout $workout): Collection
    {
        $addedIds = $workout?->exercises()->pluck('exercise_id')->all() ?? [];
        $query = trim($this->exerciseQuery);

        return Exercise::query()
            ->availableTo(auth()->user())
            ->when($addedIds !== [], fn ($builder) => $builder->whereNotIn('id', $addedIds))
            ->when($query !== '', fn ($builder) => $builder->where('name', 'like', '%'.$query.'%'))
            ->when($query === '' && $this->exerciseGroup !== '', fn ($builder) => $builder->where('group', $this->exerciseGroup))
            ->when($query === '' && $this->exerciseGroup === '', function ($builder): void {
                $builder->where(function ($query): void {
                    $query->whereNull('user_id')->orWhere(function ($query): void {
                        $query->where('user_id', auth()->id())->whereNull('approved_at');
                    });
                });
            })
            ->when($this->sessionTypeFilter() !== null, function ($builder): void {
                $type = $this->sessionTypeFilter();
                $searching = trim($this->exerciseQuery) !== '';

                $builder->where(function ($query) use ($type, $searching): void {
                    $query->whereJsonContains('session_types', $type);

                    if ($searching) {
                        $query->orWhere(function ($query): void {
                            $query->where('user_id', auth()->id())->whereNull('approved_at');
                        });
                    }
                });
            })
            ->when($this->groupsForSession() !== null, fn ($builder) => $builder->whereIn('group', $this->groupsForSession()))
            ->whereNotIn('name', array_values(Run::Types))
            ->orderBy('name')
            ->get();
    }

    private function canAddCustomExercise(): bool
    {
        $name = trim($this->exerciseQuery);

        if (mb_strlen($name) < 2) {
            return false;
        }

        return ! Exercise::query()
            ->availableTo(auth()->user())
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->exists();
    }

    private function normalizeDate(): void
    {
        $parsed = date_create_from_format('Y-m-d', $this->date);

        if ($parsed === false || $parsed->format('Y-m-d') !== $this->date) {
            $this->date = now()->toDateString();
        }
    }

    private function normalizeView(): void
    {
        if (! in_array($this->view, ['day', 'month'], true)) {
            $this->view = 'day';
        }

        if ($this->view === 'month' && $this->month === '') {
            $this->month = Carbon::parse($this->date)->format('Y-m');
        }
    }

    private function moveMonth(int $by): void
    {
        $this->view = 'month';

        if ($this->month === '') {
            $this->month = Carbon::parse($this->date)->format('Y-m');
        }

        $this->month = Carbon::createFromFormat('Y-m-d', $this->month.'-01')->addMonths($by)->format('Y-m');
    }

    private function monthLabel(): string
    {
        $month = $this->month !== '' ? $this->month : Carbon::parse($this->date)->format('Y-m');

        return Carbon::createFromFormat('Y-m-d', $month.'-01')->format('F Y');
    }

    /**
     * @return list<array{date: string, number: string, inMonth: bool, today: bool, selected: bool, type: ?string, logged: bool, completed: bool, label: string}>
     */
    private function calendar(): array
    {
        if ($this->month === '') {
            $this->month = Carbon::parse($this->date)->format('Y-m');
        }

        $start = Carbon::createFromFormat('Y-m-d', $this->month.'-01')->startOfMonth();
        $gridStart = $start->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $start->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $workouts = Workout::query()
            ->where('user_id', auth()->id())
            ->whereDate('performed_on', '>=', $gridStart->toDateString())
            ->whereDate('performed_on', '<=', $gridEnd->toDateString())
            ->withCount('exercises')
            ->get()
            ->groupBy(fn (Workout $workout): string => $workout->performed_on->toDateString());

        $days = [];
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $key = $cursor->toDateString();
            $dayWorkouts = $workouts->get($key, collect())
                ->filter(fn (Workout $workout): bool => $workout->exercises_count > 0 || $workout->completed_at !== null)
                ->values();
            $label = $cursor->format('M j');
            $type = null;

            if ($dayWorkouts->isEmpty()) {
                $label .= ', no workout';
            } elseif ($dayWorkouts->count() === 1) {
                $type = $dayWorkouts->first()->type;
                $label .= ', '.($type ?: 'workout');
                $label .= $dayWorkouts->first()->completed_at === null ? ', not completed' : ', completed';
            } else {
                $type = $dayWorkouts->count().' sessions';
                $label .= ', '.$type;
                $label .= $dayWorkouts->contains(fn (Workout $workout): bool => $workout->completed_at === null)
                    ? ', not completed'
                    : ', completed';
            }

            $days[] = [
                'date' => $key,
                'number' => $cursor->format('j'),
                'inMonth' => $cursor->month === $start->month,
                'today' => $cursor->isToday(),
                'selected' => $key === $this->date,
                'type' => $type,
                'logged' => $dayWorkouts->isNotEmpty(),
                'completed' => $dayWorkouts->isNotEmpty() && $dayWorkouts->every(fn (Workout $workout): bool => $workout->completed_at !== null),
                'label' => $label,
            ];

            $cursor->addDay();
        }

        return $days;
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
