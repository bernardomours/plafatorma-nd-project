<?php

namespace App\Filament\Resources\Monitorings\Tables;

use App\Enums\MonitoringStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MonitoringsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('task')
                    ->label('Tarefa')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('unit.city')
                    ->label('Unidade')
                    ->sortable(),
                TextColumn::make('setor_responsavel')
                    ->label('Setor')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Responsável')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->sortable(),
                TextColumn::make('prazo')
                    ->label('Prazo')
                    ->date('d/m/Y')
                    ->sortable(),   
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(MonitoringStatus::class)
                    ->label('Status'),
                SelectFilter::make('unit_id')
                    ->relationship('unit', 'name')
                    ->label('Unidade'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
