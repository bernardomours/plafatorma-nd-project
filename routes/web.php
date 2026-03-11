<?php

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Patient;
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
    // Pega todos os pacientes.
    $patients = Patient::all();
    $output = "Iniciando recálculo de visitas...<br><br>";

    foreach ($patients as $patient) {
        $output .= "<b>Verificando Paciente: {$patient->name}</b><br>";

        $anyAppointment = Appointment::where('patient_id', $patient->id)->first();

        if ($anyAppointment) {

            // Estado ANTES: Busca as contagens iniciais.
            $before_counts = Patient::withCount([
                'visits as pending_coordination_visits_count' => fn ($q) => $q->where('type', VisitType::Coordination)->where('status', VisitStatus::Pending),
                'visits as pending_supervision_visits_count' => fn ($q) => $q->where('type', VisitType::Supervision)->where('status', VisitStatus::Pending),
            ])->find($patient->id);

            $before_coord_count = $before_counts->pending_coordination_visits_count;
            $before_super_count = $before_counts->pending_supervision_visits_count;

            $output .= $before_coord_count > 0
                ? "- Status: Já possui {$before_coord_count} visita(s) de Coordenação pendente(s).<br>"
                : "- Status: Nenhuma visita de Coordenação pendente encontrada.<br>";

            $output .= $before_super_count > 0
                ? "- Status: Já possui {$before_super_count} visita(s) de Supervisão pendente(s).<br>"
                : "- Status: Nenhuma visita de Supervisão pendente encontrada.<br>";

            $output .= "-> Acionando o observador para recalcular...<br>";

            // Ação: Roda o observador, que pode criar visitas.
            $observer->created($anyAppointment);

            // Estado DEPOIS: Re-carrega o paciente com as contagens atualizadas.
            $after_counts = Patient::withCount([
                'visits as pending_coordination_visits_count' => fn ($q) => $q->where('type', VisitType::Coordination)->where('status', VisitStatus::Pending),
                'visits as pending_supervision_visits_count' => fn ($q) => $q->where('type', VisitType::Supervision)->where('status', VisitStatus::Pending),
            ])->find($patient->id);

            $after_coord_count = $after_counts->pending_coordination_visits_count;
            $after_super_count = $after_counts->pending_supervision_visits_count;

            $visitCreated = false;
            if ($after_coord_count > $before_coord_count) {
                $output .= "- <span style='color:green;'><b>Sucesso:</b> Nova visita de Coordenação foi criada!</span><br>";
                $visitCreated = true;
            }
            if ($after_super_count > $before_super_count) {
                $output .= "- <span style='color:green;'><b>Sucesso:</b> Nova visita de Supervisão foi criada!</span><br>";
                $visitCreated = true;
            }

            if (!$visitCreated) {
                $output .= "- Resultado: Nenhuma nova visita foi necessária.<br>";
            }

            $output .= "Verificação concluída.<br><br>";
        } else {
            $output .= "- Paciente: {$patient->name} não possui atendimentos. Pulando...<br><br>";
        }
    }

    $output .= "<b>Recálculo finalizado!</b>";

    return $output;
});

// Route::get('/disparar-aniversarios', function () {
//     Artisan::call('app:send-birthday-emails'); // ou 'nucleo:notificar-aniversarios'
//     return 'E-mails de aniversário enviados com sucesso!';
// });

// Route::get('/arrumar-visitas', function () {
//     $idClinica = \App\Models\ServiceType::where('name', 'Clínica')->value('id');
//     $atualizados = \App\Models\Visit::whereNull('service_type_id')->update(['service_type_id' => $idClinica]);
//     return "Mágica feita! {$atualizados} visitas atualizadas para Clínica.";
// });

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