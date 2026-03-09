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
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Collection; // <-- IMPORTANTE: Adicionado para a ação em massa funcionar

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
                TextColumn::make('status_visual')
                    ->label('Status')
                    ->state(fn ($record) => $record->trashed() ? 'Inativo' : 'Ativo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Ativo' => 'success',
                        'Inativo' => 'danger',
                    }),
            ])
            ->filters([
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
                //    ->visible(fn () => auth()->user()?->is_admin),
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
                    
                DeleteAction::make()
                    ->label('Registrar Saída')
                    ->modalHeading('Registrar Saída do Paciente')
                    ->modalDescription('O paciente ficará inativo no sistema. Por favor, informe o motivo da saída abaixo.')
                    ->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->form([
                        Select::make('motivo_saida')
                            ->label('Motivo principal')
                            ->options([
                                'Alta' => 'Alta',
                                'Suspensão' => 'Suspensão',
                                'Solicitação do Responsável' => 'Solicitação do Responsável',
                            ])
                            ->required(),
                            
                        Textarea::make('observacao')
                            ->label('Observação adicional (opcional)')
                            ->placeholder('Detalhes sobre a alta ou saída...')
                            ->rows(3),
                    ])
                    ->after(function (\App\Models\Patient $record, array $data) {
                        $motivoCompleto = $data['motivo_saida'];
                        if (!empty($data['observacao'])) {
                            $motivoCompleto .= ' - ' . $data['observacao'];
                        }
                        $record->movementHistories()->create([
                            'action' => 'Saída', 
                            'reason' => $motivoCompleto,
                            'date' => now(),
                            'user_id' => auth()->id(),
                        ]);
                    }),
                RestoreAction::make()
                    ->label('Registrar Retorno')
                    ->modalHeading('Reativar Paciente')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->visible(fn ($record) => auth()->user()?->is_admin && $record->trashed())
                    ->form([
                        Textarea::make('motivo_retorno')
                            ->label('Motivo do Retorno')
                            ->placeholder('Ex: Paciente retornou após 2 meses de suspensão para nova avaliação...')
                            ->required()
                            ->rows(3),
                    ])
                    ->after(function (\App\Models\Patient $record, array $data) {
                        $record->movementHistories()->create([
                            'action' => 'Retorno',
                            'reason' => $data['motivo_retorno'],
                            'date' => now(),
                            'user_id' => auth()->id(),
                        ]);
                    }),
                    
                ForceDeleteAction::make()
                    ->visible(fn ($record) => auth()->user()?->is_admin && $record->trashed()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Registrar Saída')
                        ->modalHeading('Registrar Saída do(s) Paciente(s)')
                        ->modalDescription('Os pacientes selecionados ficarão inativos no sistema. O motivo abaixo será aplicado a TODOS eles.')
                        ->icon('heroicon-o-arrow-right-start-on-rectangle')
                        ->form([
                            Select::make('motivo_saida')
                                ->label('Motivo principal')
                                ->options([
                                    'Alta' => 'Alta',
                                    'Suspensão' => 'Suspensão',
                                    'Solicitação do Responsável' => 'Solicitação do Responsável',
                                ])
                                ->required(),
                                
                            Textarea::make('observacao')
                                ->label('Observação adicional (opcional)')
                                ->placeholder('Detalhes sobre a alta ou saída...')
                                ->rows(3),
                        ])
                        ->after(function (Collection $records, array $data) {
                            $motivoCompleto = $data['motivo_saida'];
                            if (!empty($data['observacao'])) {
                                $motivoCompleto .= ' - ' . $data['observacao'];
                            }
                            foreach ($records as $record) {
                                $record->movementHistories()->create([
                                    'action' => 'Saída', 
                                    'reason' => $motivoCompleto,
                                    'date' => now(),
                                    'user_id' => auth()->id(),
                                ]);
                            }
                        }),
                    
                    RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()?->is_admin),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->is_admin),
                ]),
            ]);
    }
}