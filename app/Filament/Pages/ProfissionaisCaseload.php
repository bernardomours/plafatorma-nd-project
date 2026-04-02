<?php

namespace App\Filament\Pages;

use App\Models\Professional;
use App\Models\PatientService;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;
use Filament\Schemas\Schema;

class ProfissionaisCaseload extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static ?string $title = 'Vínculos de Pacientes';
    protected static string|UnitEnum|null $navigationGroup = 'Coordenação/Supervisão';
    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.profissionais-caseload';

    public ?string $professional_id = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user->isAdmin() || $user->isManager() || $user->isAdministrative() || $user->isAdministrative();
    }

    public function mount(): void
    {
        if (method_exists($this, 'fillSchemas')) {
             $this->fillSchemas();
        } elseif (method_exists($this->form, 'fill')) {
             $this->form->fill();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('')
            ->schema([
                \Filament\Schemas\Components\Section::make('Filtro de Coordenadores/Supervisores')
                    ->description('Selecione um profissional para visualizar todos os pacientes sob sua responsabilidade.')
                    ->schema([
                        Select::make('professional_id')
                            ->label('Coordenador / Supervisor')
                            ->options(Professional::whereIn('role', ['coordinator', 'supervisor'])->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetTable()) 
                    ])
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PatientService::query()
                    ->with(['patient', 'serviceType', 'coordinator', 'supervisor'])
                    ->whereHas('patient')
                    ->where(function (Builder $query) {
                        if ($this->professional_id) {
                            $query->where('coordinator_id', $this->professional_id)
                                  ->orWhere('supervisor_id', $this->professional_id);
                        } else {
                            $query->whereRaw('1 = 0'); 
                        }
                    })
            )
            ->columns([
                TextColumn::make('patient.name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('serviceType.name')
                    ->label('Ambiente')
                    ->badge()
                    ->color('info'),
                TextColumn::make('coordinator.name')
                    ->label('Coordenador'),
                TextColumn::make('supervisor.name')
                    ->label('Supervisor'),
            ])
            ->emptyStateHeading(fn () => $this->professional_id ? 'Nenhum paciente vinculado.' : 'Nenhum profissional selecionado')
            ->emptyStateDescription(fn () => $this->professional_id ? 'Este profissional ainda não possui pacientes.' : 'Selecione um profissional acima para ver a lista de pacientes.');
    }
}