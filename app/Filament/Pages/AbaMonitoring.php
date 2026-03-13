<?php

namespace App\Filament\Pages;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientService;
use App\Models\ServiceType;
use App\Models\Visit;
use Filament\Pages\Page;
use Filament\Resources\Components\Tab;
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
    protected static string|UnitEnum|null $navigationGroup = 'Gerência';
    protected static ?string $navigationLabel = 'Monitoramento ABA';
    protected static ?string $title = 'Auditoria: Monitoramento ABA';
    protected static ?string $slug = 'aba-monitoring';
    protected string $view = 'filament.pages.aba-monitoring';

    public static function canViewAny(): bool
    {
        return auth()->user()?->is_admin ?? false;
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
        return $table
            ->query(PatientService::query()->whereHas('patient')->with(['patient', 'serviceType', 'coordinator', 'supervisor']))
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
                        $hasPending = Visit::where('patient_id', $record->patient_id)
                            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                            ->where('type', VisitType::Coordination->value)
                            ->where('status', VisitStatus::Pending->value)
                            ->exists();

                        if ($hasPending) return '⚠️ Visita Pendente';

                        $lastVisit = Visit::where('patient_id', $record->patient_id)
                            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                            ->where('type', VisitType::Coordination->value)
                            ->where('status', VisitStatus::Completed->value)
                            ->latest('happened_at')
                            ->first();

                        $startDate = $lastVisit ? $lastVisit->happened_at : null;
                        
                        $query = Appointment::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->where('appointment_date', '<=', Carbon::today()) // 🛡️ Trava contra o futuro!
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
                        $hasPending = Visit::where('patient_id', $record->patient_id)
                            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                            ->where('type', VisitType::Supervision->value)
                            ->where('status', VisitStatus::Pending->value)
                            ->exists();

                        if ($hasPending) return '⚠️ Visita Pendente';

                        $lastVisit = Visit::where('patient_id', $record->patient_id)
                            ->where(fn($q) => $q->where('service_type_id', $record->service_type_id)->orWhereNull('service_type_id'))
                            ->where('type', VisitType::Supervision->value)
                            ->where('status', VisitStatus::Completed->value)
                            ->latest('happened_at')
                            ->first();

                        $startDate = $lastVisit ? $lastVisit->happened_at : null;

                        $query = Appointment::where('patient_id', $record->patient_id)
                            ->where('service_type_id', $record->service_type_id)
                            ->where('appointment_date', '<=', Carbon::today()) // 🛡️ Trava contra o futuro!
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
                        return $query->whereHas('patient', fn($q) => $q->where('unit_id', $data['value']));
                    }),
                SelectFilter::make('professional')
                    ->label('Profissional')
                    ->options(\App\Models\Professional::whereIn('role', ['coordinator', 'supervisor'])->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        return $query->where(function ($q) use ($data) {
                            $q->where('coordinator_id', $data['value'])
                              ->orWhere('supervisor_id', $data['value']);
                        });
                    }),
            ], layout: FiltersLayout::AboveContent);
    }
}