<?php

namespace App\Filament\Resources\Schedules\Schemas;

use App\Models\Professional;
use App\Models\ServiceType;
use App\Models\Therapy;
use App\Models\Schedule;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Closure;
use Illuminate\Database\Eloquent\Model;

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
                ->required()
                ->rule(static function (Get $get, ?Model $record): Closure {
                    return static function (string $attribute, $value, Closure $fail) use ($get, $record) {
                        
                        $prof_id = $get('professional_id');
                        $dia = $get('day_of_week');
                        $inicio = $get('start_time');
                        $fim = $value;

                        if ($prof_id && $dia && $inicio && $fim) {
                            
                            $conflito = Schedule::where('professional_id', $prof_id)
                                ->where('day_of_week', $dia)
                                ->where(function ($query) use ($inicio, $fim) {
                                    $query->where('start_time', '<', $fim)
                                          ->where('end_time', '>', $inicio);
                                });

                            if ($record) {
                                $conflito->where('id', '!=', $record->id);
                            }

                            if ($conflito->exists()) {
                                $fail('⚠️ Este profissional já tem um paciente neste horário!');
                            }
                        }
                    };
                }),
                
            Select::make('professional_id')
                ->label('Profissional')
                ->options(Professional::all()->pluck('name', 'id'))
                ->searchable()
                ->required(),
                
            Select::make('therapy_id')
                ->label('Terapia')
                ->options(Therapy::all()->pluck('name', 'id'))
                ->searchable()
                ->required(),
                
            Select::make('service_type_id')
                ->label('Tipo de Atendimento')
                ->options(ServiceType::all()->pluck('name', 'id'))
                ->searchable()
                ->required(),
        ];
    }
}