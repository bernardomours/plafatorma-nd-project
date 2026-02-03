<?php

namespace App\Filament\Resources\Appointments\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('appointment_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('therapy.name')
                    ->label('Terapia')
                    ->searchable(),
                TextColumn::make('serviceType.name')
                    ->label('Tipo de Atendimento')
                    ->searchable(),
                TextColumn::make('session_number')
                    ->label('Qtd de Sessões')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('professional.name')
                    ->label('Profissional')
                    ->searchable(),
                TextColumn::make('check_in')
                    ->label('Check-in')
                    ->time()
                    ->sortable(),
                TextColumn::make('check_out')
                    ->label('Check-out')
                    ->time()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Registrado em')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('America/Fortaleza')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->timezone('America/Fortaleza')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
