<?php

namespace App\Filament\Resources\RequestedServices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RequestedServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.name')
                    ->label('Nome')
                    ->numeric()
                    ->sortable(),
                    TextColumn::make('therapy.name')
                    ->label('Terapia')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('service_type')
                    ->label('Tipo de Atendimento')
                    ->searchable(),
                TextColumn::make('month_year')
                    ->label('Mês/Ano')
                    ->date()
                    ->sortable(),
                TextColumn::make('requisition_number')
                    ->label('Requisição')
                    ->searchable(),
                TextColumn::make('requested_hours')
                    ->label('Horas Solicitadas')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('approved_hours')
                    ->label('Horas Liberadas')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('planned_hours')
                    ->label('Horas Planejadas')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Data de registro')
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
