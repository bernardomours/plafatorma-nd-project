<?php

namespace App\Models\Scopes;

use App\Models\Appointment;
use App\Models\RequestedService;
use App\Models\Schedule;
use App\Models\Professional; // Importante: Adicionado o model Professional
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class UnitScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        // 1. Catraca VIP: Se não tiver usuário logado ou se for admin, libera tudo.
        if (! $user || $user->is_admin) {
            return;
        }

        // 2. Descobre TODAS as unidades vinculadas ao usuário atual
        $userUnitIds = [];

        // Se o usuário tem uma unidade direta vinculada na tabela users
        if ($user->unit_id) {
            $userUnitIds[] = $user->unit_id;
        } else {
            // O Truque do E-mail: Busca no profissional ignorando bloqueios temporariamente
            // (Agora usando a relação 'units' em vez da coluna apagada 'unit_id')
            $profissional = Professional::withoutGlobalScopes()
                                ->with('units')
                                ->where('email', $user->email)
                                ->first();
            
            if ($profissional) {
                // Pega todos os IDs das unidades em que esse profissional trabalha e transforma num array
                $userUnitIds = $profissional->units->pluck('id')->toArray();
            }
        }

        // Se mesmo assim não achar a unidade (ex: e-mail errado), bloqueia por segurança
        if (empty($userUnitIds)) {
            $builder->whereRaw('1 = 0');
            return;
        }

        $mossoroUnitId = 1;
        $modelClass = get_class($model);
        
        // Verifica se a unidade 1 (Mossoró) está dentro do array de unidades do usuário logado
        $isMossoro = in_array($mossoroUnitId, $userUnitIds);

        // 3. Aplica a regra de filtragem
        
        // REGRA A: Modelos que puxam a unidade através da relação com o Paciente
        if (
            $modelClass === Appointment::class ||
            $modelClass === RequestedService::class ||
            $modelClass === Schedule::class
        ) {
            $builder->whereHas('patient', function (Builder $query) use ($isMossoro, $mossoroUnitId) {
                // Mantém sua lógica original intacta para pacientes
                if ($isMossoro) {
                    $query->where('unit_id', $mossoroUnitId);
                } else {
                    $query->where('unit_id', '!=', $mossoroUnitId);
                }
            });
        } 
        
        // REGRA B: A NOVA REGRA DO PROFISSIONAL (Cruzamento Exato)
        // Se o usuário for de Natal (ex: id 3), só verá profissionais que tenham a unidade 3 vinculada.
        elseif ($modelClass === Professional::class) {
            $builder->whereHas('units', function (Builder $query) use ($userUnitIds) {
                $query->whereIn('units.id', $userUnitIds);
            });
        }
        
        // REGRA C: Todos os outros modelos (Pacientes, Visitas, etc) que ainda possuem a coluna unit_id
        else {
            // Mantém sua lógica original intacta para o resto do sistema
            if ($isMossoro) {
                $builder->where($model->getTable() . '.unit_id', $mossoroUnitId);
            } else {
                $builder->where($model->getTable() . '.unit_id', '!=', $mossoroUnitId);
            }
        }
    }
}