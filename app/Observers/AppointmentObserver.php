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

        // 2. 🛡️ O GUARDA-COSTAS: Busca ou CRIA o vínculo do paciente com este ambiente
        $ambienteBusca = $appointment->service_type_id;
        
        if ($ambienteBusca) {
            // Se a recepção salvou uma sessão Domiciliar, garante que o paciente tenha esse vínculo!
            // Se não tiver, o robô cria uma linha fantasma no banco agora mesmo.
            $patientService = PatientService::firstOrCreate([
                'patient_id' => $appointment->patient_id,
                'service_type_id' => $ambienteBusca,
            ]);
        } else {
            // Se veio sem ambiente do agendamento, tenta achar o principal
            $patientService = PatientService::where('patient_id', $appointment->patient_id)->first();
            if (!$patientService) {
                return; // Se não achou nenhum, aborta.
            }
        }

        // Dispara a checagem para visita de COORDENAÇÃO
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

    protected function checkAndCreateVisitForType(PatientService $patientService, VisitType $type, int $daysThreshold, ?int $professional_id): void
    {
        // 1. Encontra a última visita CONCLUÍDA NESTE ambiente (ou nulo)
        $lastCompletedVisit = Visit::where('patient_id', $patientService->patient_id)
            ->where(fn($q) => $q->where('service_type_id', $patientService->service_type_id)->orWhereNull('service_type_id'))
            ->where('type', $type->value) 
            ->where('status', VisitStatus::Completed->value) 
            ->latest('happened_at')
            ->first();

        $countStartDate = $lastCompletedVisit ? $lastCompletedVisit->happened_at : null;

        // 2. Busca os agendamentos de ABA válidos e conta os dias
        $appointmentsQuery = Appointment::where('patient_id', $patientService->patient_id)
            ->where('service_type_id', $patientService->service_type_id)
            ->where('appointment_date', '<=', Carbon::today())
            ->whereHas('therapy', fn ($query) => $query->where('name', 'ABA'));

        if ($countStartDate) {
            $appointmentsQuery->where('appointment_date', '>', $countStartDate);
        }

        $daysCount = $appointmentsQuery->select(DB::raw('DATE(appointment_date) as date'))->groupBy('date')->get()->count();

        // 3. Verifica se JÁ EXISTE uma visita PENDENTE para este ciclo
        $visitaPendente = Visit::where('patient_id', $patientService->patient_id)
            ->where(fn($q) => $q->where('service_type_id', $patientService->service_type_id)->orWhereNull('service_type_id'))
            ->where('type', $type->value) 
            ->where('status', VisitStatus::Pending->value) 
            ->first(); // Pega a primeira que achar

        // 4. A LÓGICA DE DECISÃO (Criar, Manter ou Destruir)
        if ($daysCount >= $daysThreshold) {
            if (!$visitaPendente) {
                Visit::create([
                    'patient_id'      => $patientService->patient_id,
                    'service_type_id' => $patientService->service_type_id,
                    'professional_id' => $professional_id, // Pode ser null para gerar o alerta vermelho
                    'type'            => $type->value,
                    'status'          => VisitStatus::Pending->value,
                    'happened_at'     => null,
                    'notes'           => "Gerado automaticamente após atingir {$daysThreshold} dias de atendimento ABA neste ambiente.",
                ]);
            }
        } else {
            if ($visitaPendente) {
                $visitaPendente->delete();
            }
        }
    }
}