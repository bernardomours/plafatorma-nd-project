<?php

namespace App\Filament\Resources\Professionals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction; 
use Filament\Actions\ForceDeleteAction; 
use Filament\Actions\RestoreBulkAction; 
use Filament\Actions\ForceDeleteBulkAction; 
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter; 
use Filament\Tables\Enums\FiltersLayout;

class ProfessionalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderByRaw("LOWER(name) {$direction}");
                    }) //isso serve para deixar tudo minusculo para ordenar sem diferir maiusculas e minusculas
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
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Cargo / Função')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'therapist'   => 'AT',
                        'supervisor'  => 'Supervisor',
                        'coordinator' => 'Coordenador',
                        default       => $state,
                    }),
                TextColumn::make('therapy.name')
                    ->label('Especialidade')
                    ->searchable(),
                TextColumn::make('unit.city')
                    ->label('Unidade')
                    ->searchable(),    
                TextColumn::make('created_at')
                    ->label('Registrado em')
                    ->timezone('America/Fortaleza')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Última Atualização')
                    ->timezone('America/Fortaleza')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                SelectFilter::make('unit')
                    ->relationship('unit', 'city')
                    ->preload()
                    ->multiple()
                    ->label('Unidade'),
                    
                TrashedFilter::make()
                    ->visible(fn () => auth()->user()?->is_admin),
            ], layout: FiltersLayout::AboveContentCollapsible)
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
                    BulkAction::make('delete')
                        ->label('Excluir selecionados')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->form([
                            Select::make('deletion_reason')
                                ->label('Motivo da Exclusão')
                                ->options([
                                    'Iniciativa do profissional' => 'Iniciativa do profissional',
                                    'Iniciativa da empresa' => 'Iniciativa da empresa',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(function ($record) use ($data) {
                                $record->update([
                                    'deletion_reason' => $data['deletion_reason'],
                                ]);
                                $record->delete();
                            });
                        }),
                        
                    // AS TRAVAS ADICIONADAS AQUI:
                    RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()?->is_admin),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->is_admin),
                ]),
            ]);
    }
}