<?php

namespace App\Filament\Resources\Professionals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProfessionalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),
                TextInput::make('cpf')
                    ->label('CPF')
                    ->required(),
                TextInput::make('phone')
                    ->label('Telefone')
                    ->placeholder('84999999999')
                    ->tel()
                    ->required(),
                DatePicker::make('birth_date')
                    ->label('Data de Nascimento')
                    ->required(),
                TextInput::make('register_number')
                    ->label('Número de Registro'),
                TextInput::make('email')
                    ->label('Email'),
                Select::make('therapy_id')
                    ->label('Especialidade')
                    ->relationship('therapy', 'name')
                    ->required(),
                Select::make('unit_id')
                    ->label('Unidade')
                    ->relationship('unit', 'city')
                    ->searchable() 
                    ->preload(),
            ]);
    }
}
