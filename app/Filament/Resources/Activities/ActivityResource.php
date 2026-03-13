<?php

namespace App\Filament\Resources\Activities;

use App\Filament\Resources\Activities\Pages\ManageActivities;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivityResource extends Resource
{
    protected static ?string $model = \Spatie\Activitylog\Models\Activity::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $modelLabel = 'Controle de Atividade';
    protected static ?string $pluralModelLabel = 'Controles de Atividades';
    protected static ?string $navigationLabel = 'Controles de Atividades';
    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    // 🚨 TRAVA DE SEGURANÇA: Proíbe criar, editar ou excluir os logs manualmente
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
                        // Se for uma Movimentação, ele lê o que está no banco e muda a palavra
                        if (class_basename($record->subject_type) === 'MovementHistory') {
                            $atributos = $record->properties['attributes'] ?? [];
                            $acaoClinica = $atributos['action'] ?? '';
                            
                            if ($acaoClinica === 'Saida' || $acaoClinica === 'Saída') return 'saída';
                            if ($acaoClinica === 'Retorno') return 'retorno';
                            
                            return 'movimentação';
                        }
                        
                        // Se for um paciente/profissional direto, mantém o padrão (created, deleted)
                        return $record->event; 
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
                        
                        // Se for Movimentação, pega o nome do paciente pela relação 'moveable' (mesmo se já foi deletado)
                        if ($modelo === 'MovementHistory') {
                            $nome = $record->subject?->moveable()->withTrashed()->first()?->name;
                        } else {
                            $nome = $record->subject?->name ?? $record->subject?->city ?? null;
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
                        
                        // 1. Tratamento Vip para Entradas e Saídas Clínicas (MovementHistory)
                        if ($modelo === 'MovementHistory' && $evento === 'created') {
                            $atributos = $props['attributes'] ?? [];
                            $acao = $atributos['action'] ?? 'Movimentação';
                            $icone = $acao === 'Retorno' ? '🟢' : '🔴';
                            return "{$icone} Registro de {$acao} oficializado.";
                        }

                        // 2. Se foi uma EXCLUSÃO (Botão vermelho bruto)
                        if ($evento === 'deleted') {
                            return '🗑️ Registro excluído do sistema.';
                        }

                        // 3. Se foi uma CRIAÇÃO direta
                        if ($evento === 'created') {
                            return '✨ Cadastro realizado no sistema.';
                        }

                        // 4. Se foi um RETORNO (Lixeira do sistema)
                        if ($evento === 'restored') {
                            return '♻️ Registro restaurado (Retornou ao sistema).';
                        }
                        
                        // 5. Se foi uma ATUALIZAÇÃO (Traz o Antes e Depois)
                        if ($evento === 'updated' && isset($props['attributes'])) {
                            $mudancas = [];
                            foreach ($props['attributes'] as $coluna => $valorNovo) {
                                $valorAntigo = $props['old'][$coluna] ?? 'vazio';
                                
                                // TRADUTOR DE UNIDADE
                                if ($coluna === 'unit_id') {
                                    $cidadeAntiga = \App\Models\Unit::find($valorAntigo)?->city ?? $valorAntigo;
                                    $cidadeNova = \App\Models\Unit::find($valorNovo)?->city ?? $valorNovo;
                                    $mudancas[] = "Unidade: [{$cidadeAntiga}] ➔ [{$cidadeNova}]";
                                    continue;
                                }
                                
                                // TRADUTOR DE CONVÊNIO
                                if ($coluna === 'agreement_id') {
                                    $convAntigo = \App\Models\Agreement::find($valorAntigo)?->name ?? $valorAntigo;
                                    $convNovo = \App\Models\Agreement::find($valorNovo)?->name ?? $valorNovo;
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
                        if (class_basename($record->subject_type) === 'MovementHistory') {
                            return $record->subject?->moveable()->withTrashed()->first()?->unit?->city ?? '-';
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
                //
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