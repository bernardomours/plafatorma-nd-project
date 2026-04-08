<?php

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\User;
use App\Models\PatientService;
use App\Observers\AppointmentObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Models\Appointment;
use App\Models\Therapy;
use App\Models\Visit;
use App\Models\Professional;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/recalculate-visits', function () {
    $output = "Iniciando modo de resgate e recálculo (ABA e DENVER)...<br><br>";

    // 0. CARIMBA AS VISITAS DO PASSADO
    $output .= "<b>Passo 0: Carimbando visitas antigas sem terapia...</b><br>";
    $visitasAntigas = \App\Models\Visit::whereNull('therapy_id')->get();
    $abaTherapy = \App\Models\Therapy::where('name', 'ABA')->first();
    $corrigidas = 0;

    foreach($visitasAntigas as $visita) {
        $dataReferencia = $visita->happened_at ?? $visita->created_at;
        
        $ultimaConsulta = \App\Models\Appointment::where('patient_id', $visita->patient_id)
            ->whereHas('therapy', fn($q) => $q->whereIn('name', ['ABA', 'DENVER']))
            ->where('appointment_date', '<=', $dataReferencia)
            ->latest('appointment_date')
            ->first();

        $therapyIdToSet = $ultimaConsulta ? $ultimaConsulta->therapy_id : ($abaTherapy ? $abaTherapy->id : null);

        if ($therapyIdToSet) {
            $visita->update(['therapy_id' => $therapyIdToSet]);
            $corrigidas++;
        }
    }
    $output .= "- <span style='color:green;'>{$corrigidas} visitas do passado foram corrigidas!</span><br><br>";

    // 1. CRIA AS FICHAS FALTANTES
    $output .= "<b>Passo 1: Sincronizando fichas de pacientes...</b><br>";
    $appointments = \App\Models\Appointment::whereHas('therapy', function($q) {
        $q->whereIn('name', ['ABA', 'DENVER']);
    })->whereNotNull('service_type_id')->get();

    foreach ($appointments as $appt) {
        \App\Models\PatientService::firstOrCreate([
            'patient_id' => $appt->patient_id,
            'service_type_id' => $appt->service_type_id,
        ]);
    }
    $output .= "- Fichas sincronizadas com sucesso!<br><br>";

    // 2. RECÁLCULO
    $output .= "<b>Passo 2: Recalculando os dias...</b><br>";
    $observer = new \App\Observers\AppointmentObserver();
    $patientServices = \App\Models\PatientService::with('patient', 'serviceType')->get();
    $terapias = \App\Models\Therapy::whereIn('name', ['ABA', 'DENVER'])->get()->keyBy('name');

    foreach ($patientServices as $service) {
        $patient = $service->patient;
        if (!$patient) continue;

        foreach (['ABA', 'DENVER'] as $therapyName) {
            if (!isset($terapias[$therapyName])) continue;
            $therapyId = $terapias[$therapyName]->id;

            $validAppointment = \App\Models\Appointment::where('patient_id', $patient->id)
                ->where('service_type_id', $service->service_type_id)
                ->where('therapy_id', $therapyId) 
                ->latest('appointment_date')
                ->first();

            if ($validAppointment) {
                $observer->created($validAppointment); 
            } else {
                \App\Models\Visit::where('patient_id', $patient->id)
                    ->where(fn($q) => $q->where('service_type_id', $service->service_type_id)->orWhereNull('service_type_id'))
                    ->where('status', \App\Enums\VisitStatus::Pending->value)
                    ->where('therapy_id', $therapyId) 
                    ->delete();
            }
        }
    }
    $output .= "- Recálculo finalizado!<br><br>";

    // 3. A GRANDE FAXINA DE DUPLICIDADES
    $output .= "<b>Passo 3: Limpando visitas pendentes duplicadas (Aspirador de Pó)...</b><br>";
    $pendentes = \App\Models\Visit::where('status', \App\Enums\VisitStatus::Pending->value)->get();

    // Agrupa as visitas por: Paciente + Ambiente + Tipo + Terapia
    $grupos = $pendentes->groupBy(function($visit) {
        // 👇 Colocamos o ->value depois do type para transformar o Enum em texto!
        return $visit->patient_id . '-' . $visit->service_type_id . '-' . $visit->type->value . '-' . $visit->therapy_id;
    });

    $apagadas = 0;
    foreach ($grupos as $chave => $grupo) {
        if ($grupo->count() > 1) {
            // Ordena da mais recente para a mais antiga, salva a 1ª e separa o resto para exclusão
            $paraApagar = $grupo->sortByDesc('id')->slice(1);
            foreach ($paraApagar as $visita) {
                $visita->delete();
                $apagadas++;
            }
        }
    }
    $output .= "- <span style='color:green;'>{$apagadas} visitas clonadas/duplicadas foram eliminadas!</span><br><br>";

    $output .= "<b>✅ Faxina completa! Tabela limpinha.</b>";

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