<?php

namespace App\Observers;

use App\Models\Visit;
use App\Models\Appointment;
use App\Enums\VisitType;
use App\Enums\VisitStatus;
use Illuminate\Support\Facades\DB;

class VisitObserver
{
    /**
     * Esse evento dispara toda vez que uma visita é CRIADA ou ATUALIZADA no banco.
     */
    public function saved(Visit $visit): void
    {
        // 1. Só fazemos a checagem se a visita foi marcada como CONCLUÍDA e tem uma DATA preenchida.
        // (Isso ignora as visitas que a sua chefe acabou de deixar como Pendentes e sem data)
        if ($visit->status === VisitStatus::Completed && !empty($visit->happened_at)) {
            
            // 2. Conta os dias ÚNICOS de sessões ABA do paciente DEPOIS da data dessa visita que acabou de ser salva
            $diasDeAba = Appointment::where('patient_id', $visit->patient_id)
                ->where('service_type_id', $visit->service_type_id)
                ->whereHas('therapy', fn ($q) => $q->where('name', 'ABA'))
                ->whereDate('appointment_date', '>', $visit->happened_at)
                ->select(DB::raw('DATE(appointment_date) as date'))
                ->groupBy('date')
                ->get()
                ->count();

            // 3. Regra de Coordenação (Meta: 10 dias)
            if ($visit->type === VisitType::Coordination && $diasDeAba >= 10) {
                $this->gerarVisitaPendente($visit, VisitType::Coordination);
            }

            // 4. Regra de Supervisão (Meta: 20 dias)
            if ($visit->type === VisitType::Supervision && $diasDeAba >= 20) {
                $this->gerarVisitaPendente($visit, VisitType::Supervision);
            }
        }
    }

    /**
     * Função auxiliar para gerar a visita com segurança (sem duplicar)
     */
    private function gerarVisitaPendente(Visit $visit, $tipo): void
    {
        // Verifica se já não existe uma visita PENDENTE desse tipo para o paciente.
        // Isso impede que o sistema crie duas visitas pendentes iguais se a pessoa clicar em "Salvar" duas vezes.
        $jaTemPendente = Visit::where('patient_id', $visit->patient_id)
            ->where('service_type_id', $visit->service_type_id)
            ->where('type', $tipo)
            ->where('status', VisitStatus::Pending)
            ->exists();

        if (!$jaTemPendente) {
            // Cria a nova solicitação silenciosamente no banco!
            Visit::create([
                'patient_id' => $visit->patient_id,
                'service_type_id' => $visit->service_type_id,
                'type' => $tipo,
                'status' => VisitStatus::Pending,
            ]);
        }
    }
}