<?php

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\User;
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

Route::get('/migrar-unidades-usuarios', function () {
    $users = User::whereNotNull('unit_id')->get();
    $contador = 0;

    foreach ($users as $user) {
        $user->units()->syncWithoutDetaching([$user->unit_id]);
        $contador++;
    }

    return "Migração concluída com sucesso! {$contador} usuários foram atualizados para o novo formato de múltiplas unidades.";
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
    $agendamentos = Appointment::whereNotNull('service_type_id')
        ->select('patient_id', 'service_type_id')
        ->distinct()
        ->get();

    $criados = 0;
    foreach ($agendamentos as $agendamento) {
        $vinculo = PatientService::firstOrCreate([
            'patient_id' => $agendamento->patient_id,
            'service_type_id' => $agendamento->service_type_id,
        ]);
        
        if ($vinculo->wasRecentlyCreated) {
            $criados++;
        }
    }
    $output .= "✅ <b>{$criados}</b> vínculos fantasmas (pacientes invisíveis) foram criados.<br>";
    $vinculos = PatientService::all();
    $removidos = 0;

    foreach ($vinculos as $vinculo) {
        $temAgendamento = Appointment::where('patient_id', $vinculo->patient_id)
            ->where('service_type_id', $vinculo->service_type_id)
            ->exists();

        $temVisita = Visit::where('patient_id', $vinculo->patient_id)
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