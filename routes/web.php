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
    
    // Vamos varrer os VÍNCULOS em vez de pacientes soltos. Assim cobrimos todos os ambientes!
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