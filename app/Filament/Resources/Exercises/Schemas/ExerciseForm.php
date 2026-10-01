<?php

namespace App\Filament\Resources\Exercises\Schemas;

use App\Models\Workout;
use App\Support\SetMeasure;
use Database\Seeders\ExerciseSeeder;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class ExerciseForm
{
    public static function configure(Schema $schema): Schema
    {
        $groups = array_keys(ExerciseSeeder::library());

        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(100)
                    ->unique(modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where(
                        fn ($query) => $query->where('user_id', $get('user_id')),
                    )),
                Select::make('group')
                    ->options(array_combine($groups, $groups))
                    ->searchable()
                    ->native(false)
                    ->placeholder('No group'),
                Select::make('measure')
                    ->options(SetMeasure::Labels)
                    ->default(SetMeasure::Reps)
                    ->required()
                    ->native(false)
                    ->helperText('Reps keep weight and reps. Time is a duration. Calories is a calorie count.'),
                CheckboxList::make('session_types')
                    ->label('Sessions')
                    ->options(array_combine(Workout::SuggestedTypes, Workout::SuggestedTypes))
                    ->columns(2)
                    ->required()
                    ->helperText('This exercise can be added only during these sessions. A custom exercise stays pending until these are set and an administrator approves it.'),
                Select::make('user_id')
                    ->label('Owner')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Shared library')
                    ->helperText('Leave empty to share this exercise with everyone.'),
            ]);
    }
}
