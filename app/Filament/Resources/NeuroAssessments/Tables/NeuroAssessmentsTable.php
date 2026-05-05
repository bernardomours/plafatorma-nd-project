<?php

namespace App\Filament\Resources\NeuroAssessments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Split;

class NeuroAssessmentsTable
{
    public static function configure(Table $table): Table
{
    return $table
        ->contentGrid([
            'md' => 2,
            'xl' => 3,
        ])
        ->columns([
            Stack::make([
                TextColumn::make('patient.name')
                    ->weight('bold')
                    ->size('lg'),

                TextColumn::make('cidade_e_convenio')
                    ->state(fn ($record) => $record->patient?->unit?->city . ' • ' . $record->patient?->agreement?->name)
                    ->icon('heroicon-m-building-office')
                    ->badge()
                    ->color('gray')
                    ->extraAttributes(['class' => 'mt-2 mb-2']),

                TextColumn::make('professional.name')
                    ->color('gray')
                    ->size('sm'),
                
                TextColumn::make('current_session')
                    ->formatStateUsing(fn ($state) => "Sessão {$state} de 10")
                    ->badge()
                    ->color(fn ($state) => $state >= 10 ? 'success' : 'warning'),

                TextColumn::make('status')
                    ->badge(),
            ])
        ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                ->label('Acessar Diário') // <-- Muda o texto no card
                ->icon('heroicon-m-folder-open') // <-- Bota um ícone de pastinha (opcional)
                ->color('info'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
