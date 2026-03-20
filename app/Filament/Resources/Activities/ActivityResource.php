<?php

namespace App\Filament\Resources\Activities;

use App\Filament\Resources\Activities\Pages\ManageActivities;
use BackedEnum;
use UnitEnum;
use App\Models\Unit;
use App\Models\Agreement;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActivityResource extends Resource
{
    protected static ?string $model = \Spatie\Activitylog\Models\Activity::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $modelLabel = 'Controle de Atividade';
    protected static ?string $pluralModelLabel = 'Controles de Atividades';
    protected static ?string $navigationLabel = 'Controles de Atividades';
    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->id === 1;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('DATA/HORA')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('causer.name')
                    ->label('Usuário')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Ação')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (class_basename($record->subject_type) === 'MovementHistory') {
                            $atributos = $record->properties['attributes'] ?? [];
                            $acaoClinica = $atributos['action'] ?? '';
                            
                            if ($acaoClinica === 'Saida' || $acaoClinica === 'Saída') return 'saída';
                            if ($acaoClinica === 'Retorno') return 'retorno';
                            
                            return 'movimentação';
                        }
                        
                        return $record->event; 
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'ENTRADA',
                        'updated' => 'ATUALIZAÇÃO',
                        'deleted' => 'EXCLUSÃO',
                        'saída' => 'SAÍDA',
                        'retorno' => 'RETORNO',
                        'movimentação' => 'MOVIMENTAÇÃO',
                        'restored' => 'RETORNO',
                        default => mb_strtoupper($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'created', 'retorno' => 'success',
                        'updated' => 'warning',
                        'deleted', 'saída' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('subject_type')
                    ->label('Onde mexeu')
                    ->getStateUsing(function ($record) {
                        if (!$record->subject_type) {
                            return '-';
                        }
                        
                        $modelo = class_basename($record->subject_type);
                        $nomeAmigavel = match($modelo) {
                            'Patient' => 'Paciente',
                            'Professional' => 'Profissional',
                            'Visit' => 'Visita/Supervisão',
                            'MovementHistory' => 'Desligamento/Entrada',
                            default => $modelo
                        };
                        
                        $nome = null;
                
                        if ($record->subject) {
                            if ($modelo === 'MovementHistory') {
                                $nome = $record->subject->moveable()->withTrashed()->first()?->name;
                            } else {
                                $nome = $record->subject->name ?? $record->subject->city ?? null;
                            }
                        }

                        if (!$nome && $record->properties) {
                            $propriedades = is_array($record->properties) ? $record->properties : $record->properties->toArray();
                            
                            $nome = $propriedades['old']['name'] ?? $propriedades['attributes']['name'] ?? null;
                        }
                        
                        if ($nome) {
                            return "{$nomeAmigavel} ({$nome})";
                        }
                       
                        return "{$nomeAmigavel} #{$record->subject_id}";
                    }),

                TextColumn::make('properties')
                    ->label('Detalhes da Mudança')
                    ->wrap()
                    ->visible(fn ($livewire) => $livewire->activeTab !== 'entradas_saidas')
                    ->getStateUsing(function ($record) {
                        $props = $record->properties;
                        $evento = $record->event; 
                        $modelo = class_basename($record->subject_type);
                        
                        if ($modelo === 'MovementHistory' && $evento === 'created') {
                            $atributos = $props['attributes'] ?? [];
                            $acao = $atributos['action'] ?? 'Movimentação';
                            $icone = $acao === 'Retorno' ? '🟢' : '🔴';
                            return "{$icone} Registro de {$acao} oficializado.";
                        }

                        if ($evento === 'deleted') {
                            return 'Registro excluído do sistema.';
                        }

                        if ($evento === 'created') {
                            return 'Cadastro realizado no sistema.';
                        }

                        if ($evento === 'restored') {
                            return 'Cadastro disponível no sistema.';
                        }
                        
                        if ($evento === 'updated' && isset($props['attributes'])) {
                            $mudancas = [];
                            foreach ($props['attributes'] as $coluna => $valorNovo) {
                                $valorAntigo = $props['old'][$coluna] ?? 'vazio';
                                
                                if ($coluna === 'unit_id') {
                                    $cidadeAntiga = Unit::find($valorAntigo)?->city ?? $valorAntigo;
                                    $cidadeNova = Unit::find($valorNovo)?->city ?? $valorNovo;
                                    $mudancas[] = "Unidade: [{$cidadeAntiga}] ➔ [{$cidadeNova}]";
                                    continue;
                                }
                                
                                if ($coluna === 'agreement_id') {
                                    $convAntigo = Agreement::find($valorAntigo)?->name ?? $valorAntigo;
                                    $convNovo = Agreement::find($valorNovo)?->name ?? $valorNovo;
                                    $mudancas[] = "Convênio: [{$convAntigo}] ➔ [{$convNovo}]";
                                    continue;
                                }
                                
                                $mudancas[] = "{$coluna}: [{$valorAntigo}] ➔ [{$valorNovo}]";
                            }
                            
                            return implode(' | ', $mudancas);
                        }
                        
                        return 'Sem detalhes processáveis.';
                    }),

                TextColumn::make('unidade')
                    ->label('Unidade que aconteceu')
                    ->visible(fn ($livewire) => $livewire->activeTab === 'entradas_saidas')
                    ->getStateUsing(function ($record) {
                        $modelo = class_basename($record->subject_type);
                        if ($modelo === 'MovementHistory') {
                            return $record->subject?->moveable()->withTrashed()->first()?->unit?->city ?? '-';
                        }

                        if ($modelo === 'Patient') {
                            $paciente = $record->subject()->withTrashed()->first();
                            
                            if ($paciente && $paciente->unit) {
                                return $paciente->unit->city;
                            }
                            
                            $atributos = $record->properties['attributes'] ?? [];
                            if (isset($atributos['unit_id'])) {
                                return Unit::find($atributos['unit_id'])?->city ?? '-';
                            }
                        }

                        return '-';
                    }),

                TextColumn::make('razao')
                    ->label('Razão')
                    ->visible(fn ($livewire) => $livewire->activeTab === 'entradas_saidas')
                    ->getStateUsing(function ($record) {
                        if (class_basename($record->subject_type) === 'MovementHistory') {
                            $atributos = $record->properties['attributes'] ?? [];
                            return $atributos['reason'] ?? $record->subject?->reason ?? '-';
                        }
                        
                        if ($record->event === 'deleted') return 'Excluído manualmente';
                        if ($record->event === 'created') return 'Cadastro inicial';
                        if ($record->event === 'restored') return 'Restaurado manualmente';
                        
                        return '-';
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('tipo_acao')
                    ->label('Filtrar por Ação')
                    ->options([
                        'entrada' => 'Entradas',
                        'saida'   => 'Saídas',
                        'retorno' => 'Retornos',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $valor = $data['value'] ?? null;
                        if (! $valor) {
                            return $query;
                        }

                        return $query->where(function (Builder $q) use ($valor) {
                            
                            if ($valor === 'entrada') {
                                $q->where('event', 'created')
                                  ->where('subject_type', 'not like', '%MovementHistory%');
                            }

                            if ($valor === 'saida') {
                                $q->where('event', 'deleted')
                                  ->orWhere(function ($sub) {
                                      $sub->where('properties->attributes->action', 'Saída')
                                          ->orWhere('properties->attributes->action', 'Saida');
                                  });
                            }

                            if ($valor === 'retorno') {
                                $q->where('event', 'restored')
                                  ->orWhere(function ($sub) {
                                      $sub->where('properties->attributes->action', 'Retorno');
                                  });
                            }
                        });
                    }),
            ])
            ->recordActions([
            ])
            ->toolbarActions([
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageActivities::route('/'),
        ];
    }
}