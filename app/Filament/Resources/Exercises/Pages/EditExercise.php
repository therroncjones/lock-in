<?php

namespace App\Filament\Resources\Exercises\Pages;

use App\Filament\Resources\Exercises\ExerciseResource;
use App\Models\Exercise;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditExercise extends EditRecord
{
    protected static string $resource = ExerciseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->visible(fn (): bool => $this->getRecord()->approved_at === null)
                ->requiresConfirmation()
                ->modalDescription('This adds the exercise to the shared library. Save the sessions first.')
                ->action(function (): void {
                    /** @var Exercise $exercise */
                    $exercise = $this->getRecord();

                    $error = $exercise->approvalError();

                    if ($error !== null) {
                        Notification::make()->title($error)->danger()->send();

                        return;
                    }

                    $exercise->approve();

                    Notification::make()->title('Exercise approved')->success()->send();
                }),
            DeleteAction::make()
                ->modalDescription('Logged sets and one rep maxes for this exercise will be removed.'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('index');
    }
}
