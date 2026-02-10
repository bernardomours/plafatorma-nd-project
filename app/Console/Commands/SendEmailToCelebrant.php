<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Patient;
use App\Models\Professional;
use App\Mail\BirthdayCelebrants;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendEmailToCelebrant extends Command
{
    protected $signature = 'app:send-birthday-emails';
    protected $description = 'Envia e-mail ao RH com os aniversariantes do dia, separados por unidade';

    public function handle()
    {
        $hoje = Carbon::today();
        
        // Busca todos os aniversariantes do dia, já carregando a unidade
        $users = User::with('unit')->whereMonth('birth_date', $hoje->month)
                       ->whereDay('birth_date', $hoje->day)
                       ->get()
                       ->each(fn($item) => $item->tipo_pessoa = 'Administrativo');

        $professionals = Professional::with('unit')->whereMonth('birth_date', $hoje->month)
                                       ->whereDay('birth_date', $hoje->day)
                                       ->get()
                                       ->each(fn($item) => $item->tipo_pessoa = 'Profissional(is)');
        
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

        // Envia e-mail para o RH de Mossoró
        if ($aniversariantesMossoro->isNotEmpty()) {
            Mail::to('bindmossoro@gmail.com')->send(new BirthdayCelebrants($aniversariantesMossoro));
            $this->info('E-mail para RH Mossoró enviado com ' . $aniversariantesMossoro->count() . ' aniversariantes.');
        } else {
            $this->info('Nenhum aniversariante hoje para o RH Mossoró.');
        }

        // Envia e-mail para o RH de Natal
        if ($aniversariantesNatal->isNotEmpty()) {
            Mail::to('bernardo.araujo1612@gmail.com')->send(new BirthdayCelebrants($aniversariantesNatal));
            $this->info('E-mail para RH Natal enviado com ' . $aniversariantesNatal->count() . ' aniversariantes.');
        } else {
            $this->info('Nenhum aniversariante hoje para o RH Natal.');
        }
    }
}
