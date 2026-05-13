<?php

namespace App\Filament\Producao\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use App\Models\Appointment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Producao\Clusters\Conciliacao;
use Filament\Schemas\Components\Grid;
use App\Filament\Producao\Widgets\RastreabilidadeStats;
use Carbon\Carbon;

class Rastreabilidade extends Page implements HasTable
{
    use InteractsWithTable;
    use ExposesTableToWidgets;

    // A MÁGICA QUE RESOLVE O SEU ERRO ESTÁ AQUI:
    // O Filament precisa dessa variável para o "ExposesTableToWidgets" não quebrar.
    public ?string $activeTab = null;
    public $parentRecord = null;

    protected static ?string $cluster = Conciliacao::class;
    protected static ?string $title = 'Base de Dados Rastreabilidade';
    
    // Apontando para o Blade limpo que tem apenas o <x-filament-panels::page>
    protected string $view = 'filament.producao.pages.rastreabilidade';

    protected function getHeaderWidgets(): array
    {
        return [
            RastreabilidadeStats::class,
        ];
    }

    public function updated($property): void
    {
        if (str_starts_with($property, 'tableFilters') || str_starts_with($property, 'tableSearch')) {
            $this->dispatch('atualizar-cards');
        }
    }

    protected function getHeaderWidgetsData(): array
    {
        return [
            'filtrosDaTabela' => $this->tableFilters,
            'buscaDaTabela' => $this->tableSearch,
        ];
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $query = Appointment::query()->with(['patient.agreement', 'patient.unit', 'therapy', 'serviceType', 'professional']);

        // Trava de segurança da unidade
        if (!$user->isAdmin()) {
            $unidadesDoUsuario = $user->units->pluck('id')->toArray();
            $query->whereHas('patient', function ($q) use ($unidadesDoUsuario) {
                $q->whereIn('unit_id', $unidadesDoUsuario);
            });
        }

        return $table
            ->query($query)
            ->recordUrl(null)
            ->recordAction(null)
            ->columns([
                TextColumn::make('appointment_date')->label('Data')->date('d/m/Y')->searchable()->sortable(),
                TextColumn::make('patient.name')->label('Beneficiário')->searchable()->sortable(),
                TextColumn::make('guide')->label('Guia')->searchable(),
                TextColumn::make('procedimento_completo')
                    ->label('Procedimento')
                    ->state(fn (Appointment $record) => ($record->therapy->name ?? '') . ' - ' . ($record->serviceType->name ?? 'Clínica'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('therapy', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                                     ->orWhereHas('serviceType', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                    }),
                TextColumn::make('professional.name')->label('Profissional')->searchable()->sortable(),
                TextColumn::make('session_number')->label('Qt.')->sortable(),
                TextColumn::make('check_in')->label('Checkin')->time('H:i')->sortable(),
                TextColumn::make('check_out')
                    ->label('Checkout')
                    ->state(function (Appointment $record) {
                        // Se tem checkout, formata a hora manualmente
                        if ($record->check_out) {
                            return Carbon::parse($record->check_out)->format('H:i');
                        }
                        // Se não tem, mostra o aviso
                        return $record->check_in ? 'Sem Checkout' : '-';
                    })
                    // Se não tiver checkout, transforma o visual numa etiqueta
                    ->badge(fn (Appointment $record) => $record->check_in && empty($record->check_out))
                    // Pinta a etiqueta de vermelho (danger)
                    ->color(fn (Appointment $record) => $record->check_in && empty($record->check_out) ? 'danger' : 'gray')
                    // Coloca o ícone de perigo
                    ->icon(fn (Appointment $record) => $record->check_in && empty($record->check_out) ? 'heroicon-m-exclamation-triangle' : null)
                    ->sortable(),
                
                TextColumn::make('duracao')
                    ->label('Duração')
                    ->state(function (Appointment $record) {
                        if ($record->check_in && $record->check_out) {
                            $in = Carbon::parse($record->check_in);
                            $out = Carbon::parse($record->check_out);
                            return $in->diff($out)->format('%H:%I');
                        }
                        return $record->check_in ? 'Pendente' : '-';
                    })
                    ->color(fn (Appointment $record) => $record->check_in && empty($record->check_out) ? 'danger' : null),
            ])
            ->filters([
                SelectFilter::make('patient')->relationship('patient', 'name')->searchable()->preload()->label('Paciente'),
                SelectFilter::make('professional')->relationship('professional', 'name')->searchable()->preload()->label('Profissional'),
                SelectFilter::make('agreement')->relationship('patient.agreement', 'name')->searchable()->preload()->label('Convênio'),
                SelectFilter::make('therapy')->relationship('therapy', 'name')->searchable()->preload()->label('Terapia'),
                SelectFilter::make('serviceType')->relationship('serviceType', 'name')->searchable()->preload()->label('Tipo de Atendimento'),
                SelectFilter::make('unit')->relationship('patient.unit', 'city')->searchable()->preload()->multiple()->label('Unidade'),
                Filter::make('appointment_date')
                    ->columnSpan(2)
                    ->form([
                        Grid::make(2)->schema([
                            DatePicker::make('date_from')->label('Data Início'),
                            DatePicker::make('date_until')->label('Data Fim'),
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'], fn (Builder $query, $date): Builder => $query->whereDate('appointment_date', '>=', $date))
                            ->when($data['date_until'], fn (Builder $query, $date): Builder => $query->whereDate('appointment_date', '<=', $date));
                    })
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->defaultSort('appointment_date', 'desc');
    }
}