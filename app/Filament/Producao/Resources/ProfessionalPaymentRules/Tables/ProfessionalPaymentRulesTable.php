<?php

namespace App\Filament\Producao\Resources\ProfessionalPaymentRules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ProfessionalPaymentRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('professional.name')
                    ->label('Profissional')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payment_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'por_sessao' => 'Por Sessão',
                        'por_hora' => 'Por Hora',
                        'por_dia' => 'Por Dia',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'por_sessao' => 'success',
                        'por_hora' => 'warning',
                        'por_dia' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('agreement.name')
                    ->label('Convênio')
                    ->default('Todos')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('therapy.name')
                    ->label('Terapia')
                    ->default('Todas')
                    ->badge()
                    ->color('gray'),
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
