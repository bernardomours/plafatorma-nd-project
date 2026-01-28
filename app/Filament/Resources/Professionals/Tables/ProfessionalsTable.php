<?php

namespace App\Filament\Resources\Professionals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProfessionalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('cpf')
                    ->label('CPF')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Telefone')
                    ->searchable(),
                TextColumn::make('birth_date')
                    ->label('Data de Nascimento')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('register_number')
                    ->label('Número de Registro')
                    ->searchable(),
                TextColumn::make('therapy.name')
                    ->label('Especialidade')
                    ->searchable(),
                TextColumn::make('unit.city')
                    ->label('Unidade')
                    ->searchable(),    
                TextColumn::make('created_at')
                    ->label('Registrado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Última Atualização')
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
