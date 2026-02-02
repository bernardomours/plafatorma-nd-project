<?php

namespace App\Filament\Resources\Patients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PatientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),
                DatePicker::make('birth_date')
                    ->label('Data de Nascimento')
                    ->required(),
                TextInput::make('cpf')
                    ->label('CPF')
                    ->required()
                    ->helperText('Coloque apenas os dígitos'),
                TextInput::make('agreement_number')
                    ->label('Carteira')
                    ->required(),
                TextInput::make('guardian_name')
                    ->label('Nome do Responsável')
                    ->helperText('Caso não possua esse dado, pode deixar em branco'),
                TextInput::make('guardian_phone')
                    ->label('Contato do Responsável')
                    ->helperText('Caso não possua esse dado, pode deixar em branco')
                    ->tel(),
                Select::make('unit_id')
                    ->label('Unidade')
                    ->relationship('unit', 'city')
                    ->required(),
                Select::make('agreement_id')
                    ->label('Convênio')
                    ->relationship(
                        name: 'agreement', 
                        titleAttribute: 'name',
                    )
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
            ]);
    }
}
