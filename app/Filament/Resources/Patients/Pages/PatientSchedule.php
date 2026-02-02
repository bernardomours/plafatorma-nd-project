<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use App\Filament\Resources\Schedules\Schemas\ScheduleForm;
use App\Models\Patient;
use App\Models\Schedule;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class PatientSchedule extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string $resource = PatientResource::class;

    protected string $view = 'filament.pages.patient-schedule';

    public $record;
    public $schedules;

    public function getTitle(): string | Htmlable
    {
        $patient = $this->getPatient();
        return "Agenda de " . $patient->name;
    }

    public function mount($record): void
    {
        $this->record = $record;
        $this->schedules = $this->getSchedules();
    }

    public function getPatient()
    {
        return Patient::findOrFail($this->record);
    }

    public function getSchedules()
    {
        return $this->getPatient()->schedules()->with(['therapy', 'professional'])->get();
    }
    

    public function getScheduleByDay($day)
    {
        return $this->schedules->where('day_of_week', $day);
    }

    public function addScheduleAction(): Action
    {
        return Action::make('addSchedule')
            ->label('Adicionar Horário')
            ->icon('heroicon-o-plus')
            ->form(ScheduleForm::getSchema())
            ->model(Schedule::class)
            ->action(function ($data) {
                $this->createSchedule($data);
            })
            ->after(fn() => $this->schedules = $this->getSchedules());
    }

    public function editScheduleAction(): Action
    {
        return Action::make('editSchedule')
            ->label('Editar')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->tooltip('Editar Horário')
            ->record(fn (array $arguments) => Schedule::find($arguments['record']))
            ->fillForm(fn (Schedule $record) => [
                'day_of_week' => $record->day_of_week, 
                'start_time' => $record->start_time,
                'end_time' => $record->end_time,
                'professional_id' => $record->professional_id,
                'therapy_id' => $record->therapy_id,
                'therapy_type' => $record->therapy_type,
            ])
            ->form(ScheduleForm::getSchema())
            ->action(function (array $data, Schedule $record) {
                $this->updateSchedule($record, $data);
            })
            ->after(fn() => $this->schedules = $this->getSchedules());
    }

    public function deleteScheduleAction(): Action
    {
        return Action::make('deleteSchedule')
            ->label('Excluir')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->tooltip('Excluir Horário')
            ->requiresConfirmation()
            ->record(fn (array $arguments) => Schedule::find($arguments['record']))
            ->action(function (Schedule $record) {
                $this->deleteSchedule($record);
            })
            ->after(fn() => $this->schedules = $this->getSchedules());
    }

    protected function createSchedule($data): void
    {
        $schedule = new Schedule($data);
        $schedule->patient_id = $this->getPatient()->id;
        $schedule->save();
    }

    protected function updateSchedule(Schedule $schedule, $data): void
    {
        $schedule->update($data);
    }

    protected function deleteSchedule(Schedule $schedule): void
    {
        $schedule->delete();
    }
}
