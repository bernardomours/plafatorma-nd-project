<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Widgets\AppointmentStats;
use App\Filament\Resources\Appointments\Widgets\AppointmentsByTypeChart;
use App\Filament\Resources\Appointments\Widgets\AppointmentsPerDayChart;
use App\Filament\Resources\Appointments\Widgets\AppointmentsByAgreementChart;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Therapy;
use App\Models\Agreement;
use Filament\Resources\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Barryvdh\DomPDF\Facade\Pdf;


class AttendanceReports extends Page implements HasTable
{
    use InteractsWithTable, InteractsWithForms;

    protected static string $resource = AppointmentResource::class;
    protected static ?string $title = 'Relatórios de Atendimento';
    protected string $view = 'filament.resources.appointments.pages.attendance-reports';

    public ?string $mes = null;
    public ?string $ano = null;
    public ?string $patient_id = null;
    public ?string $therapy_id = null;
    public ?array $unidades = [];
    public ?string $agreement_id = null; // <-- NOVO: Variável do Convênio

    public function mount(): void
    {
        $this->mes = date('m');
        $this->ano = date('Y');
        
        if (method_exists($this, 'fillSchemas')) {
             $this->fillSchemas();
        } elseif (method_exists($this->form, 'fill')) {
             $this->form->fill();
        }
    }

    // O FORMULÁRIO DE FILTRO NO TOPO
    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->statePath('')
            ->components([
                \Filament\Schemas\Components\Section::make('Filtros Gerenciais')
                    ->schema([
                        Select::make('mes')
                            ->label('Mês')
                            ->options([
                                '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
                                '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
                                '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
                                '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro',
                            ]),
                        
                        Select::make('ano')
                            ->label('Ano')
                            ->options(['2025' => '2025', '2026' => '2026', '2027' => '2027']),

                        Select::make('agreement_id') // <-- NOVO: Filtro de Convênio
                            ->label('Convênio')
                            ->options(\App\Models\Agreement::pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Todos os convênios'),

                        Select::make('patient_id')
                            ->label('Paciente')
                            ->options(\App\Models\Patient::pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Todos os pacientes'),

                        Select::make('therapy_id')
                            ->label('Terapia')
                            ->options(\App\Models\Therapy::pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Todas as terapias'),
                            
                        Select::make('unidades')
                            ->label('Unidade(s)')
                            ->options(\App\Models\Unit::pluck('city', 'id')) 
                            ->multiple()
                            ->searchable()
                            ->placeholder('Todas as unidades'),
                            
                    ])->columns(3), // <-- Alterado de 5 para 3 para criar duas linhas organizadas
            ]);
    }

    public function aplicarFiltros(): void
    {
        $this->dispatch('atualizar-relatorio', 
            mes: $this->mes, 
            ano: $this->ano, 
            patient_id: $this->patient_id, 
            therapy_id: $this->therapy_id,
            unidades: $this->unidades,
            agreement_id: $this->agreement_id // <-- NOVO: Enviando para os gráficos
        );
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AppointmentStats::class,
            AppointmentsPerDayChart::class,
            AppointmentsByTypeChart::class,
            AppointmentsByAgreementChart::class,
        ];
    }

    protected function getHeaderWidgetsData(): array
    {
        return [
            'mes' => $this->mes ?: date('m'),
            'ano' => $this->ano ?: date('Y'),
            'patient_id' => $this->patient_id,
            'therapy_id' => $this->therapy_id,
            'unidades' => $this->unidades, 
            'agreement_id' => $this->agreement_id, // <-- NOVO: Disponibilizando para os gráficos
        ];
    }

    // A TABELA
    public function table(Table $table): Table
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        return $table
            ->query(
                Appointment::query()
                    ->join('patients', 'appointments.patient_id', '=', 'patients.id')
                    ->join('therapies', 'appointments.therapy_id', '=', 'therapies.id')
                    ->when($this->mes, fn($q) => $q->whereMonth('appointments.appointment_date', $this->mes))
                    ->when($this->ano, fn($q) => $q->whereYear('appointments.appointment_date', $this->ano))
                    ->when($this->patient_id, fn($q) => $q->where('appointments.patient_id', $this->patient_id))
                    ->when($this->therapy_id, fn($q) => $q->where('appointments.therapy_id', $this->therapy_id))
                    ->when(!empty($this->unidades), fn($q) => $q->whereHas('patient', fn($queryPaciente) => $queryPaciente->whereIn('unit_id', $this->unidades)))
                    // <-- NOVO: O Filtro de Convênio aplicado na tabela!
                    ->when($this->agreement_id, fn($q) => $q->whereHas('patient', fn($queryPaciente) => $queryPaciente->where('agreement_id', $this->agreement_id)))
                    ->select(
                        $isSqlite 
                            ? DB::raw("strftime('%m/%Y', appointments.appointment_date) as reference_month")
                            : DB::raw("DATE_FORMAT(appointments.appointment_date, '%m/%Y') as reference_month"),
                        'patients.name as patient_name',
                        'therapies.name as therapy_name',
                        DB::raw('SUM(appointments.session_number) as total_sessions')
                    )
                    ->groupBy('reference_month', 'patients.id', 'therapies.id', 'patients.name', 'therapies.name')
                    ->orderBy('reference_month', 'desc')
                    ->orderBy('patients.name', 'asc')
            )
            ->columns([
                TextColumn::make('reference_month')->label('MÊS')->sortable(),
                TextColumn::make('patient_name')->label('PACIENTE')->searchable(),
                TextColumn::make('therapy_name')->label('TERAPIA')->searchable(),
                TextColumn::make('total_sessions')->label('TOTAL DE SESSÕES')->sortable(),
            ]);
    }

