<?php

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Patient;
use App\Models\PatientService;
use App\Observers\AppointmentObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Models\Appointment;
use App\Models\Visit;
use App\Models\Professional;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/recalculate-visits', function () {
    $observer = new AppointmentObserver();
    $patientServices = PatientService::with('patient')->get();
    $output = "Iniciando recálculo de visitas (Modo Turbinado)...<br><br>";

    foreach ($patientServices as $service) {
        $patient = $service->patient;
        if (!$patient) continue;

        $ambienteNome = $service->serviceType ? $service->serviceType->name : 'Desconhecido';
        $output .= "<b>Verificando Paciente: {$patient->name} | Ambiente: {$ambienteNome}</b><br>";

        // Pega um agendamento válido DESTE ambiente que seja de ABA
        $validAppointment = Appointment::where('patient_id', $patient->id)
            ->where('service_type_id', $service->service_type_id)
            ->whereHas('therapy', fn ($q) => $q->where('name', 'ABA'))
            ->latest('appointment_date')
            ->first();

        if ($validAppointment) {
            $before_counts = Patient::withCount([
                'visits as pending_coordination_visits_count' => fn ($q) => $q->where('type', VisitType::Coordination->value)->where('status', VisitStatus::Pending->value),
                'visits as pending_supervision_visits_count' => fn ($q) => $q->where('type', VisitType::Supervision->value)->where('status', VisitStatus::Pending->value),
            ])->find($patient->id);

            $before_coord_count = $before_counts->pending_coordination_visits_count;
            $before_super_count = $before_counts->pending_supervision_visits_count;

            $output .= "-> Acionando o observador para recalcular...<br>";

            $observer->created($validAppointment);

            $after_counts = Patient::withCount([
                'visits as pending_coordination_visits_count' => fn ($q) => $q->where('type', VisitType::Coordination->value)->where('status', VisitStatus::Pending->value),
                'visits as pending_supervision_visits_count' => fn ($q) => $q->where('type', VisitType::Supervision->value)->where('status', VisitStatus::Pending->value),
            ])->find($patient->id);

            $after_coord_count = $after_counts->pending_coordination_visits_count;
            $after_super_count = $after_counts->pending_supervision_visits_count;

            $visitCreated = false;
            if ($after_coord_count > $before_coord_count) {
                $output .= "- <span style='color:green;'><b>Sucesso:</b> Nova visita de Coordenação criada!</span><br>";
                $visitCreated = true;
            }
            if ($after_super_count > $before_super_count) {
                $output .= "- <span style='color:green;'><b>Sucesso:</b> Nova visita de Supervisão criada!</span><br>";
                $visitCreated = true;
            }

            if (!$visitCreated) {
                $output .= "- Resultado: Nenhuma nova visita foi necessária.<br>";
            }

            $output .= "Verificação concluída para este ambiente.<br><br>";
        } else {
            $output .= "- Resultado: Paciente não possui agendamentos de ABA neste ambiente. Pulando...<br><br>";
        }
    }

    $output .= "<b>✅ Recálculo finalizado com sucesso!</b>";

    return $output;
});

Route::get('/disparar-aniversarios', function () {
    Artisan::call('app:send-birthday-emails'); // ou 'nucleo:notificar-aniversarios'
    return 'E-mails de aniversário enviados com sucesso!';
});

Route::get('/sync-ambientes', function () {
    $output = "Iniciando a Máquina do Tempo de Ambientes... 🕰️<br><br>";

    // ==========================================
    // 1. O RESGATE (Criar os que estão faltando)
    // ==========================================
    $agendamentos = \App\Models\Appointment::whereNotNull('service_type_id')
        ->select('patient_id', 'service_type_id')
        ->distinct()
        ->get();

    $criados = 0;
    foreach ($agendamentos as $agendamento) {
        $vinculo = \App\Models\PatientService::firstOrCreate([
            'patient_id' => $agendamento->patient_id,
            'service_type_id' => $agendamento->service_type_id,
        ]);
        
        if ($vinculo->wasRecentlyCreated) {
            $criados++;
        }
    }
    $output .= "✅ <b>{$criados}</b> vínculos fantasmas (pacientes invisíveis) foram criados.<br>";
    $vinculos = \App\Models\PatientService::all();
    $removidos = 0;

    foreach ($vinculos as $vinculo) {
        // Regra 1: Tem algum agendamento neste ambiente?
        $temAgendamento = \App\Models\Appointment::where('patient_id', $vinculo->patient_id)
            ->where('service_type_id', $vinculo->service_type_id)
            ->exists();

        // Regra 2: Tem alguma visita (Pendente ou Concluída) registrada neste ambiente?
        $temVisita = \App\Models\Visit::where('patient_id', $vinculo->patient_id)
            ->where(function($q) use ($vinculo) {
                $q->where('service_type_id', $vinculo->service_type_id)
                  ->orWhereNull('service_type_id'); 
            })
            ->exists();

        if (!$temAgendamento && !$temVisita) {
            $vinculo->delete();
            $removidos++;
        }
    }
    
    $output .= "🧹 <b>{$removidos}</b> vínculos totalmente vazios foram removidos do painel.<br><br>";

    $output .= "<b>Sincronização e Faxina concluídas com sucesso! 🎉</b>";

    return $output;
});

Route::get('/gerar-acessos-admin', function () {
    $criados = 0;
    
    Professional::whereIn('role', ['supervisor', 'coordinator'])
        ->whereNull('user_id')
        ->whereNotNull('email')
        ->get()
        ->each(function ($professional) use (&$criados) {
            $cpfLimpo = preg_replace('/[^0-9]/', '', $professional->cpf);
            
            $user = \App\Models\User::firstOrCreate(
                ['email' => $professional->email],
                [
                    'name' => $professional->name,
                    'password' => bcrypt($cpfLimpo),
                ]
            );
            
            $professional->updateQuietly(['user_id' => $user->id]);
            $criados++;
        });
        
    return "Processo finalizado com sucesso! $criados acesso(s) criado(s).";
});