<?php

namespace App\Observers;

use App\Models\Visit;
use App\Models\Appointment;
use App\Enums\VisitType;
use App\Enums\VisitStatus;
use Illuminate\Support\Facades\DB;

class VisitObserver
{
    public function creating(Visit $visit): void
    {
        if (empty($visit->therapy_id)) {
            $ultimaConsulta = Appointment::where('patient_id', $visit->patient_id)
                ->whereHas('therapy', function ($query) {
                    $query->whereIn('name', ['ABA', 'DENVER']);
                })
                ->latest('appointment_date')
                ->first();

            if ($ultimaConsulta) {
                $visit->therapy_id = $ultimaConsulta->therapy_id;
            }
        }
    }

    public function saved(Visit $visit): void
    {
        if ($visit->status === VisitStatus::Completed && !empty($visit->happened_at)) {
            
            $diasDeTerapia = Appointment::where('patient_id', $visit->patient_id)
                ->where('service_type_id', $visit->service_type_id)
                ->when($visit->therapy_id, function ($q) use ($visit) {
                    $q->where('therapy_id', $visit->therapy_id); 
                })
                ->whereDate('appointment_date', '>', $visit->happened_at)
                ->select(DB::raw('DATE(appointment_date) as date'))
                ->groupBy('date')
                ->get()
                ->count();

            if ($visit->type === VisitType::Coordination && $diasDeTerapia >= 10) {
                $this->gerarVisitaPendente($visit, VisitType::Coordination);
            }

            if ($visit->type === VisitType::Supervision && $diasDeTerapia >= 20) {
                $this->gerarVisitaPendente($visit, VisitType::Supervision);
            }
        }
    }

    private function gerarVisitaPendente(Visit $visit, $tipo): void
    {
        $jaTemPendente = Visit::where('patient_id', $visit->patient_id)
            ->where('service_type_id', $visit->service_type_id)
            ->where('type', $tipo)
            ->where('status', VisitStatus::Pending)
            ->when($visit->therapy_id, fn($q) => $q->where('therapy_id', $visit->therapy_id)) 
            ->exists();

        if (!$jaTemPendente) {
            Visit::create([
                'patient_id' => $visit->patient_id,
                'service_type_id' => $visit->service_type_id,
                'professional_id' => $visit->professional_id,
                'type' => $tipo,
                'status' => VisitStatus::Pending,
                'therapy_id' => $visit->therapy_id,
            ]);
        }
    }
}