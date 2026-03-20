<?php

namespace App\Filament\Pages;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\PatientService;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;

class AlinhamentoVisitas extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Alinhamento de Visitas';
    protected static ?string $title = 'Preenchimento Retroativo';
    protected static string|UnitEnum|null $navigationGroup = 'Coordenação/Supervisão';
    
    protected string $view = 'filament.pages.alinhamento-visitas';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PatientService::query()
                    ->when(!auth()->user()->is_admin, function ($query) {
                            $user = auth()->user();
                            $userUnitIds = [];

                            if ($user->unit_id) {
                                $userUnitIds[] = $user->unit_id;
                            } else {
                                $profissional = \App\Models\Professional::withoutGlobalScopes()
                                    ->with('units')
                                    ->where('email', $user->email)
                                    ->first();
                                
                                if ($profissional) {
                                    $userUnitIds = $profissional->units->pluck('id')->toArray();
                                }
                            }

                            $isMossoro = in_array(1, $userUnitIds);
                            $query->whereHas('patient', function ($q) use ($isMossoro) {
                                if ($isMossoro) {
                                    $q->where('unit_id', 1);
                                } else {
                                    $q->where('unit_id', '!=', 1);
                                }
                            });
                        })
                    ->addSelect([
                        'ultima_coordenacao_data' => Visit::select('happened_at')
                            ->whereColumn('patient_id', 'patient_services.patient_id')
                            ->whereColumn('service_type_id', 'patient_services.service_type_id')
                            ->where('type', VisitType::Coordination->value)
                            ->where('status', VisitStatus::Completed->value)
                            ->orderByDesc('happened_at')
                            ->limit(1),
                        
                        'ultima_supervisao_data' => Visit::select('happened_at')
                            ->whereColumn('patient_id', 'patient_services.patient_id')
                            ->whereColumn('service_type_id', 'patient_services.service_type_id')
                            ->where('type', VisitType::Supervision->value)
                            ->where('status', VisitStatus::Completed->value)
                            ->orderByDesc('happened_at')
                            ->limit(1),
                    ])
                    ->where(function ($query) {
                // Traz se NÃO existir visita de coordenação
                $query->whereNotExists(function ($subQuery) {
                    $subQuery->select('id')
                        ->from('visits')
                        ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                        ->whereColumn('visits.service_type_id', 'patient_services.service_type_id')
                        ->where('visits.type', VisitType::Coordination->value)
                        ->where('visits.status', VisitStatus::Completed->value);
                })
                // OU traz se NÃO existir visita de supervisão
                ->orWhereNotExists(function ($subQuery) {
                    $subQuery->select('id')
                        ->from('visits')
                        ->whereColumn('visits.patient_id', 'patient_services.patient_id')
                        ->whereColumn('visits.service_type_id', 'patient_services.service_type_id')
                        ->where('visits.type', VisitType::Supervision->value)
                        ->where('visits.status', VisitStatus::Completed->value);
                });
            })
            )
            ->columns([
                TextColumn::make('patient.name')
                    ->label('Paciente e Ambiente')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        $ambiente = $record->serviceType ? $record->serviceType->name : 'Ambiente não definido';
                        return "{$state} — {$ambiente}";
                    })
                    ->description(function ($record) {
                        $coord = $record->coordinator ? $record->coordinator->name : 'Sem Coord.';
                        $sup = $record->supervisor ? $record->supervisor->name : 'Sem Sup.';
                        return "Coord: {$coord} | Sup: {$sup}";
                    }),

                TextColumn::make('ultima_coordenacao_data')
                    ->label('Últ. Coordenação')
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : 'Sem registro')
                    ->badge()
                    ->color(fn ($state) => empty($state) ? 'danger' : 'success'),

                TextColumn::make('ultima_supervisao_data')
                    ->label('Últ. Supervisão')
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : 'Sem registro')
                    ->badge()
                    ->color(fn ($state) => empty($state) ? 'danger' : 'success'),
            ])
            ->actions([
                Action::make('registrar_coordenacao')
                    ->label('Coordenação')
                    ->icon('heroicon-o-plus-circle')
                    ->color('warning')
                    ->form([
                        DatePicker::make('happened_at')
                            ->label('Data da Coordenação')
                            ->required()
                            ->default(now()),
                    ])
                    ->action(function (array $data, PatientService $record): void {
                        Visit::create([
                            'patient_id'      => $record->patient_id,
                            'service_type_id' => $record->service_type_id,
                            'professional_id' => $record->coordinator_id,
                            'type'            => VisitType::Coordination->value,
                            'status'          => VisitStatus::Completed->value,
                            'happened_at'     => $data['happened_at'],
                            'notes'           => 'Registro retroativo de alinhamento (Backfill).',
                        ]);
                    }),

                Action::make('registrar_supervisao')
                    ->label('Supervisão')
                    ->icon('heroicon-o-plus-circle')
                    ->color('info')
                    ->form([
                        DatePicker::make('happened_at')
                            ->label('Data da Supervisão')
                            ->required()
                            ->default(now()),
                    ])
                    ->action(function (array $data, PatientService $record): void {
                        Visit::create([
                            'patient_id'      => $record->patient_id,
                            'service_type_id' => $record->service_type_id,
                            'professional_id' => $record->supervisor_id,
                            'type'            => VisitType::Supervision->value,
                            'status'          => VisitStatus::Completed->value,
                            'happened_at'     => $data['happened_at'],
                            'notes'           => 'Registro retroativo de alinhamento (Backfill).',
                        ]);
                    })
            ]);
    }
}