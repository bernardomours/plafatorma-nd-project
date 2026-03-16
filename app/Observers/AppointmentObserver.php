<?php

namespace App\Observers;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\PatientService;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentObserver
{
    public function created(Appointment $appointment): void
    {
        $this->checkAndCreateVisit($appointment);
    }

    public function updated(Appointment $appointment): void
    {
        // Se a data ou o ambiente da consulta mudarem, o robô recalcula
        if ($appointment->isDirty('appointment_date') || $appointment->isDirty('service_type_id')) {
            $this->checkAndCreateVisit($appointment);
        }
    }

    /**
     * Lógica central que dispara a verificação para cada tipo de visita.
     */
    protected function checkAndCreateVisit(Appointment $appointment): void
    {
        // 1. Garante que a terapia associada a este agendamento é ABA
        if (!$appointment->therapy || $appointment->therapy->name !== 'ABA') {
            return;
        }

        // 2. Busca quem são os coordenadores/supervisores DESTE paciente NESSE ambiente (Clínica, Escola, etc)
        // 🛡️ Ajuste: Se o appointment estiver sem ambiente, ele tenta achar o principal do paciente.
        $ambienteBusca = $appointment->service_type_id;
        if (!$ambienteBusca) {
             $vinculoPadrao = PatientService::where('patient_id', $appointment->patient_id)->first();
             if (!$vinculoPadrao) return;
             $ambienteBusca = $vinculoPadrao->service_type_id;
        }

        $patientService = PatientService::where('patient_id', $appointment->patient_id)
            ->where('service_type_id', $ambienteBusca)
            ->first();

        // Se o paciente não tiver a equipe cadastrada para esse ambiente, o robô não faz nada
        if (!$patientService) {
            return;
        }

        // Dispara a checagem para visita de COORDENAÇÃO (agora lendo do patientService)
        $this->checkAndCreateVisitForType(
            $patientService,
            VisitType::Coordination,
            10, // Limite de dias
            $patientService->coordinator_id
        );

        // Dispara a checagem para visita de SUPERVISÃO
        $this->checkAndCreateVisitForType(
            $patientService,
            VisitType::Supervision,
            20, // Limite de dias
            $patientService->supervisor_id
        );
    }

    /**
     * Verifica e cria uma visita de um tipo específico se as condições forem atendidas.
     */
    protected function checkAndCreateVisitForType(PatientService $patientService, VisitType $type, int $daysThreshold, ?int $professional_id): void
    {
        // 1. Se não houver um profissional designado, encerra.
        if (!$professional_id) {
            return;
        }

        // 2. Verifica pendência para ESTE paciente, NESTE ambiente (ou nulo)
        if (Visit::where('patient_id', $patientService->patient_id)
            ->where(fn($q) => $q->where('service_type_id', $patientService->service_type_id)->orWhereNull('service_type_id'))
            ->where('type', $type->value) // 🛡️ Ajuste: Enum blindado
            ->where('status', VisitStatus::Pending->value) // 🛡️ Ajuste: Enum blindado
            ->exists()) {
            return;
        }

        // 3. Encontra a última completada NESTE ambiente (ou nulo)
        $lastCompletedVisit = Visit::where('patient_id', $patientService->patient_id)
            ->where(fn($q) => $q->where('service_type_id', $patientService->service_type_id)->orWhereNull('service_type_id'))
            ->where('type', $type->value) // 🛡️ Ajuste: Enum blindado
            ->where('status', VisitStatus::Completed->value) // 🛡️ Ajuste: Enum blindado
            ->latest('happened_at')
            ->first();

        $countStartDate = $lastCompletedVisit ? $lastCompletedVisit->happened_at : null;

        // 4. Busca os agendamentos de ABA
        $appointmentsQuery = Appointment::where('patient_id', $patientService->patient_id)
            ->where('service_type_id', $patientService->service_type_id)
            ->where('appointment_date', '<=', Carbon::today())
            ->whereHas('therapy', fn ($query) => $query->where('name', 'ABA'));

        if ($countStartDate) {
            $appointmentsQuery->where('appointment_date', '>', $countStartDate);
        }

        // 5. Conta os dias únicos
        $daysCount = $appointmentsQuery->select(DB::raw('DATE(appointment_date) as date'))->groupBy('date')->get()->count();

        // 6. Se atingir o limite, cria a nova visita com a tag do ambiente correto!
        if ($daysCount >= $daysThreshold) {
            Visit::create([
                'patient_id'      => $patientService->patient_id,
                'service_type_id' => $patientService->service_type_id,
                'professional_id' => $professional_id,
                'type'            => $type->value,
                'status'          => VisitStatus::Pending->value,
                'happened_at'     => null,
                'notes'           => "Gerado automaticamente após atingir a marca de {$daysThreshold} dias de atendimento ABA neste ambiente.",
            ]);
        }
    }
}