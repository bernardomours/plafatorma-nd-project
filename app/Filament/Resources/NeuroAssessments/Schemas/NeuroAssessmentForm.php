<?php

namespace App\Filament\Resources\NeuroAssessments\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class NeuroAssessmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações da Avaliação')
                    ->description('Defina o paciente e o profissional responsável pelas 10 sessões.')
                    ->schema([
                        Select::make('patient_id')
                            ->relationship('patient', 'name')
                            ->label('Paciente')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('professional_id')
                            ->relationship('professional', 'name')
                            ->label('Profissional Responsável')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Grid::make(2)->schema([
                            Select::make('status')
                                ->label('Status da Avaliação')
                                ->hiddenOn('create')
                                ->options([
                                    'Em andamento' => 'Em andamento',
                                    'Concluída' => 'Concluída',
                                    'Cancelada' => 'Cancelada',
                                ])
                                ->native(false)
                                ->required()
                                ->default('Em andamento'),

                            TextInput::make('current_session')
                                ->label('Sessão Atual')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(10)
                                ->readOnly()
                                ->default(0)
                                ->hiddenOn('create')
                                ->helperText('Avança de 1 a 10.'),
                        ]),
                    ])
            ]);
    }
}