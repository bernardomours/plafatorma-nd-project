<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Widgets\AppointmentStats;
use App\Filament\Resources\Appointments\Widgets\AppointmentsByTypeChart;
use App\Filament\Resources\Appointments\Widgets\AppointmentsPerDayChart;
use App\Models\Appointment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceReports extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static string $resource = AppointmentResource::class;
    protected static ?string $title = 'Relatórios de Atendimento';
    protected string $view = 'filament.resources.appointments.pages.attendance-reports';

    public ?array $data = [];

    public function mount(): void
    {
        // Define as datas padrão no carregamento da página
        $this->form->fill([
            'startDate' => now()->startOfMonth(),
            'endDate' => now()->endOfMonth(),
        ]);
    }

    // Define o formulário de filtros
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filtros Globais')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Data de Início')
                            ->default(now()->startOfMonth())
                            ->reactive(),

                        DatePicker::make('endDate')
                            ->label('Data de Fim')
                            ->default(now()->endOfMonth())
                            ->reactive(),
                    ])
            ])
            ->statePath('data'); 
    }

    // Passa os filtros para os widgets
    protected function getHeaderWidgets(): array
    {
        return [
            AppointmentStats::class,
            AppointmentsPerDayChart::class,
            AppointmentsByTypeChart::class,
        ];
    }
    
    // Método para expor os filtros aos widgets
    public function getFilters(): ?array
    {
        return $this->form->getState();
    }

    // Define a tabela de dados
    public function table(Table $table): Table
    {
        return $table
            ->query(
                // A query agora usa os filtros do formulário
                Appointment::query()
                    ->join('patients', 'appointments.patient_id', '=', 'patients.id')
                    ->join('therapies', 'appointments.therapy_id', '=', 'therapies.id')
                    ->when($this->data['startDate'], fn (Builder $query, $date) => $query->whereDate('appointments.appointment_date', '>=', $date))
                    ->when($this->data['endDate'], fn (Builder $query, $date) => $query->whereDate('appointments.appointment_date', '<=', $date))
                    ->select(
                        DB::raw('strftime(\'%m/%Y\', appointment_date) as reference_month'),
                        'patients.name as patient_name',
                        'therapies.name as therapy_name',
                        DB::raw('SUM(session_number) as total_sessions')
                    )
                    ->groupBy('reference_month', 'patients.id', 'therapies.id', 'patient_name', 'therapy_name')
                    ->orderBy('reference_month', 'desc')
                    ->orderBy('patient_name', 'asc')
            )
            ->columns([
                TextColumn::make('reference_month')->label('MÊS DE REFERÊNCIA'),
                TextColumn::make('patient_name')->label('PACIENTE'),
                TextColumn::make('therapy_name')->label('TERAPIA'),
                TextColumn::make('total_sessions')->label('TOTAL DE SESSÕES'),
            ])
            // Removemos os filtros antigos daqui
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    // Oculta a paginação, já que a tabela é um resumo
    public function isTablePaginationEnabled(): bool
    {
        return false;
    }
}
