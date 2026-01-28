<?php

namespace App\Filament\Resources\Schedules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;

class ScheduleForm
{
    /**
     * @return array
     */
    public static function getSchema(): array
    {
        return [
            Select::make('day_of_week')
                ->label('Dia da Semana')
                ->options([
                    'segunda' => 'Segunda-feira',
                    'terca' => 'Terça-feira',
                    'quarta' => 'Quarta-feira',
                    'quinta' => 'Quinta-feira',
                    'sexta' => 'Sexta-feira',
                ])
                ->required(),
            TimePicker::make('start_time')
                ->label('Hora de Início')
                ->required(),
            TimePicker::make('end_time')
                ->label('Hora de Fim')
                ->required(),
            Select::make('professional_id')
                ->relationship('professional', 'name')
                ->required(),
            Select::make('therapy_id')
                ->relationship('therapy', 'name')
                ->required(),
            Select::make('type_therapy')
                ->options([
                    'clinica' => 'Clínica',
                    'escolar' => 'Escolar',
                    'domiciliar' => 'Domiciliar',
                ])
                ->required(),
        ];
    }
}
