<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Models\Patient;
use App\Models\Therapy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        $updateSessionNumber = function (callable $set, callable $get) {
            $checkIn = $get('check_in');
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
                    ->relationship('patient', 'name')
                    ->required()
                    ->preload()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated($updateSessionNumber),
                DatePicker::make('appointment_date')
                    ->label('Data')
                    ->required(),
                TimePicker::make('check_in')
                    ->label('Check-in')
                    ->required()
                    ->seconds(false)
                    ->live()
                    ->afterStateUpdated($updateSessionNumber),
                TimePicker::make('check_out')
                    ->label('Check-out')
                    ->required()
                    ->seconds(false)
                    ->live()
                    ->afterStateUpdated($updateSessionNumber),
                Select::make('therapy_id')
                    ->label('Terapia')
                    ->relationship('therapy', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated($updateSessionNumber),
                Select::make('service_type_id')
                    ->label('Tipo de Atendimento')
                    ->relationship('serviceType', 'name')
                    ->required(),
                Select::make('professional_id')
                    ->label('Profissional')
                    ->relationship('professional', 'name')
                    ->required(),
                TextInput::make('session_number') #preenchido automaticamente
                    ->label('Qtd de Sessões')
                    ->numeric()
                    ->helperText('Preenchido automaticamente com base no check-in e check-out')
            ]);
    }
}
