<?php

namespace App\Filament\Pages;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Unit;
use App\Models\Professional;
use App\Models\PatientService;
use App\Models\ServiceType;
use App\Models\Visit;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use Carbon\Carbon;
use UnitEnum;

class AbaMonitoring extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static string|UnitEnum|null $navigationGroup = 'Coordenação/Supervisão';
    protected static ?string $navigationLabel = 'Cronograma de Coordenação';
    protected static ?string $title = 'Auditoria: Monitoramento ABA';
    protected static ?string $slug = 'aba-monitoring';
    protected string $view = 'filament.pages.aba-monitoring';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return true; 
    }

    public function getTableTabs(): array
    {
        $tabs = [
            'todos' => Tab::make('Todos os Ambientes'),
        ];

        $serviceTypes = ServiceType::all();
        foreach ($serviceTypes as $type) {
            $tabs[$type->id] = Tab::make($type->name)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('service_type_id', $type->id));
        }

        return $tabs;
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();
        
        return $table
            ->query( #essa query vÊ os pacientes que tem alguma visita ABA ou algum atendimento
                PatientService::query()
                    ->whereHas('patient', function ($q) use ($user) {
                        if (!$user->isAdmin() && !$user->isManager() && $user->unit_id) {
                            $q->where('unit_id', $user->unit_id);
                        }
                    })
                    ->where(function ($query) {                      
                        $query->whereExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                  ->from('appointments')
                                  ->join('therapies', 'appointments.therapy_id', '=', 'therapies.id')
                                  ->whereColumn('appointments.patient_id', 'patient_services.patient_id')
                                  ->whereColumn('appointments.service_type_id', 'patient_services.service_type_id')
                                  ->where('therapies.name', 'ABA');
                        })
                        ->orWhereExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                  ->from('visits')
                                  ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                                  ->whereRaw('(visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)');
                        });
                        
                    })
                    ->with(['patient', 'serviceType', 'coordinator', 'supervisor'])
            )
            ->columns([
                TextColumn::make('patient.name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable()
                    ->description(fn (PatientService $record) => $record->serviceType->name),
                TextColumn::make('coordination_status')
                    ->label('Coordenação (Meta: 10)')
                    ->sortable(query: function (Builder $query, string $direction) {
                        $type = VisitType::Coordination->value;
                        $status = VisitStatus::Completed->value;
                        
                        return $query->orderByRaw("(
                            SELECT COUNT(DISTINCT DATE(appointment_date))
                            FROM appointments
                            WHERE appointments.patient_id = patient_services.patient_id
                              AND appointments.service_type_id = patient_services.service_type_id
                              AND appointments.appointment_date <= CURRENT_DATE
                              AND appointments.therapy_id IN (SELECT id FROM therapies WHERE name = 'ABA')
                              AND appointments.appointment_date > COALESCE((
                                  SELECT happened_at FROM visits
                                  WHERE visits.patient_id = patient_services.patient_id
                                    AND (visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)
                                    AND visits.type = '{$type}'
                                    AND visits.status = '{$status}'
                                  ORDER BY happened_at DESC LIMIT 1
                              ), '2000-01-01')
                        ) {$direction}");
                    })
                    ->getStateUsing(function (PatientService $record) {
                        $lastVisit = Visit::where('patient_id', $record->patient_id)
                            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                            ->where('type', VisitType::Coordination->value)
                            ->where('status', VisitStatus::Completed->value)
                            ->latest('happened_at')
                            ->first();

                        $startDate = $lastVisit ? $lastVisit->happened_at : null;
                        
                        $query = Appointment::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->where('appointment_date', '<=', Carbon::today())
                            ->whereHas('therapy', fn ($q) => $q->where('name', 'ABA'));

                        if ($startDate) {
                            $query->where('appointment_date', '>', $startDate);
                        }

                        $daysCount = $query->select(DB::raw('DATE(appointment_date) as date'))->groupBy('date')->get()->count();

                        if ($daysCount > 0 && empty($record->coordinator_id)) {
                             return "🚨 Sem coordenador cadastrado ({$daysCount}/10 dias)";
                        }
                        $hasPending = Visit::where('patient_id', $record->patient_id)
                            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                            ->where('type', VisitType::Coordination->value)
                            ->where('status', VisitStatus::Pending->value)
                            ->exists();

                        if ($hasPending) return '⚠️ Visita Pendente';

                        if ($daysCount === 0) return '✅ Em dia (0 dias)';
                        
                        return "⏳ {$daysCount} / 10 dias";
                    })
                    ->description(function (PatientService $record) {
                        $lastVisit = Visit::with('professional')
                            ->where('patient_id', $record->patient_id)
                            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                            ->where('type', VisitType::Coordination->value)
                            ->where('status', VisitStatus::Completed->value)
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
                        str_starts_with($state, '🚨') => 'danger',
                        $state === '⚠️ Visita Pendente' => 'warning',
                        $state === '✅ Em dia (0 dias)' => 'success',
                        default => 'info',
                    }),

                TextColumn::make('supervision_status')
                    ->label('Supervisão (Meta: 20)')
                    ->sortable(query: function (Builder $query, string $direction) {
                        $type = VisitType::Supervision->value;
                        $status = VisitStatus::Completed->value;
                        
                        return $query->orderByRaw("(
                            SELECT COUNT(DISTINCT DATE(appointment_date))
                            FROM appointments
                            WHERE appointments.patient_id = patient_services.patient_id
                              AND appointments.service_type_id = patient_services.service_type_id
                              AND appointments.appointment_date <= CURRENT_DATE
                              AND appointments.therapy_id IN (SELECT id FROM therapies WHERE name = 'ABA')
                              AND appointments.appointment_date > COALESCE((
                                  SELECT happened_at FROM visits
                                  WHERE visits.patient_id = patient_services.patient_id
                                    AND (visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)
                                    AND visits.type = '{$type}'
                                    AND visits.status = '{$status}'
                                  ORDER BY happened_at DESC LIMIT 1
                              ), '2000-01-01')
                        ) {$direction}");
                    })
                    ->getStateUsing(function (PatientService $record) {
                        $lastVisit = Visit::where('patient_id', $record->patient_id)
                            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                            ->where('type', VisitType::Supervision->value)
                            ->where('status', VisitStatus::Completed->value)
                            ->latest('happened_at')
                            ->first();

                        $startDate = $lastVisit ? $lastVisit->happened_at : null;

                        $query = Appointment::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->where('appointment_date', '<=', Carbon::today())
                            ->whereHas('therapy', fn ($q) => $q->where('name', 'ABA'));

                        if ($startDate) {
                            $query->where('appointment_date', '>', $startDate);
                        }

                        $daysCount = $query->select(DB::raw('DATE(appointment_date) as date'))->groupBy('date')->get()->count();

                        if ($daysCount > 0 && empty($record->supervisor_id)) {
                             return "🚨 Sem supervisor cadastrado ({$daysCount}/20 dias)";
                        }

                        $hasPending = Visit::where('patient_id', $record->patient_id)
                            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                            ->where('type', VisitType::Supervision->value)
                            ->where('status', VisitStatus::Pending->value)
                            ->exists();

                        if ($hasPending) return '⚠️ Visita Pendente';

                        if ($daysCount === 0) return '✅ Em dia (0 dias)';
                        
                        return "⏳ {$daysCount} / 20 dias";
                    })
                    ->description(function (PatientService $record) {
                        $lastVisit = Visit::with('professional')
                            ->where('patient_id', $record->patient_id)
                            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                            ->where('type', VisitType::Supervision->value)
                            ->where('status', VisitStatus::Completed->value)
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
                        str_starts_with($state, '🚨') => 'danger',
                        $state === '⚠️ Visita Pendente' => 'warning',
                        $state === '✅ Em dia (0 dias)' => 'success',
                        default => 'info',
                    }),
            ])
            ->filters([
                SelectFilter::make('service_type_id')
                    ->label('Ambiente (ABA)')
                    ->options(ServiceType::pluck('name', 'id'))
                    ->placeholder('Todos os Ambientes')
                    ->hidden(),

                SelectFilter::make('unit_id')
                    ->label('Unidade')
                    ->options(Unit::pluck('city', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        return $query->whereHas('patient', fn($q) => $q->where('unit_id', $data['value']));
                    }),
                SelectFilter::make('professional')
                    ->label('Profissional')
                    ->options(Professional::whereIn('role', ['coordinator', 'supervisor'])->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        return $query->where(function ($q) use ($data) {
                            $q->where('coordinator_id', $data['value'])
                              ->orWhere('supervisor_id', $data['value']);
                        });
                    }),

                SelectFilter::make('status_coordenacao')
                    ->label('Status Coordenação')
                    ->options([
                        'em_dia' => '✅ Em dia (0 dias)',
                        'sem_coordenador' => '🚨 Sem coordenador cadastrado',
                        'pendente' => '⚠️ Visita Pendente',
                        'em_andamento' => '⏳ Em andamento (1 a 9 dias)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        
                        $type = VisitType::Coordination->value;
                        $completedStatus = VisitStatus::Completed->value;
                        $pendingStatus = VisitStatus::Pending->value;

                        $daysCountSql = "(
                            SELECT COUNT(DISTINCT DATE(appointment_date))
                            FROM appointments
                            WHERE appointments.patient_id = patient_services.patient_id
                              AND appointments.service_type_id = patient_services.service_type_id
                              AND appointments.appointment_date <= CURRENT_DATE
                              AND appointments.therapy_id IN (SELECT id FROM therapies WHERE name = 'ABA')
                              AND appointments.appointment_date > COALESCE((
                                  SELECT happened_at FROM visits
                                  WHERE visits.patient_id = patient_services.patient_id
                                    AND (visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)
                                    AND visits.type = '{$type}'
                                    AND visits.status = '{$completedStatus}'
                                  ORDER BY happened_at DESC LIMIT 1
                              ), '2000-01-01')
                        )";

                        return match ($data['value']) {
                            'sem_coordenador' => $query->whereNull('coordinator_id')->whereRaw("{$daysCountSql} > 0"),
                            
                            'pendente' => $query->whereHas('patient', function ($q) use ($type, $pendingStatus) {
                                $q->whereExists(function ($sub) use ($type, $pendingStatus) {
                                    $sub->select(DB::raw(1))
                                        ->from('visits')
                                        ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                                        ->whereRaw('(visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)')
                                        ->where('type', $type)
                                        ->where('status', $pendingStatus);
                                });
                            }),

                            'em_dia' => $query->whereRaw("{$daysCountSql} = 0")
                                              ->whereNotExists(function ($sub) use ($type, $pendingStatus) {
                                                  $sub->select(DB::raw(1))->from('visits')
                                                      ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                                                      ->whereRaw('(visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)')
                                                      ->where('type', $type)->where('status', $pendingStatus);
                                              }),

                            'em_andamento' => $query->whereRaw("{$daysCountSql} > 0")
                                                    ->whereRaw("{$daysCountSql} < 10")
                                                    ->whereNotNull('coordinator_id')
                                                    ->whereNotExists(function ($sub) use ($type, $pendingStatus) {
                                                        $sub->select(DB::raw(1))->from('visits')
                                                            ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                                                            ->whereRaw('(visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)')
                                                            ->where('type', $type)->where('status', $pendingStatus);
                                                    }),
                            
                            default => $query,
                        };
                    }),

                SelectFilter::make('status_supervisao')
                    ->label('Status Supervisão')
                    ->options([
                        'em_dia' => '✅ Em dia (0 dias)',
                        'sem_supervisor' => '🚨 Sem supervisor cadastrado',
                        'pendente' => '⚠️ Visita Pendente',
                        'em_andamento' => '⏳ Em andamento (1 a 19 dias)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        
                        $type = VisitType::Supervision->value;
                        $completedStatus = VisitStatus::Completed->value;
                        $pendingStatus = VisitStatus::Pending->value;

                        $daysCountSql = "(
                            SELECT COUNT(DISTINCT DATE(appointment_date))
                            FROM appointments
                            WHERE appointments.patient_id = patient_services.patient_id
                              AND appointments.service_type_id = patient_services.service_type_id
                              AND appointments.appointment_date <= CURRENT_DATE
                              AND appointments.therapy_id IN (SELECT id FROM therapies WHERE name = 'ABA')
                              AND appointments.appointment_date > COALESCE((
                                  SELECT happened_at FROM visits
                                  WHERE visits.patient_id = patient_services.patient_id
                                    AND (visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)
                                    AND visits.type = '{$type}'
                                    AND visits.status = '{$completedStatus}'
                                  ORDER BY happened_at DESC LIMIT 1
                              ), '2000-01-01')
                        )";

                        return match ($data['value']) {
                            'sem_supervisor' => $query->whereNull('supervisor_id')->whereRaw("{$daysCountSql} > 0"),
                            
                            'pendente' => $query->whereExists(function ($sub) use ($type, $pendingStatus) {
                                $sub->select(DB::raw(1))->from('visits')
                                    ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                                    ->whereRaw('(visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)')
                                    ->where('type', $type)->where('status', $pendingStatus);
                            }),

                            'em_dia' => $query->whereRaw("{$daysCountSql} = 0")
                                              ->whereNotExists(function ($sub) use ($type, $pendingStatus) {
                                                  $sub->select(DB::raw(1))->from('visits')
                                                      ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                                                      ->whereRaw('(visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)')
                                                      ->where('type', $type)->where('status', $pendingStatus);
                                              }),

                            'em_andamento' => $query->whereRaw("{$daysCountSql} > 0")
                                                    ->whereRaw("{$daysCountSql} < 20")
                                                    ->whereNotNull('supervisor_id')
                                                    ->whereNotExists(function ($sub) use ($type, $pendingStatus) {
                                                        $sub->select(DB::raw(1))->from('visits')
                                                            ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                                                            ->whereRaw('(visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)')
                                                            ->where('type', $type)->where('status', $pendingStatus);
                                                    }),
                            
                            default => $query,
                        };
                    }),
            ], layout: FiltersLayout::AboveContent);
    }
}