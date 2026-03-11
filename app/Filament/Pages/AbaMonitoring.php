<?php

namespace App\Filament\Pages;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientService;
use App\Models\Visit;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use BackedEnum;
use UnitEnum;

class AbaMonitoring extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static string|UnitEnum|null $navigationGroup = 'Administração';
    protected static ?string $navigationLabel = 'Monitoramento ABA';
    protected static ?string $title = 'Auditoria: Monitoramento ABA';
    protected static ?string $slug = 'aba-monitoring';
    protected string $view = 'filament.pages.aba-monitoring';

    public static function canAccess(): bool
    {
        return auth()->user()?->id === 1;
    }

    // === AS FAMOSAS ABAS (TABS) MÁGICAS ===
    public function getTableTabs(): array
        {
        $tabs = [
            'todos' => Tab::make('Todos os Ambientes'),
        ];

        // Cria uma aba dinamicamente para cada tipo de serviço (Clínica, Escola, etc)
        $serviceTypes = ServiceType::all();
        foreach ($serviceTypes as $type) {
            $tabs[$type->id] = Tab::make($type->name)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('service_type_id', $type->id));
        }

        return $tabs;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PatientService::query()->whereHas('patient')->with(['patient', 'serviceType', 'coordinator', 'supervisor']))
            ->columns([
                TextColumn::make('patient.name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable()
                    ->description(fn (PatientService $record) => $record->serviceType->name), // Mostra "Clínica" miúdo embaixo do nome

                // === COLUNA DE COORDENAÇÃO (10 DIAS) ===
                TextColumn::make('coordination_status')
                    ->label('Coordenação (Meta: 10)')
                    ->getStateUsing(function (PatientService $record) {
                        // Busca visitas pendentes para ESSE paciente NESTE ambiente
                        $hasPending = Visit::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->where('type', VisitType::Coordination)
                            ->where('status', VisitStatus::Pending)
                            ->exists();

                        if ($hasPending) return '⚠️ Visita Pendente';

                        $lastVisit = Visit::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->where('type', VisitType::Coordination)
                            ->where('status', VisitStatus::Completed)
                            ->latest('happened_at')
                            ->first();

                        $startDate = $lastVisit ? $lastVisit->happened_at : null;

                        // Conta os agendamentos de ABA apenas para ESSE ambiente
                        $query = Appointment::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->whereHas('therapy', fn ($q) => $q->where('name', 'ABA'));

                        if ($startDate) {
                            $query->where('appointment_date', '>', $startDate);
                        }

                        $daysCount = $query->select(DB::raw('DATE(appointment_date) as date'))->groupBy('date')->get()->count();

                        if ($daysCount === 0) return '✅ Em dia (0 dias)';
                        
                        return "⏳ {$daysCount} / 10 dias";
                    })
                    ->description(function (PatientService $record) {
                        $lastVisit = Visit::with('professional')
                            ->where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->where('type', VisitType::Coordination)
                            ->where('status', VisitStatus::Completed)
                            ->latest('happened_at')
                            ->first();

                        if ($lastVisit && $lastVisit->happened_at) {
                            $data = Carbon::parse($lastVisit->happened_at)->format('d/m/Y');
                            $nomeProfissional = $lastVisit->professional ? implode(' ', array_slice(explode(' ', $lastVisit->professional->name), 0, 2)) : 'Desconhecido';
                            return "Última: {$data} (com {$nomeProfissional})";
                        }
                        return 'Nenhuma visita registrada';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state === '⚠️ Visita Pendente' => 'warning',
                        $state === '✅ Em dia (0 dias)' => 'success',
                        default => 'info',
                    }),

                // === COLUNA DE SUPERVISÃO (20 DIAS) ===
                TextColumn::make('supervision_status')
                    ->label('Supervisão (Meta: 20)')
                    ->getStateUsing(function (PatientService $record) {
                        $hasPending = Visit::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->where('type', VisitType::Supervision)
                            ->where('status', VisitStatus::Pending)
                            ->exists();

                        if ($hasPending) return '⚠️ Visita Pendente';

                        $lastVisit = Visit::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->where('type', VisitType::Supervision)
                            ->where('status', VisitStatus::Completed)
                            ->latest('happened_at')
                            ->first();

                        $startDate = $lastVisit ? $lastVisit->happened_at : null;

                        $query = Appointment::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->whereHas('therapy', fn ($q) => $q->where('name', 'ABA'));

                        if ($startDate) {
                            $query->where('appointment_date', '>', $startDate);
                        }

                        $daysCount = $query->select(DB::raw('DATE(appointment_date) as date'))->groupBy('date')->get()->count();

                        if ($daysCount === 0) return '✅ Em dia (0 dias)';
                        
                        return "⏳ {$daysCount} / 20 dias";
                    })
                    ->description(function (PatientService $record) {
                        $lastVisit = Visit::with('professional')
                            ->where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->where('type', VisitType::Supervision)
                            ->where('status', VisitStatus::Completed)
                            ->latest('happened_at')
                            ->first();

                        if ($lastVisit && $lastVisit->happened_at) {
                            $data = Carbon::parse($lastVisit->happened_at)->format('d/m/Y');
                            $nomeProfissional = $lastVisit->professional ? implode(' ', array_slice(explode(' ', $lastVisit->professional->name), 0, 2)) : 'Desconhecido';
                            return "Última: {$data} (com {$nomeProfissional})";
                        }
                        return 'Nenhuma visita registrada';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state === '⚠️ Visita Pendente' => 'warning',
                        $state === '✅ Em dia (0 dias)' => 'success',
                        default => 'info',
                    }),
            ])
            ->filters([
                SelectFilter::make('service_type_id')
                    ->label('Ambiente (ABA)')
                    ->options(\App\Models\ServiceType::pluck('name', 'id'))
                    ->placeholder('Todos os Ambientes'),

                SelectFilter::make('unit_id')
                    ->label('Unidade')
                    ->options(\App\Models\Unit::pluck('city', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        // Busca através da relação do paciente
                        return $query->whereHas('patient', fn($q) => $q->where('unit_id', $data['value']));
                    }),
                SelectFilter::make('professional')
                    ->label('Profissional')
                    ->options(\App\Models\Professional::whereIn('role', ['coordinator', 'supervisor'])->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        // Como a tabela agora é PatientService, a busca ficou super direta!
                        return $query->where(function ($q) use ($data) {
                            $q->where('coordinator_id', $data['value'])
                              ->orWhere('supervisor_id', $data['value']);
                        });
                    }),
            ], layout: FiltersLayout::AboveContent);
    }
}