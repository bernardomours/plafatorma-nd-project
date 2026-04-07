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
        if ($appointment->isDirty('appointment_date') || $appointment->isDirty('service_type_id') || $appointment->isDirty('therapy_id')) {
            $this->checkAndCreateVisit($appointment);
        }
    }

    protected function checkAndCreateVisit(Appointment $appointment): void
    {
        if (!$appointment->therapy || !in_array($appointment->therapy->name, ['ABA', 'DENVER'])) {
            return;
        }

        $ambienteBusca = $appointment->service_type_id;
        $therapyName = $appointment->therapy->name;
        $therapyId = $appointment->therapy_id;
        
        if ($ambienteBusca) {
            $patientService = PatientService::firstOrCreate([
                'patient_id' => $appointment->patient_id,
                'service_type_id' => $ambienteBusca,
            ]);
        } else {
            $patientService = PatientService::where('patient_id', $appointment->patient_id)->first();
            if (!$patientService) {
                return;
            }
        }

        $this->checkAndCreateVisitForType(
            $patientService,
            VisitType::Coordination,
            10,
            $patientService->coordinator_id,
            $therapyId,
            $therapyName
        );

        $this->checkAndCreateVisitForType(
            $patientService,
            VisitType::Supervision,
            20,
            $patientService->supervisor_id,
            $therapyId,
            $therapyName
        );
    }

    protected function checkAndCreateVisitForType(PatientService $patientService, VisitType $type, int $daysThreshold, ?int $professional_id, int $therapyId, string $therapyName): void
    {
        $lastCompletedVisit = Visit::where('patient_id', $patientService->patient_id)
            ->where(fn($q) => $q->where('service_type_id', $patientService->service_type_id)->orWhereNull('service_type_id'))
            ->where('type', $type->value) 
            ->where('status', VisitStatus::Completed->value) 
            ->where('therapy_id', $therapyId)
            ->latest('happened_at')
            ->first();

        $countStartDate = $lastCompletedVisit ? $lastCompletedVisit->happened_at : null;

        $appointmentsQuery = Appointment::where('patient_id', $patientService->patient_id)
            ->where('service_type_id', $patientService->service_type_id)
            ->where('appointment_date', '<=', Carbon::today())
            ->where('therapy_id', $therapyId);

        if ($countStartDate) {
            $appointmentsQuery->where('appointment_date', '>', $countStartDate);
        }

        $daysCount = $appointmentsQuery->select(DB::raw('DATE(appointment_date) as date'))->groupBy('date')->get()->count();

        $visitaPendente = Visit::where('patient_id', $patientService->patient_id)
            ->where(fn($q) => $q->where('service_type_id', $patientService->service_type_id)->orWhereNull('service_type_id'))
            ->where('type', $type->value) 
            ->where('status', VisitStatus::Pending->value) 
            ->where('therapy_id', $therapyId)
            ->first();

        if ($daysCount >= $daysThreshold) {
            if (!$visitaPendente) {
                Visit::create([
                    'patient_id'      => $patientService->patient_id,
                    'service_type_id' => $patientService->service_type_id,
                    'professional_id' => $professional_id,
                    'type'            => $type->value,
                    'status'          => VisitStatus::Pending->value,
                    'happened_at'     => null,
                    'therapy_id'      => $therapyId,
                    'notes'           => "Gerado automaticamente após atingir {$daysThreshold} dias de atendimento {$therapyName} neste ambiente.",
                ]);
            }
        } else {
            if ($visitaPendente) {
                $visitaPendente->delete();
            }
        }
    }
}