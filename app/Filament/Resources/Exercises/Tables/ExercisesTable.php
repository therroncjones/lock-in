<?php

namespace App\Filament\Resources\Exercises\Tables;

use App\Filament\Resources\Exercises\ExerciseResource;
use App\Models\Exercise;
use App\Models\Workout;
use App\Support\SetMeasure;
use Database\Seeders\ExerciseSeeder;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExercisesTable
{
    public static function configure(Table $table): Table
    {
        $groups = array_keys(ExerciseSeeder::library());

        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('group')
                    ->placeholder('None')
                    ->sortable(),
                TextColumn::make('measure')
                    ->formatStateUsing(fn (?string $state): string => SetMeasure::label($state))
                    ->sortable(),
                TextColumn::make('session_types')
                    ->label('Sessions')
                    ->badge()
                    ->placeholder('None'),
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->placeholder('Shared library')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->state(fn (Exercise $record): string => $record->approved_at === null ? 'Pending' : 'Approved')
                    ->color(fn (string $state): string => $state === 'Pending' ? 'warning' : 'success'),
            ])
            ->defaultSort('name')
            ->persistFiltersInSession()
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'pending' => $query->whereNull('approved_at'),
                            'approved' => $query->whereNotNull('approved_at'),
                            default => $query,
                        };
                    }),
                SelectFilter::make('group')
                    ->options(array_combine($groups, $groups)),
                SelectFilter::make('measure')
                    ->options(SetMeasure::Labels),
                SelectFilter::make('session')
                    ->label('Session')
                    ->options(array_combine(Workout::SuggestedTypes, Workout::SuggestedTypes))
                    ->query(function (Builder $query, array $data): Builder {
                        $session = $data['value'] ?? null;

                        if (! is_string($session) || $session === '') {
                            return $query;
                        }

                        return $query->whereJsonContains('session_types', $session);
                    }),
            ])
            ->recordUrl(fn (Exercise $record): string => ExerciseResource::getUrl('edit', ['record' => $record]))
            ->recordActions([
                Action::make('approve')
                    ->visible(fn (Exercise $record): bool => $record->approved_at === null)
                    ->requiresConfirmation()
                    ->modalDescription('This adds the exercise to the shared library. Set its sessions and save before approving.')
                    ->action(function (Exercise $record): void {
                        $error = $record->approvalError();

                        if ($error !== null) {
                            Notification::make()->title($error)->danger()->send();

                            return;
                        }

                        $record->approve();

                        Notification::make()->title('Exercise approved')->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Logged sets and one rep maxes for this exercise will be removed.'),
            ]);
    }
}
