<?php

namespace App\Filament\Resources\Appointments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                TextColumn::make('service_type')
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
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Última atualização')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
