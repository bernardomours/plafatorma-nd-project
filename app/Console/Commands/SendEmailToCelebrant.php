<?php

namespace App\Console\Commands;

use App\Mail\BirthdayCelebrants;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendEmailToCelebrant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-birthday-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia e-mail ao RH com os aniversariantes do dia, separados por unidade';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $hoje = Carbon::today();

            // Busca aniversariantes em Users
            $users = User::with('unit')->whereMonth('birth_date', $hoje->month)
                              ->whereDay('birth_date', $hoje->day)
                              ->get()
                              ->each(fn($item) => $item->tipo_pessoa = 'Usuário(s)');

            // Busca aniversariantes em Professionals
            $professionals = Professional::with('unit')->whereMonth('birth_date', $hoje->month)
                                     ->whereDay('birth_date', $hoje->day)
                                     ->get()
                                     ->each(fn($item) => $item->tipo_pessoa = 'Profissional(is)');

            // Busca aniversariantes em Patients
            $patients = Patient::with('unit')->whereMonth('birth_date', $hoje->month)
                                 ->whereDay('birth_date', $hoje->day)
                                 ->get()
                                 ->each(fn($item) => $item->tipo_pessoa = 'Paciente(s)');

            // Junta todos em uma única coleção
            $todosAniversariantes = collect([])->merge($users)->merge($professionals)->merge($patients);

            if ($todosAniversariantes->isEmpty()) {
                $this->info('Nenhum aniversariante hoje.');
                return;
            }

            // Separa os aniversariantes por unidade
            $aniversariantesMossoro = $todosAniversariantes->where('unit_id', 1);
            $aniversariantesNatal = $todosAniversariantes->where('unit_id', '!=', 1);

            // Lógica para Unidade Mossoró
            if ($aniversariantesMossoro->isNotEmpty()) {
                $pacientesMossoro = $aniversariantesMossoro->where('tipo_pessoa', 'Paciente(s)');
                $outrosAniversariantesMossoro = $aniversariantesMossoro->where('tipo_pessoa', '!=', 'Paciente(s)');

                if ($pacientesMossoro->isNotEmpty()) {
                    Mail::to('controlesinternos@ndmossoro.com')->send(new BirthdayCelebrants($pacientesMossoro));
                    $this->info('E-mail para Controles Internos Mossoró enviado com ' . $pacientesMossoro->count() . ' aniversariantes (pacientes).');
                }

                if ($outrosAniversariantesMossoro->isNotEmpty()) {
                    Mail::to('rh@ndmossoro.com')->send(new BirthdayCelebrants($outrosAniversariantesMossoro));
                    $this->info('E-mail para RH Mossoró enviado com ' . $outrosAniversariantesMossoro->count() . ' aniversariantes (profissionais/usuários).');
                }
            } else {
                $this->info('Nenhum aniversariante hoje para a unidade de Mossoró.');
            }

            // Lógica para Outras Unidades
            if ($aniversariantesNatal->isNotEmpty()) {
                $pacientesNatal = $aniversariantesNatal->where('tipo_pessoa', 'Paciente(s)');
                $outrosAniversariantesNatal = $aniversariantesNatal->where('tipo_pessoa', '!=', 'Paciente(s)');

                if ($pacientesNatal->isNotEmpty()) {
                    Mail::to('controlesinternos@ndnatal.com')->send(new BirthdayCelebrants($pacientesNatal));
                    $this->info('E-mail para Controles Internos Natal enviado com ' . $pacientesNatal->count() . ' aniversariantes (pacientes).');
                }

                if ($outrosAniversariantesNatal->isNotEmpty()) {
                    Mail::to('rh@ndnatal.com')->send(new BirthdayCelebrants($outrosAniversariantesNatal));
                    $this->info('E-mail para RH Natal enviado com ' . $outrosAniversariantesNatal->count() . ' aniversariantes (profissionais/usuários).');
                }
            } else {
                $this->info('Nenhum aniversariante hoje para as outras unidades.');
            }

        } catch (\Exception $e) {
            $this->error('Ocorreu um erro ao enviar os e-mails: ' . $e->getMessage());
        }
    }
}
