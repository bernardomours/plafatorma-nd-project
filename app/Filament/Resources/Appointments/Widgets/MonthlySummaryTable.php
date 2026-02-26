<?php

namespace App\Filament\Resources\Appointments\Widgets;

use App\Models\Appointment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class MonthlySummaryTable extends BaseWidget
{
    protected static ?string $heading = 'Resumo Mensal de Sessões';
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Appointment::query()
                    ->join('patients', 'appointments.patient_id', '=', 'patients.id')
                    ->join('therapies', 'appointments.therapy_id', '=', 'therapies.id')
                    ->select(
                        DB::raw("DATE_FORMAT(appointment_date, '%m/%Y') as reference_month"),                        'patients.name as patient_name',
                        'therapies.name as therapy_name',
                        DB::raw('SUM(session_number) as total_sessions')
                    )
                    ->groupBy('reference_month', 'patients.id', 'therapies.id', 'patient_name', 'therapy_name')
                    ->orderBy('reference_month', 'desc')
                    ->orderBy('patient_name', 'asc')
            )
            ->columns([
                TextColumn::make('reference_month')
                    ->label('MÊS DE REFERÊNCIA')
                    ->sortable(),

                TextColumn::make('patient_name')
                    ->label('PACIENTE')
                    ->searchable(),

                TextColumn::make('therapy_name')
                    ->label('TERAPIA')
                    ->searchable(),

                TextColumn::make('total_sessions')
                    ->label('TOTAL DE SESSÕES')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('patient')
                    ->relationship('patient', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Paciente'),
                SelectFilter::make('therapy')
                    ->relationship('therapy', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Terapia'),
                    SelectFilter::make('mes')
                    ->label('Mês')
                    ->options([
                        '01' => 'Janeiro',
                        '02' => 'Fevereiro',
                        '03' => 'Março',
                        '04' => 'Abril',
                        '05' => 'Maio',
                        '06' => 'Junho',
                        '07' => 'Julho',
                        '08' => 'Agosto',
                        '09' => 'Setembro',
                        '10' => 'Outubro',
                        '11' => 'Novembro',
                        '12' => 'Dezembro',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $value): Builder => $query->whereMonth('appointments.appointment_date', $value)
                        );
                    }),
                ],layout: FiltersLayout::AboveContentCollapsible)
            ->filtersTriggerAction(
                fn ($action) => $action
                ->button()
                ->label('Filtros')
                ->slideOver()
                ->icon('heroicon-m-chevron-down')
            );
    }

    public function getTableRecordKey(Model | array $record): string
    {
        $month = data_get($record, 'reference_month');
        $patient = data_get($record, 'patient_name');
        $therapy = data_get($record, 'therapy_name');

        return (string) ($month . '-' . $patient . '-' . $therapy);
    }
}