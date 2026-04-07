<?php

namespace App\Filament\Pages;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\PatientService;
use App\Models\Professional;
use App\Models\ServiceType;
use App\Models\Therapy;
use App\Models\Unit;
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
use BackedEnum;
use Carbon\Carbon;
use UnitEnum;

class AbaMonitoring extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static string|UnitEnum|null $navigationGroup = 'Coordenação/Supervisão';
    protected static ?string $navigationLabel = 'Cronograma de Terapias';
    protected static ?string $title = 'Auditoria: Monitoramento de Terapias Especiais';
    protected static ?string $slug = 'therapy-monitoring';
    protected string $view = 'filament.pages.aba-monitoring';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return true; 
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();
        
        $userUnits = $user->units->pluck('id')->toArray();
        $regioesPermitidas = [];
        if (in_array(1, $userUnits)) $regioesPermitidas[] = 1;
        if (array_intersect([2, 3, 4], $userUnits)) array_push($regioesPermitidas, 2, 3, 4);

        return $table
            ->query(
                PatientService::query()
                    ->whereHas('patient', function ($q) use ($user, $regioesPermitidas) {
                        if (!$user->isAdmin() && !$user->isManager()) {
                            if (empty($regioesPermitidas)) {
                                $q->whereRaw('1 = 0');
                            } else {
                                $q->whereIn('unit_id', $regioesPermitidas);
                            }
                        }
                    })
                    // Filtra apenas pacientes que têm histórico de ABA ou DENVER
                    ->whereExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))->from('appointments')
                            ->join('therapies', 'appointments.therapy_id', '=', 'therapies.id')
                            ->whereColumn('appointments.patient_id', 'patient_services.patient_id')
                            ->whereColumn('appointments.service_type_id', 'patient_services.service_type_id')
                            ->whereIn('therapies.name', ['ABA', 'DENVER']);
                    })
                    ->with(['patient', 'serviceType', 'coordinator', 'supervisor'])
            )
            ->columns([
                TextColumn::make('patient.name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable()
                    ->description(fn (PatientService $record) => $record->serviceType->name),

                // 👇 A NOVA COLUNA DE TERAPIA 👇
                TextColumn::make('terapia_atual')
                    ->label('Terapia')
                    ->getStateUsing(function (PatientService $record) {
                        $latest = Appointment::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->whereHas('therapy', fn($q) => $q->whereIn('name', ['ABA', 'DENVER']))
                            ->latest('appointment_date')
                            ->first();
                        return $latest ? $latest->therapy->name : 'N/A';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ABA' => 'info',
                        'DENVER' => 'warning',
                        default => 'gray',
                    }),

                // COORDENAÇÃO
                TextColumn::make('coordination_status')
                    ->label('Coordenação (Meta: 10)')
                    ->getStateUsing(function (PatientService $record) {
                        // Descobre a terapia mais recente para calcular
                        $latestAppt = Appointment::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->whereHas('therapy', fn($q) => $q->whereIn('name', ['ABA', 'DENVER']))
                            ->latest('appointment_date')->first();

                        if (!$latestAppt) return 'Sem registros';
                        $therapyId = $latestAppt->therapy_id;

                        $lastVisit = Visit::where('patient_id', $record->patient_id)
                            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                            ->where('type', VisitType::Coordination->value)
                            ->where('status', VisitStatus::Completed->value)
                            ->where('therapy_id', $therapyId)
                            ->latest('happened_at')->first();

                        $startDate = $lastVisit ? $lastVisit->happened_at : null;
                        
                        $query = Appointment::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->where('appointment_date', '<=', Carbon::today())
                            ->where('therapy_id', $therapyId);

                        if ($startDate) $query->where('appointment_date', '>', $startDate);

                        $daysCount = $query->select(DB::raw('DATE(appointment_date) as date'))->groupBy('date')->get()->count();

                        if ($daysCount > 0 && empty($record->coordinator_id)) return "🚨 Sem coordenador ({$daysCount}/10 dias)";
                        
                        $hasPending = Visit::where('patient_id', $record->patient_id)
                            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                            ->where('type', VisitType::Coordination->value)->where('status', VisitStatus::Pending->value)
                            ->where('therapy_id', $therapyId)->exists();

                        if ($hasPending) return '⚠️ Visita Pendente';
                        if ($daysCount === 0) return '✅ Em dia (0 dias)';
                        return "⏳ {$daysCount} / 10 dias";
                    })
                    ->description(function (PatientService $record) {
                        $latestAppt = Appointment::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->whereHas('therapy', fn($q) => $q->whereIn('name', ['ABA', 'DENVER']))
                            ->latest('appointment_date')->first();

                        if (!$latestAppt) return '';
                        $therapyId = $latestAppt->therapy_id;

                        $lastVisit = Visit::with('professional')->where('patient_id', $record->patient_id)
                            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                            ->where('type', VisitType::Coordination->value)->where('status', VisitStatus::Completed->value)
                            ->where('therapy_id', $therapyId)->latest('happened_at')->first();

                        if ($lastVisit && $lastVisit->happened_at) {
                            $data = Carbon::parse($lastVisit->happened_at)->format('d/m/Y');
                            $nome = $lastVisit->professional ? implode(' ', array_slice(explode(' ', $lastVisit->professional->name), 0, 2)) : 'Desconhecido';
                            return "Última: {$data} ({$nome})";
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

                // SUPERVISÃO
                TextColumn::make('supervision_status')
                    ->label('Supervisão (Meta: 20)')
                    ->getStateUsing(function (PatientService $record) {
                        $latestAppt = Appointment::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->whereHas('therapy', fn($q) => $q->whereIn('name', ['ABA', 'DENVER']))
                            ->latest('appointment_date')->first();

                        if (!$latestAppt) return 'Sem registros';
                        $therapyId = $latestAppt->therapy_id;

                        $lastVisit = Visit::where('patient_id', $record->patient_id)
                            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                            ->where('type', VisitType::Supervision->value)->where('status', VisitStatus::Completed->value)
                            ->where('therapy_id', $therapyId)->latest('happened_at')->first();

                        $startDate = $lastVisit ? $lastVisit->happened_at : null;

                        $query = Appointment::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->where('appointment_date', '<=', Carbon::today())
                            ->where('therapy_id', $therapyId);

                        if ($startDate) $query->where('appointment_date', '>', $startDate);

                        $daysCount = $query->select(DB::raw('DATE(appointment_date) as date'))->groupBy('date')->get()->count();

                        if ($daysCount > 0 && empty($record->supervisor_id)) return "🚨 Sem supervisor ({$daysCount}/20 dias)";

                        $hasPending = Visit::where('patient_id', $record->patient_id)
                            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                            ->where('type', VisitType::Supervision->value)->where('status', VisitStatus::Pending->value)
                            ->where('therapy_id', $therapyId)->exists();

                        if ($hasPending) return '⚠️ Visita Pendente';
                        if ($daysCount === 0) return '✅ Em dia (0 dias)';
                        return "⏳ {$daysCount} / 20 dias";
                    })
                    ->description(function (PatientService $record) {
                        $latestAppt = Appointment::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->whereHas('therapy', fn($q) => $q->whereIn('name', ['ABA', 'DENVER']))
                            ->latest('appointment_date')->first();

                        if (!$latestAppt) return '';
                        $therapyId = $latestAppt->therapy_id;

                        $lastVisit = Visit::with('professional')->where('patient_id', $record->patient_id)
                            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                            ->where('type', VisitType::Supervision->value)->where('status', VisitStatus::Completed->value)
                            ->where('therapy_id', $therapyId)->latest('happened_at')->first();

                        if ($lastVisit && $lastVisit->happened_at) {
                            $data = Carbon::parse($lastVisit->happened_at)->format('d/m/Y');
                            $nome = $lastVisit->professional ? implode(' ', array_slice(explode(' ', $lastVisit->professional->name), 0, 2)) : 'Desconhecido';
                            return "Última: {$data} ({$nome})";
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
                    ->label('Ambiente')
                    ->options(ServiceType::pluck('name', 'id'))
                    ->placeholder('Todos os Ambientes'),

                SelectFilter::make('unit_id')
                    ->label('Unidade')
                    ->options(function () {
                        $user = auth()->user();
                        $query = Unit::query();
                        if ($user->isAdmin() || $user->isManager()) return $query->pluck('city', 'id');

                        $userUnits = $user->units->pluck('id')->toArray();
                        $regioesPermitidas = [];
                        if (in_array(1, $userUnits)) $regioesPermitidas[] = 1;
                        if (array_intersect([2, 3, 4], $userUnits)) array_push($regioesPermitidas, 2, 3, 4);

                        if (empty($regioesPermitidas)) return [];
                        return $query->whereIn('id', $regioesPermitidas)->pluck('city', 'id');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        return $query->whereHas('patient', fn($q) => $q->where('unit_id', $data['value']));
                    }),

                SelectFilter::make('professional')
                    ->label('Profissional')
                    ->options(function () {
                        $user = auth()->user();
                        $query = Professional::whereIn('role', ['coordinator', 'supervisor']);
                        if ($user->isAdmin() || $user->isManager()) return $query->pluck('name', 'id');

                        $userUnits = $user->units->pluck('id')->toArray();
                        $regioesPermitidas = [];
                        if (in_array(1, $userUnits)) $regioesPermitidas[] = 1;
                        if (array_intersect([2, 3, 4], $userUnits)) array_push($regioesPermitidas, 2, 3, 4);

                        if (empty($regioesPermitidas)) return [];
                        return $query->whereHas('units', fn($q) => $q->whereIn('unit_id', $regioesPermitidas))->pluck('name', 'id');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        return $query->where(function ($q) use ($data) {
                            $q->where('coordinator_id', $data['value'])->orWhere('supervisor_id', $data['value']);
                        });
                    }),

                SelectFilter::make('status_coordenacao')
                    ->label('Status Coordenação')
                    ->options([
                        'em_dia' => '✅ Em dia (0 dias)',
                        'sem_coordenador' => '🚨 Sem coordenador cadastrado',
                        'pendente' => '⚠️ Visita Pendente',
                        'em_andamento' => '⏳ Em andamento',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        
                        $type = VisitType::Coordination->value;
                        $completedStatus = VisitStatus::Completed->value;
                        $pendingStatus = VisitStatus::Pending->value;

                        $daysCountSql = "(SELECT COUNT(DISTINCT DATE(appointments.appointment_date)) 
                            FROM appointments 
                            JOIN therapies ON appointments.therapy_id = therapies.id
                            WHERE appointments.patient_id = patient_services.patient_id
                              AND appointments.service_type_id = patient_services.service_type_id
                              AND appointments.appointment_date <= CURRENT_DATE
                              AND therapies.name IN ('ABA', 'DENVER')
                              AND appointments.appointment_date > COALESCE((
                                  SELECT visits.happened_at FROM visits
                                  JOIN therapies vt ON visits.therapy_id = vt.id
                                  WHERE visits.patient_id = patient_services.patient_id
                                    AND (visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)
                                    AND visits.type = '{$type}' AND visits.status = '{$completedStatus}'
                                    AND vt.name = therapies.name
                                  ORDER BY visits.happened_at DESC LIMIT 1
                              ), '2000-01-01'))";

                        return match ($data['value']) {
                            'sem_coordenador' => $query->whereNull('coordinator_id')->whereRaw("{$daysCountSql} > 0"),
                            'pendente' => $query->whereHas('patient', function ($q) use ($type, $pendingStatus) {
                                $q->whereExists(function ($sub) use ($type, $pendingStatus) {
                                    $sub->select(DB::raw(1))->from('visits')
                                        ->join('therapies', 'visits.therapy_id', '=', 'therapies.id')
                                        ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                                        ->whereRaw('(visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)')
                                        ->where('visits.type', $type)->where('visits.status', $pendingStatus)
                                        ->whereIn('therapies.name', ['ABA', 'DENVER']);
                                });
                            }),
                            'em_dia' => $query->whereRaw("{$daysCountSql} = 0")
                                ->whereNotExists(function ($sub) use ($type, $pendingStatus) {
                                    $sub->select(DB::raw(1))->from('visits')
                                        ->join('therapies', 'visits.therapy_id', '=', 'therapies.id')
                                        ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                                        ->whereRaw('(visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)')
                                        ->where('visits.type', $type)->where('visits.status', $pendingStatus)
                                        ->whereIn('therapies.name', ['ABA', 'DENVER']);
                                }),
                            'em_andamento' => $query->whereRaw("{$daysCountSql} > 0")->whereRaw("{$daysCountSql} < 10")->whereNotNull('coordinator_id')
                                ->whereNotExists(function ($sub) use ($type, $pendingStatus) {
                                    $sub->select(DB::raw(1))->from('visits')
                                        ->join('therapies', 'visits.therapy_id', '=', 'therapies.id')
                                        ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                                        ->whereRaw('(visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)')
                                        ->where('visits.type', $type)->where('visits.status', $pendingStatus)
                                        ->whereIn('therapies.name', ['ABA', 'DENVER']);
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
                        'em_andamento' => '⏳ Em andamento',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        
                        $type = VisitType::Supervision->value;
                        $completedStatus = VisitStatus::Completed->value;
                        $pendingStatus = VisitStatus::Pending->value;

                        $daysCountSql = "(SELECT COUNT(DISTINCT DATE(appointments.appointment_date)) 
                            FROM appointments 
                            JOIN therapies ON appointments.therapy_id = therapies.id
                            WHERE appointments.patient_id = patient_services.patient_id
                              AND appointments.service_type_id = patient_services.service_type_id
                              AND appointments.appointment_date <= CURRENT_DATE
                              AND therapies.name IN ('ABA', 'DENVER')
                              AND appointments.appointment_date > COALESCE((
                                  SELECT visits.happened_at FROM visits
                                  JOIN therapies vt ON visits.therapy_id = vt.id
                                  WHERE visits.patient_id = patient_services.patient_id
                                    AND (visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)
                                    AND visits.type = '{$type}' AND visits.status = '{$completedStatus}'
                                    AND vt.name = therapies.name
                                  ORDER BY visits.happened_at DESC LIMIT 1
                              ), '2000-01-01'))";

                        return match ($data['value']) {
                            'sem_supervisor' => $query->whereNull('supervisor_id')->whereRaw("{$daysCountSql} > 0"),
                            'pendente' => $query->whereExists(function ($sub) use ($type, $pendingStatus) {
                                $sub->select(DB::raw(1))->from('visits')
                                    ->join('therapies', 'visits.therapy_id', '=', 'therapies.id')
                                    ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                                    ->whereRaw('(visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)')
                                    ->where('visits.type', $type)->where('visits.status', $pendingStatus)
                                    ->whereIn('therapies.name', ['ABA', 'DENVER']);
                            }),
                            'em_dia' => $query->whereRaw("{$daysCountSql} = 0")
                                ->whereNotExists(function ($sub) use ($type, $pendingStatus) {
                                    $sub->select(DB::raw(1))->from('visits')
                                        ->join('therapies', 'visits.therapy_id', '=', 'therapies.id')
                                        ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                                        ->whereRaw('(visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)')
                                        ->where('visits.type', $type)->where('visits.status', $pendingStatus)
                                        ->whereIn('therapies.name', ['ABA', 'DENVER']);
                                }),
                            'em_andamento' => $query->whereRaw("{$daysCountSql} > 0")->whereRaw("{$daysCountSql} < 20")->whereNotNull('supervisor_id')
                                ->whereNotExists(function ($sub) use ($type, $pendingStatus) {
                                    $sub->select(DB::raw(1))->from('visits')
                                        ->join('therapies', 'visits.therapy_id', '=', 'therapies.id')
                                        ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                                        ->whereRaw('(visits.service_type_id = patient_services.service_type_id OR visits.service_type_id IS NULL)')
                                        ->where('visits.type', $type)->where('visits.status', $pendingStatus)
                                        ->whereIn('therapies.name', ['ABA', 'DENVER']);
                                }),
                            default => $query,
                        };
                    }),
            ], layout: FiltersLayout::AboveContent);
    }
}