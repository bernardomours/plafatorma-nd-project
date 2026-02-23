<?php

use App\Models\Patient;
use App\Observers\AppointmentObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Models\Appointment;
use App\Models\Visit;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/recalculate-visits', function () {
    $observer = new AppointmentObserver();
    $patients = Patient::all();
    $output = "Iniciando recálculo de visitas...<br><br>";

    foreach ($patients as $patient) {
        // A lógica do observador só precisa do ID do paciente.
        // Pegamos qualquer atendimento dele apenas para usar como "gatilho".
        $anyAppointment = Appointment::where('patient_id', $patient->id)->first();

        if ($anyAppointment) {
            $output .= "<b>Verificando Paciente: {$patient->name}</b><br>";
            // Acionamos o observador manualmente para este paciente
            $observer->created($anyAppointment);
            $output .= "Verificação concluída.<br><br>";
        } else {
            $output .= "Paciente: {$patient->name} não possui atendimentos. Pulando...<br><br>";
        }
    }

    $output .= "<b>Recálculo finalizado!</b> Verifique a página de Visitas no seu painel de administração.";

    return $output;
});
