<?php

namespace App\Filament\Resources\RequestedServices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;

class RequestedServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.name')
                    ->label('Nome')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('therapy.name')
                    ->label('Terapia')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('serviceType.name')
                    ->label('Tipo de Atendimento')
                    ->searchable(),
                TextColumn::make('month_year')
                    ->label('Mês/Ano')
                    ->date('F \\d\\e Y')
                    ->sortable(),
                TextColumn::make('requisition_number')
                    ->label('Requisição')
                    ->searchable(),
                TextColumn::make('requested_hours')
                    ->label('Horas Solicitadas')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('approved_hours')
                    ->label('Horas Liberadas')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('planned_hours')
                    ->label('Horas Planejadas')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Data de registro')
                    ->timezone('America/Fortaleza')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Última atualização')
                    ->timezone('America/Fortaleza')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('unit')
                    ->relationship('patient.unit', 'city')
                    ->preload()
                    ->label('Unidade'),
                Filter::make('month_year')
                    ->form([
                        Select::make('month')
                            ->label('Mês')
                            ->options([
                                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                            ]),
                        Select::make('year')
                            ->label('Ano')
                            ->options(function () {
                                $years = [];
                                for ($i = 0; $i <= 5; $i++) {
                                    $year = now()->subYears($i)->year;
                                    $years[$year] = $year;
                                }
                                return $years;
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['year'],
                                fn (Builder $query, $year): Builder => $query->whereYear('month_year', '=', $year)
                            )
                            ->when(
                                $data['month'],
                                fn (Builder $query, $month): Builder => $query->whereMonth('month_year', '=', $month)
                            );
                    })
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
