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
    $output = "Iniciando recálculo de visitas...<br><br>";

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
            $output .= "-> Acionando o observador para recalcular...<br>";
            $observer->created($validAppointment);
            $output .= "- <span style='color:green;'>Observer rodou com sucesso!</span><br><br>";
        } else {
            // 🚨 O PULO DO GATO: Não tem agendamento? Então limpa o lixo!
            $deleted = Visit::where('patient_id', $patient->id)
                ->where(fn($q) => $q->where('service_type_id', $service->service_type_id)->orWhereNull('service_type_id'))
                ->where('status', VisitStatus::Pending->value)
                ->delete();

            if ($deleted) {
                $output .= "- <span style='color:orange;'>⚠️ Limpeza: {$deleted} visita(s) pendente(s) órfã(s) apagada(s) pois o ambiente ficou vazio.</span><br><br>";
            } else {
                $output .= "- Resultado: Sem agendamentos e sem visitas órfãs. Tudo limpo.<br><br>";
            }
        }
    }

    $output .= "<b>✅ Recálculo finalizado com sucesso!</b>";

    return $output;
});

Route::get('/disparar-aniversarios', function () {
    Artisan::call('app:send-birthday-emails'); // ou 'nucleo:notificar-aniversarios'
    return 'E-mails de aniversário enviados com sucesso!';
});

Route::get('/migrar-terapias', function () {
    $profissionais = Professional::whereNotNull('therapy_id')->get();
    $atualizados = 0;

    foreach ($profissionais as $profissional) {
        if (!$profissional->therapies()->where('therapy_id', $profissional->therapy_id)->exists()) {
            $profissional->therapies()->attach($profissional->therapy_id);
            $atualizados++;
        }
    }

    return "<h1>Migração concluída com sucesso! 🎉</h1> <p>{$atualizados} profissionais foram transferidos para a nova estrutura de terapias.</p>";
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