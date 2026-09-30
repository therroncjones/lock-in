<?php

namespace App\Filament\Resources\Exercises\Pages;

use App\Filament\Resources\Exercises\ExerciseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExercise extends CreateRecord
{
    protected static string $resource = ExerciseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('index');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['approved_at'] = now();

        return $data;
    }
}
