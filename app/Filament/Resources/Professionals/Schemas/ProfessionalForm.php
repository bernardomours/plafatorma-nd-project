<?php

namespace App\Filament\Resources\Professionals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use App\Rules\CpfValidate;

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
                    ->required()
                    ->mask('999.999.999-99')
                    ->rule(new CpfValidate())
                    ->maxLength(14)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Esse CPF já está cadastrado.'
                    ])
                    ->helperText('Digite apenas os números, o sistema formata automaticamente.'),
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
                Select::make('role')
                    ->label('Função / Cargo')
                    ->options([
                        'therapist'   => 'AT',
                        'supervisor'  => 'Supervisor',
                        'coordinator' => 'Coordenador',
                    ])
                    ->default('therapist')
                    ->required()
                    ->native(false),
                Select::make('therapies')
                    ->label('Especialidade(s)')
                    ->relationship('therapies', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                Select::make('units')
                    ->label('Unidades')
                    ->preload()
                    ->relationship('units', 'city')
                    ->multiple()
            ]);
    }
}
