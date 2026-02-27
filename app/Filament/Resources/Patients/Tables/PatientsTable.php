<?php

namespace App\Filament\Resources\Patients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Actions\RestoreAction; 
use Filament\Actions\ForceDeleteAction; 
use Filament\Actions\RestoreBulkAction; 
use Filament\Actions\ForceDeleteBulkAction; 
use Filament\Tables\Filters\TrashedFilter; 

class PatientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderByRaw("LOWER(name) {$direction}");
                    })
                    ->searchable(),
                TextColumn::make('cpf')
                    ->label('CPF')
                    ->searchable(),
                TextColumn::make('agreement_number')
                    ->label('Carteira')
                    ->searchable(),
                TextColumn::make('birth_date')
                    ->label('Data de Nascimento')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('agreement.name')
                    ->label('Convênio')
                    ->searchable(),
                TextColumn::make('guardian_name')
                    ->label('Nome do Responsável')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('guardian_phone')
                    ->label('Contato do Responsável')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('unit.city')
                    ->label('Unidade')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->timezone('America/Fortaleza')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->timezone('America/Fortaleza')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_active')
                    ->label('Ativo')
                    ->onColor('success')
                    ->offColor('danger')
                //   ->disabled(fn () => ! auth()->user()->is_admin),

            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status de Atendimento')
                    ->placeholder('Todos os Pacientes')
                    ->trueLabel('Apenas Ativos')
                    ->falseLabel('Apenas Inativos')
                    ->default(true),

                SelectFilter::make('unit')
                    ->relationship('unit', 'city')
                    ->preload()
                    ->multiple()
                    ->label('Unidade'),

                SelectFilter::make('agreement')
                    ->relationship('agreement', 'name')
                    ->preload()
                    ->multiple()
                    ->label('Convênio'),
                
                TrashedFilter::make()
                    ->visible(fn () => auth()->user()?->is_admin),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->defaultSort('name', 'asc')
            ->filtersTriggerAction(
                fn ($action) => $action
                    ->button()
                    ->label('Filtros')
                    ->slideOver()
                    ->icon('heroicon-m-chevron-down'))
            ->actions([
                EditAction::make()
                    ->hidden(fn ($record) => $record->trashed()),
                    
                RestoreAction::make()
                    ->visible(fn ($record) => auth()->user()?->is_admin && $record->trashed()),
                    
                ForceDeleteAction::make()
                    ->visible(fn ($record) => auth()->user()?->is_admin && $record->trashed()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    
                    RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()?->is_admin),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->is_admin),
                ]),
            ]);
    }
}