<?php

namespace App\Observers;

use App\Models\Visit;
use App\Models\Appointment;
use App\Enums\VisitType;
use App\Enums\VisitStatus;
use Illuminate\Support\Facades\DB;

class VisitObserver
{
    // 1. O ESPIÃO DO "CRIANDO": Preenche a terapia automaticamente antes de salvar
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

    // 2. O ESPIÃO DO "SALVO": Verifica as metas após salvar
    public function saved(Visit $visit): void
    {
        if ($visit->status === VisitStatus::Completed && !empty($visit->happened_at)) {
            
            // Agora filtramos pela terapia específica que foi descoberta no 'creating'
            $diasDeTerapia = Appointment::where('patient_id', $visit->patient_id)
                ->where('service_type_id', $visit->service_type_id)
                ->when($visit->therapy_id, function ($q) use ($visit) {
                    // Busca os dias específicos do ABA ou do DENVER
                    $q->where('therapy_id', $visit->therapy_id); 
                })
                ->whereDate('appointment_date', '>', $visit->happened_at)
                ->select(DB::raw('DATE(appointment_date) as date'))
                ->groupBy('date')
                ->get()
                ->count();

            // A lógica continua a mesma, mas agora a variável é dinâmica
            if ($visit->type === VisitType::Coordination && $diasDeTerapia >= 10) {
                $this->gerarVisitaPendente($visit, VisitType::Coordination);
            }

            if ($visit->type === VisitType::Supervision && $diasDeTerapia >= 20) {
                $this->gerarVisitaPendente($visit, VisitType::Supervision);
            }
        }
    }

    // 3. GERADOR DE PENDÊNCIAS: Repassa a terapia para a nova visita
    private function gerarVisitaPendente(Visit $visit, $tipo): void
    {
        $jaTemPendente = Visit::where('patient_id', $visit->patient_id)
            ->where('service_type_id', $visit->service_type_id)
            ->where('type', $tipo)
            ->where('status', VisitStatus::Pending)
            ->when($visit->therapy_id, fn($q) => $q->where('therapy_id', $visit->therapy_id)) // Impede duplicatas misturando terapias
            ->exists();

        if (!$jaTemPendente) {
            Visit::create([
                'patient_id' => $visit->patient_id,
                'service_type_id' => $visit->service_type_id,
                'professional_id' => $visit->professional_id,
                'type' => $tipo,
                'status' => VisitStatus::Pending,
                'therapy_id' => $visit->therapy_id, // 👈 A mágica continua: a pendente já nasce sabendo sua terapia!
            ]);
        }
    }
}