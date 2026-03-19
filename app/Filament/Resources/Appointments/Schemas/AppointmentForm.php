<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Models\Patient;
use App\Models\Therapy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons; // 🌟 NOVO IMPORT AQUI
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        $updateSessionNumber = function (callable $set, callable $get) { #função que calcula automaticamente as sessoes
            $checkIn = $get('check_in');                                 #de uma consulta pelo checkin e checkout
            $checkOut = $get('check_out');

            if (empty($checkIn) || empty($checkOut)) {
                $set('session_number', null);
                return;
            }

            $patientId = $get('patient_id');
            $therapyId = $get('therapy_id');

            $sessionDuration = 40;

            if ($patientId && $therapyId) {
                $patient = Patient::with('agreement')->find($patientId);
                $therapy = Therapy::find($therapyId);

                if ($patient && $therapy) {
                    $isHumana = $patient->agreement && $patient->agreement->name === 'Humana';
                    $isAba = $therapy->name === 'ABA';

                    if ($isHumana) {
                        $sessionDuration = 40;
                    } else if ($isAba) {
                        $sessionDuration = 60;
                    } else {
                        $sessionDuration = 40;
                    }
                }
            }

            $checkInTime = new \DateTime($checkIn);
            $checkOutTime = new \DateTime($checkOut);

            if ($checkOutTime > $checkInTime) {
                $interval = $checkOutTime->diff($checkInTime);
                $minutes = ($interval->h * 60) + $interval->i;
                $sessions = ceil($minutes / $sessionDuration);
                $set('session_number', $sessions);
            } else {
                $set('session_number', 0);
            }
        };

        return $schema
            ->components([
                Select::make('patient_id')
                    ->label('Nome')
                    ->relationship(
                        name: 'patient', 
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query) => $query->withTrashed()
                    )
                    ->required()
                    ->preload()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated($updateSessionNumber),
                
                ToggleButtons::make('data_rapida')
                    ->label('Atalho de Data')
                    ->options([
                        'ontem' => 'Ontem',
                        'hoje' => 'Hoje',
                        'outro' => 'Outra Data',
                    ])
                    ->colors([
                        'ontem' => 'warning',
                        'hoje' => 'success',
                        'outro' => 'gray',
                    ])
                    ->inline()
                    ->default('hoje')
                    ->dehydrated(false)
                    ->live()
                    ->afterStateUpdated(function (callable $set, $state) {
                        if ($state === 'hoje') {
                            $set('appointment_date', now()->timezone('America/Fortaleza')->format('Y-m-d'));
                        } elseif ($state === 'ontem') {
                            $set('appointment_date', now()->timezone('America/Fortaleza')->subDay()->format('Y-m-d'));
                        }
                    }),
                
                TimePicker::make('check_in')
                    ->label('Check-in')
                    ->required()
                    ->seconds(false)
                    ->displayFormat('H:i')
                    ->format('H:i')
                    ->live()
                    ->afterStateUpdated($updateSessionNumber),
                
                TimePicker::make('check_out')
                    ->label('Check-out')
                    ->seconds(false)
                    ->displayFormat('H:i')
                    ->format('H:i')
                    ->live(onBlur: true)
                    ->afterStateUpdated($updateSessionNumber),
                
                Select::make('therapy_id')
                    ->label('Terapia')
                    ->relationship('therapy', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (callable $set, callable $get) use ($updateSessionNumber) {
                        $set('professional_id', null); 
                        $updateSessionNumber($set, $get);
                    }),
                
                Select::make('service_type_id')
                    ->label('Tipo de Atendimento')
                    ->relationship('serviceType', 'name')
                    ->required(),
                
                Select::make('professional_id')
                    ->label('Profissional')
                    ->relationship(
                        name: 'professional', 
                        titleAttribute: 'name',
                        modifyQueryUsing: function (\Illuminate\Database\Eloquent\Builder $query, callable $get) {
                            $query->withTrashed();

                            // 🌟 Pega a terapia selecionada acima
                            $therapyId = $get('therapy_id');

                            // Se ela escolheu uma terapia, filtra a coluna direto!
                            if ($therapyId) {
                                $query->where('therapy_id', $therapyId); 
                            }

                            return $query;
                        }
                    )
                    ->required()
                    ->preload()
                    ->searchable()
                    ->live(),
                
                TextInput::make('session_number') 
                    ->label('Qtd de Sessões')
                    ->numeric()
                    ->helperText('Preenchido automaticamente com base no check-in e check-out'),
                
                DatePicker::make('appointment_date')
                    ->label('Data da Consulta')
                    ->default(now()->timezone('America/Fortaleza'))
                    ->required(),
            ]);
    }
}