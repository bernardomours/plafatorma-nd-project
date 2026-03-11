<?php

namespace App\Filament\Pages;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Visit;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
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

    public function table(Table $table): Table
    {
        return $table
            ->query(Patient::query()) // Puxa todos os pacientes
            ->columns([
                TextColumn::make('name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),

                // === COLUNA DE COORDENAÇÃO (10 DIAS) ===
                TextColumn::make('coordination_status')
                    ->label('Coordenação (Meta: 10)')
                    ->getStateUsing(function (Patient $record) {
                        // 1. Verifica se já tem visita pendente engatilhada
                        $hasPending = Visit::where('patient_id', $record->id)
                            ->where('type', VisitType::Coordination)
                            ->where('status', VisitStatus::Pending)
                            ->exists();

                        if ($hasPending) return '⚠️ Visita Pendente';

                        // 2. Se não tem pendente, calcula os dias desde a última
                        $lastVisit = Visit::where('patient_id', $record->id)
                            ->where('type', VisitType::Coordination)
                            ->where('status', VisitStatus::Completed)
                            ->latest('happened_at')
                            ->first();

                        $startDate = $lastVisit ? $lastVisit->happened_at : null;

                        $query = Appointment::where('patient_id', $record->id)
                            ->whereHas('therapy', fn ($q) => $q->where('name', 'ABA'));

                        if ($startDate) {
                            $query->where('appointment_date', '>', $startDate);
                        }

                        $daysCount = $query->select(DB::raw('DATE(appointment_date) as date'))->groupBy('date')->get()->count();

                        if ($daysCount === 0) return '✅ Em dia (0 dias)';
                        
                        return "⏳ {$daysCount} / 10 dias";
                    })
                    // O Subtítulo cinza com a data da última visita!
                    ->description(function (Patient $record) {
                        $lastVisit = Visit::with('professional') // Puxa o profissional junto!
                            ->where('patient_id', $record->id)
                            ->where('type', VisitType::Coordination)
                            ->where('status', VisitStatus::Completed)
                            ->latest('happened_at')
                            ->first();

                        if ($lastVisit && $lastVisit->happened_at) {
                            $data = Carbon::parse($lastVisit->happened_at)->format('d/m/Y');
                            $professionalName = $lastVisit->professional ? implode(' ', array_slice(explode(' ', $lastVisit->professional->name), 0, 2)) : 'Desconhecido';                            
                            
                            return "Última: {$data} (Feita por {$professionalName})";
                        }

                        return 'Nenhuma visita registrada';
                    })

                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state === '⚠️ Visita Pendente' => 'warning',
                        $state === '✅ Em dia (0 dias)' => 'success',
                        default => 'info', // Fica azul enquanto estiver contando
                    }),

                // === COLUNA DE SUPERVISÃO (20 DIAS) ===
                TextColumn::make('supervision_status')
                    ->label('Supervisão (Meta: 20)')
                    ->getStateUsing(function (Patient $record) {
                        $hasPending = Visit::where('patient_id', $record->id)
                            ->where('type', VisitType::Supervision)
                            ->where('status', VisitStatus::Pending)
                            ->exists();

                        if ($hasPending) return '⚠️ Visita Pendente';

                        $lastVisit = Visit::where('patient_id', $record->id)
                            ->where('type', VisitType::Supervision)
                            ->where('status', VisitStatus::Completed)
                            ->latest('happened_at')
                            ->first();

                        $startDate = $lastVisit ? $lastVisit->happened_at : null;

                        $query = Appointment::where('patient_id', $record->id)
                            ->whereHas('therapy', fn ($q) => $q->where('name', 'ABA'));

                        if ($startDate) {
                            $query->where('appointment_date', '>', $startDate);
                        }

                        $daysCount = $query->select(DB::raw('DATE(appointment_date) as date'))->groupBy('date')->get()->count();

                        if ($daysCount === 0) return '✅ Em dia (0 dias)';
                        
                        return "⏳ {$daysCount} / 20 dias";
                    })
                    ->description(function (Patient $record) {
                        $lastVisit = Visit::where('patient_id', $record->id)
                            ->where('type', VisitType::Supervision)
                            ->where('status', VisitStatus::Completed)
                            ->latest('happened_at')
                            ->first();

                        return $lastVisit && $lastVisit->happened_at 
                            ? 'Última: ' . Carbon::parse($lastVisit->happened_at)->format('d/m/Y') 
                            : 'Nenhuma visita registrada';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state === '⚠️ Visita Pendente' => 'warning',
                        $state === '✅ Em dia (0 dias)' => 'success',
                        default => 'info',
                    }),
            ]);
    }
}