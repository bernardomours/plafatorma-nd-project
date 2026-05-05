<?php

namespace App\Filament\Resources\NeuroAssessments\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';
    
    protected static ?string $title = 'Diário de Sessões';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('session_number')
                        ->label('Número da Sessão')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(10)
                        ->default(function (RelationManager $livewire) {
                            $ultimaSessao = $livewire->getOwnerRecord()->sessions()->max('session_number') ?? 0;
                            
                            return $ultimaSessao + 1;
                        }),

                    DatePicker::make('date')
                        ->label('Data da Sessão')
                        ->required()
                        ->default(now()),

                    Select::make('professional_id')
                        ->relationship('professional', 'name')
                        ->label('Profissional Atendente')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),
                    
                    Select::make('status')
                                ->label('Status da Avaliação')
                                ->options([
                                    'Em andamento' => 'Em andamento',
                                    'Concluída' => 'Concluída',
                                    'Cancelada' => 'Cancelada',
                                ])
                                ->native(false)
                                ->required()
                                ->columnSpanFull()
                                ->default('Em andamento'),

                    Textarea::make('observations')
                        ->label('Observações e Anotações')
                        ->rows(4)
                        ->columnSpanFull(),
                ])
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('session_number')
            ->columns([
                TextColumn::make('session_number')
                    ->label('Sessão')
                    ->formatStateUsing(fn ($state) => "{$state}ª Sessão")
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('professional.name')
                    ->label('Profissional')
                    ->searchable(),

                TextColumn::make('observations')
                    ->label('Observações')
                    ->limit(40)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return $state && strlen($state) > 40 ? $state : null;
                    }),
            ])
            ->defaultSort('session_number', 'asc')
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar Nova Sessão')
                    ->icon('heroicon-o-plus')
                    ->visible(function (RelationManager $livewire) {
                    $ultimaSessao = $livewire->getOwnerRecord()->sessions()->max('session_number') ?? 0;
                    return $ultimaSessao < 10;
                })
                
                ->after(function ($record, $livewire) {
                    $avaliacao = $livewire->getOwnerRecord();
                    
                    $dadosParaAtualizar = [
                        'current_session' => $record->session_number
                    ];

                    if ($record->session_number == 10) {
                        $dadosParaAtualizar['status'] = 'Concluída'; 
                    }

                    $avaliacao->update($dadosParaAtualizar);
                }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}