<?php

namespace App\Filament\Resources\Appointments\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;

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
            ])
            ->filters([
                SelectFilter::make('patient')
                    ->relationship('patient', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Paciente'),
                SelectFilter::make('professional')
                    ->relationship('professional', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Profissional'),
                SelectFilter::make('agreement')
                    ->relationship('patient.agreement', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Convênio'),
                SelectFilter::make('therapy')
                    ->relationship('therapy', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Terapia'),
                SelectFilter::make('serviceType')
                    ->relationship('serviceType', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Tipo de Atendimento'),
                Filter::make('appointment_date')
                    ->form([
                        DatePicker::make('date_from')->label('Data Início'),
                        DatePicker::make('date_until')->label('Data Fim'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('appointment_date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('appointment_date', '<=', $date),
                            );
                    })
            ], layout: FiltersLayout::AboveContent);
    }
}
