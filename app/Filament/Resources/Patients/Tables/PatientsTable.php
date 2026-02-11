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

class PatientsTable
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
                    ->disabled(fn () => ! auth()->user()->is_admin),

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
                    ->label('Unidade'),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersTriggerAction(
                fn ($action) => $action
                    ->button()
                    ->label('Filtros')
                    ->slideOver()
                    ->icon('heroicon-m-chevron-down'))
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
