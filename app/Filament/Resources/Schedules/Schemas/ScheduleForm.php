<?php

namespace App\Filament\Resources\Schedules\Schemas;

use App\Models\Professional;
use App\Models\ServiceType;
use App\Models\Therapy;
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
                ->seconds(false)
                ->required(),
            TimePicker::make('end_time')
                ->label('Hora de Fim')
                ->seconds(false)
                ->required(),
            Select::make('professional_id')
                ->label('Profissional')
                ->options(Professional::all()->pluck('name', 'id')) // Changed from relationship()
                ->searchable()
                ->required(),
            Select::make('therapy_id')
                ->label('Terapia')
                ->options(Therapy::all()->pluck('name', 'id')) // Changed from relationship()
                ->searchable()
                ->required(),
            Select::make('service_type_id')
                ->label('Tipo de Atendimento')
                ->options(ServiceType::all()->pluck('name', 'id')) // Changed from relationship()
                ->searchable()
                ->required(),
        ];
    }
}
