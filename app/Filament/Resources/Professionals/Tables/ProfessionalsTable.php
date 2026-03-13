<?php

namespace App\Filament\Resources\Professionals\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter; 
use Filament\Tables\Enums\FiltersLayout;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction; 
use Filament\Actions\ForceDeleteAction; 
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction; 
use Filament\Actions\ForceDeleteBulkAction; 
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

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
                    })
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
                    ->formatStateUsing(function ($state): string {
                        // Se for um objeto Enum, pega o valor em texto dele. Se já for texto, usa normal.
                        $valor = $state instanceof \BackedEnum ? $state->value : $state;
                        
                        return match ($valor) {
                            'therapist'   => 'AT',
                            'supervisor'  => 'Supervisor',
                            'coordinator' => 'Coordenador',
                            default       => (string) $valor,
                        };
                    }),
                TextColumn::make('therapy.name')
                    ->label('Especialidade')
                    ->searchable(),
                TextColumn::make('units.city') // Coloque no plural!
                    ->badge()
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
                TextColumn::make('status_visual')
                    ->label('Status')
                    ->state(fn ($record) => $record->trashed() ? 'Inativo' : 'Ativo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Ativo' => 'success',
                        'Inativo' => 'danger',
                    }),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                SelectFilter::make('units')
                    ->label('Unidades')
                    ->relationship('units', 'city')
                    ->preload()
                    ->multiple(),
                SelectFilter::make('therapy')
                    ->relationship('therapy', 'name')
                    ->multiple()
                    ->label('Especialidade'),
                SelectFilter::make('role')
                    ->options([
                        'therapist'   => 'AT',
                        'supervisor'  => 'Supervisor',
                        'coordinator' => 'Coordenador',
                    ])
                    ->multiple()
                    ->label('Cargo/Função'),
                    
                TrashedFilter::make()
                    //->visible(fn () => auth()->user()?->is_admin),
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
                    
                // DeleteAction::make()
                //     ->label('Registrar Saída')
                //     ->modalHeading('Registrar Saída do Profissional')
                //     ->modalDescription('O profissional ficará inativo no sistema. Informe o motivo abaixo.')
                //     ->icon('heroicon-o-arrow-right-start-on-rectangle')
                //     ->form([
                //         Select::make('motivo_saida')
                //             ->label('Motivo principal')
                //             ->options([
                //                 'Iniciativa do profissional' => 'Iniciativa do profissional',
                //                 'Iniciativa da empresa' => 'Iniciativa da empresa',
                //             ])
                //             ->required(),
                            
                //         Textarea::make('observacao')
                //             ->label('Observação adicional (opcional)')
                //             ->placeholder('Detalhes da saída do profissional...')
                //             ->rows(3),
                //     ])
                //     ->after(function (\App\Models\Professional $record, array $data) {
                //         $motivoCompleto = $data['motivo_saida'];
                //         if (!empty($data['observacao'])) {
                //             $motivoCompleto .= ' - ' . $data['observacao'];
                //         }

                //
                //         $record->movementHistories()->create([
                //             'action' => 'Saída', 
                //             'reason' => $motivoCompleto,
                //             'date' => now(),
                //             'user_id' => auth()->id(),
                //         ]);
                //     }),
                    
                RestoreAction::make()
                    ->label('Registrar Retorno')
                    ->modalHeading('Reativar Profissional')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->visible(fn ($record) => auth()->user()?->is_admin && $record->trashed())
                    ->form([
                        Textarea::make('motivo_retorno')
                            ->label('Motivo do Retorno')
                            ->placeholder('Ex: Profissional recontratado...')
                            ->required()
                            ->rows(3),
                    ])
                    ->after(function (\App\Models\Professional $record, array $data) {
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
                        ->modalHeading('Registrar Saída dos Profissionais')
                        ->icon('heroicon-o-arrow-right-start-on-rectangle')
                        ->form([
                            Select::make('motivo_saida')
                                ->label('Motivo principal')
                                ->options([
                                    'Iniciativa do profissional' => 'Iniciativa do profissional',
                                    'Iniciativa da empresa' => 'Iniciativa da empresa',
                                ])
                                ->required(),
                                
                            Textarea::make('observacao')
                                ->label('Observação adicional (opcional)')
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