    public function getTableRecordKey(\Illuminate\Database\Eloquent\Model | array $record): string
    {
        return data_get($record, 'reference_month') . '-' . data_get($record, 'patient_name') . '-' . data_get($record, 'therapy_name');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export_pdf')
                ->label('Exportar para PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->form([
                    \Filament\Forms\Components\Select::make('mes')
                        ->label('Mês de Referência')
                        ->options([
                            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
                            '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
                            '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
                            '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro',
                        ])
                        ->default(date('m'))
                        ->required(),
                    
                    \Filament\Forms\Components\Select::make('ano')
                        ->label('Ano de Referência')
                        ->options([
                            '2025' => '2025', 
                            '2026' => '2026', 
                            '2027' => '2027'
                        ])
                        ->default(date('Y'))
                        ->required(),

                    // <-- NOVO: Adicionado também no PDF
                    \Filament\Forms\Components\Select::make('agreement_id')
                        ->label('Convênio')
                        ->options(\App\Models\Agreement::pluck('name', 'id'))
                        ->searchable()
                        ->placeholder('Todos os convênios'),

                    \Filament\Forms\Components\Select::make('unidades')
                        ->label('Unidade(s)')
                        ->options(\App\Models\Unit::pluck('city', 'id')) 
                        ->multiple() 
                        ->searchable()
                        ->placeholder('Todas as unidades'),
               ])
               ->action(function (array $data) {
                    $mes = $data['mes'];
                    $ano = $data['ano'];
                    $unidades = $data['unidades'] ?? []; 
                    $agreementId = $data['agreement_id'] ?? null;

                    // 1. Filtramos Mês, Ano, Unidades e Convênio
                    $query = Appointment::query()
                        ->whereMonth('appointments.appointment_date', $mes)
                        ->whereYear('appointments.appointment_date', $ano)
                        ->when($this->therapy_id, fn ($q) => $q->where('appointments.therapy_id', $this->therapy_id))
                        ->when(!empty($unidades), fn ($q) => $q->whereHas('patient', fn ($queryPaciente) => $queryPaciente->whereIn('unit_id', $unidades)))
                        // <-- NOVO: Filtro aplicado aos números do PDF
                        ->when($agreementId, fn ($q) => $q->whereHas('patient', fn ($queryPaciente) => $queryPaciente->where('agreement_id', $agreementId)));

                    // 2. Calculamos as ESTATÍSTICAS
                    $totalSessoes = (clone $query)->sum('session_number');
                    $totalAppointments = (clone $query)->count(); 
                    
                    $startDate = \Carbon\Carbon::createFromDate($ano, $mes, 1)->startOfMonth();
                    $endDate = $startDate->copy()->endOfMonth();
                    $diasNoMes = $startDate->diffInDays($endDate) + 1;
                    $mediaDiaria = ($diasNoMes > 0) ? number_format($totalAppointments / $diasNoMes, 2, ',', '.') : '0,00';

                    $sessoesPorTerapia = (clone $query)
                        ->join('therapies', 'appointments.therapy_id', '=', 'therapies.id')
                        ->select('therapies.name', DB::raw('SUM(appointments.session_number) as total'))
                        ->groupBy('therapies.name')
                        ->pluck('total', 'therapies.name');

                    $sessoesPorConvenio = (clone $query)
                        ->join('patients', 'appointments.patient_id', '=', 'patients.id')
                        ->join('agreements', 'patients.agreement_id', '=', 'agreements.id')
                        ->select('agreements.name', DB::raw('SUM(appointments.session_number) as total'))
                        ->groupBy('agreements.name')
                        ->pluck('total', 'agreements.name');

                    // 3. A CORREÇÃO DO ZERO À ESQUERDA PARA O GRÁFICO DO PDF
                    $isSqlite = DB::connection()->getDriverName() === 'sqlite';
                    $evolucaoDiaria = (clone $query)->select(
                        $isSqlite 
                            ? DB::raw("strftime('%d', appointments.appointment_date) as dia")
                            : DB::raw("DATE_FORMAT(appointments.appointment_date, '%d') as dia"),
                        DB::raw('SUM(appointments.session_number) as total')
                    )
                    ->groupBy('dia')
                    ->pluck('total', 'dia');

                    // 4. Buscamos os DADOS DA TABELA principal
                    $resumo = (clone $query)
                        ->join('patients', 'appointments.patient_id', '=', 'patients.id')
                        ->join('therapies', 'appointments.therapy_id', '=', 'therapies.id')
                        ->select(
                            $isSqlite 
                                ? DB::raw("strftime('%m/%Y', appointments.appointment_date) as reference_month")
                                : DB::raw("DATE_FORMAT(appointments.appointment_date, '%m/%Y') as reference_month"),
                            'patients.name as patient_name',
                            'therapies.name as therapy_name',
                            DB::raw('SUM(appointments.session_number) as total_sessions')
                        )
                        ->groupBy('reference_month', 'patients.id', 'therapies.id', 'patients.name', 'therapies.name')
                        ->orderBy('patients.name', 'asc')
                        ->get();

                    // 5. Injetamos tudo no PDF
                    $pdf = Pdf::loadView('pdf.monthly-summary-pdf', [
                        'mesSelecionado' => $mes,
                        'anoSelecionado' => $ano,
                        'resumo' => $resumo,
                        'totalSessoes' => $totalSessoes,
                        'mediaDiaria' => $mediaDiaria,
                        'sessoesPorTerapia' => $sessoesPorTerapia,
                        'sessoesPorConvenio' => $sessoesPorConvenio,
                        'evolucaoDiaria' => $evolucaoDiaria,
                    ]);

                    return response()->streamDownload(fn () => print($pdf->output()), "relatorio-atendimentos-{$mes}-{$ano}.pdf");
               })
        ];
    }
}