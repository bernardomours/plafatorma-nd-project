<?php

namespace App\Filament\Resources\Appointments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('patient_id')
                    ->label('Nome')
                    ->relationship('patient', 'name')
                    ->required()
                    ->preload()
                    ->searchable(),
                DatePicker::make('appointment_date')
                    ->label('Data')
                    ->required(),
                TimePicker::make('check_in')
                    ->label('Check-in')
                    ->required(),
                TimePicker::make('check_out')
                    ->label('Check-out')
                    ->required(),
                TextInput::make('session_number')
                    ->label('Qtd de Sessões')
                    ->numeric(),
                Select::make('service_type')
                    ->label('Tipo de Atendimento')
                    ->options([
                        'clinica' => 'Clínica',
                        'escolar' => 'Escolar',
                        'domiciliar' => 'Domiciliar',
                    ]),
                Select::make('professional_id')
                    ->label('Profissional')
                    ->relationship('professional', 'name')
                    ->required(),
                Select::make('therapy_id')
                    ->label('Terapia')
                    ->relationship('therapy', 'name')
                    ->required(),
            ]);
    }
}
