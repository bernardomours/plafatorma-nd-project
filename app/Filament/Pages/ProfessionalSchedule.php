<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Professional;
use App\Models\Schedule;
use BackedEnum;
use UnitEnum;

class ProfessionalSchedule extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string|UnitEnum|null $navigationGroup = 'Ocupação';
    protected static ?string $navigationLabel = 'Agenda dos Profissionais';
    protected static ?string $title = 'Agenda Semanal';
    protected string $view = 'filament.pages.professional-schedule';

    public ?array $data = []; 

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema    
    {
        return $schema
            ->components([
                Select::make('professional_id')
                    ->label('Selecione o Profissional')
                    ->placeholder('Buscar profissional...')
                    ->options(Professional::pluck('name', 'id'))
                    ->searchable()
                    ->live()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function getAgendaData(): array
    {
        $vazio = [1 => [], 2 => [], 3 => [], 4 => [], 5 => []];
        
        $agenda = [
            'Manhã' => $vazio,
            'Tarde' => $vazio,
        ];

        // Lê o ID selecionado de dentro do array
        $prof_id = $this->data['professional_id'] ?? null;

        if (!$prof_id) {
            return $agenda;
        }

        $horarios = Schedule::with(['patient', 'therapy', 'serviceType'])
            ->where('professional_id', $prof_id)
            ->orderBy('start_time')
            ->get();

        foreach ($horarios as $horario) {
            // Conversão segura da hora
            $horaInicio = \Carbon\Carbon::parse($horario->start_time)->format('H:i:s');
            $turno = $horaInicio < '12:00:00' ? 'Manhã' : 'Tarde';
            
            // O tradutor exato baseado no seu banco de dados
            $diaBanco = (string) $horario->day_of_week;
            
            $diaNumerico = match(strtolower(trim($diaBanco))) {
                'segunda' => 1,
                'terca', 'terça' => 2, // 👈 Blindado contra a falta do cedilha!
                'quarta' => 3,
                'quinta' => 4,
                'sexta' => 5,
                default => 1, 
            };

            // Guarda na gaveta certa!
            if (isset($agenda[$turno][$diaNumerico])) {
                $agenda[$turno][$diaNumerico][] = $horario;
            }
        }

        return $agenda;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user->isAdmin() || $user->isManager() || $user->isAdministrative();
    }
}