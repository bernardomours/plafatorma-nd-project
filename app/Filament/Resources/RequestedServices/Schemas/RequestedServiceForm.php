<?php

namespace App\Filament\Resources\RequestedServices\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Carbon\Carbon;

class RequestedServiceForm
{
    public static function getFormSchema(): array
    {
        return [
            Select::make('therapy_id')
                ->label('Terapia')
                ->relationship('therapy', 'name')
                ->required(),
            Select::make('service_type')
                ->label('Tipo de Atendimento')
                ->options([
                    'clinica' => 'Clínica',
                    'escolar' => 'Escolar',
                    'domiciliar' => 'Domiciliar',
                ])
                ->required(),
            Select::make('month_year')
                ->label('Mês/Ano')
                ->options(function () {
                    $options = [];
                    foreach ([now()->year, now()->addYear()->year] as $year) {
                        for ($month = 1; $month <= 12; $month++) {
                            $date = Carbon::create($year, $month, 1);
                            $options[$date->format('Y-m-d')] = $date->translatedFormat('F \d\e Y');
                        }
                    }
                    return $options;
                })
                ->searchable()
                ->native(false)
                ->required()
                ->afterStateHydrated(function (Select $component, $state) {
                    if ($state instanceof \DateTimeInterface) {
                        $component->state($state->format('Y-m-01'));
                    }
                }),
            TextInput::make('requisition_number')
                ->label('Requisição'),
            TextInput::make('requested_hours')
                ->label('Horas Solicitadas')
                ->required()
                ->numeric(),
            TextInput::make('approved_hours')
                ->label('Horas Aprovadas')
                ->required()
                ->numeric(),
            TextInput::make('planned_hours')
                ->label('Horas Planejadas')
                ->required()
                ->numeric(),
        ];
    }
}
