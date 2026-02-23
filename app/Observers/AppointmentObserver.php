<?php

namespace App\Observers;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class AppointmentObserver
{
    public function created(Appointment $appointment): void
    {
        $this->checkAndCreateVisit($appointment);
    }

    public function updated(Appointment $appointment): void
    {
        if ($appointment->isDirty('appointment_date')) {
            $this->checkAndCreateVisit($appointment);
        }
    }

    /**
     * Lógica central que dispara a verificação para cada tipo de visita.
     */
    protected function checkAndCreateVisit(Appointment $appointment): void
    {
        $patient = Patient::find($appointment->patient_id);
        if (!$patient) {
            return;
        }

        // Dispara a checagem para visita de COORDENAÇÃO
        $this->checkAndCreateVisitForType(
            $patient,
            VisitType::Coordination,
            10, // Limite de dias
            $patient->coordinator_id
        );

        // Dispara a checagem para visita de SUPERVISÃO
        $this->checkAndCreateVisitForType(
            $patient,
            VisitType::Supervision,
            20, // Limite de dias
            $patient->supervisor_id
        );
    }

    /**
     * Verifica e cria uma visita de um tipo específico se as condições forem atendidas.
     */
    protected function checkAndCreateVisitForType(Patient $patient, VisitType $type, int $daysThreshold, ?int $professional_id): void
    {
        // 1. Se não houver um profissional designado para este tipo de visita, encerra.
        if (!$professional_id) {
            return;
        }

        // 2. Se já existir uma visita PENDENTE DESTE TIPO, não faz nada.
        if (Visit::where('patient_id', $patient->id)->where('type', $type)->where('status', VisitStatus::Pending)->exists()) {
            return;
        }

        // 3. Encontra a última visita COMPLETADA deste tipo para saber de onde começar a contar.
        $lastCompletedVisit = Visit::where('patient_id', $patient->id)
            ->where('type', $type)
            ->where('status', VisitStatus::Completed)
            ->latest('happened_at') // Ordena pela data em que a visita foi realizada
            ->first();

        // 4. Define a data de início da contagem. Se não houver visita anterior, conta desde o início.
        $countStartDate = $lastCompletedVisit ? $lastCompletedVisit->happened_at : null;

        // 5. Conta os dias de atendimento ABA únicos desde a data de início.
        $appointmentsQuery = Appointment::where('patient_id', $patient->id)
            ->whereHas('therapy', fn ($query) => $query->where('name', 'ABA'));

        if ($countStartDate) {
            $appointmentsQuery->where('appointment_date', '>', $countStartDate);
        }

        $daysCount = $appointmentsQuery->distinct(DB::raw('DATE(appointment_date)'))->count();

        // 6. Se o número de dias de atendimento atingir o limite, cria a nova visita.
        if ($daysCount >= $daysThreshold) {
            Visit::create([
                'patient_id'      => $patient->id,
                'professional_id' => $professional_id,
                'type'            => $type,
                'status'          => VisitStatus::Pending,
                'happened_at'     => null,
                'notes'           => "Gerado automaticamente após atingir a marca de {$daysThreshold} dias de atendimento ABA desde a última visita completada.",
            ]);
        }
    }
}
