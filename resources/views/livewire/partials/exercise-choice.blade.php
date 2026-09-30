<li wire:key="choice-{{ $choice->id }}" class="flex items-center gap-3 py-2.5">
    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-neutral-900 text-white">
        <x-app-icon name="dumbbell" class="size-5" />
    </span>
    <span class="min-w-0 flex-1">
        <span class="block truncate font-semibold">{{ $choice->name }}</span>
        @if ($choice->approved_at === null)
            <span class="text-sm text-amber-700">Pending approval</span>
        @elseif ($showsGroupLabels && $choice->group)
            <span class="text-sm text-neutral-500">{{ $choice->group }}</span>
        @endif
    </span>
    <button type="button" wire:click="addExercise({{ $choice->id }})" class="inline-flex h-8 shrink-0 items-center justify-center rounded-full px-3 text-sm font-semibold text-[#2f6bff] ring-1 ring-[#2f6bff]/40 hover:bg-[#e8f0ff]">
        Add
    </button>
</li>
