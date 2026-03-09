<?php

namespace App\Models\Scopes;

use App\Models\Appointment;
use App\Models\RequestedService;
use App\Models\Schedule;
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

        // 2. Descobre a verdadeira unidade do usuário
        $userUnitId = $user->unit_id;

        // O Truque do E-mail: Busca no profissional ignorando bloqueios temporariamente
        if (!$userUnitId) {
            $profissional = \App\Models\Professional::withoutGlobalScopes()
                                ->where('email', $user->email)
                                ->first();
            $userUnitId = $profissional?->unit_id;
        }

        // Se mesmo assim não achar a unidade (ex: e-mail errado), bloqueia por segurança
        if (!$userUnitId) {
            $builder->whereRaw('1 = 0');
            return;
        }

        $mossoroUnitId = 1;
        $modelClass = get_class($model);

        // 3. Aplica a sua regra original, mas agora usando a unidade verdadeira ($userUnitId)
        if (
            $modelClass === Appointment::class ||
            $modelClass === RequestedService::class ||
            $modelClass === Schedule::class
        ) {
            $builder->whereHas('patient', function (Builder $query) use ($userUnitId, $mossoroUnitId) {
                if ((int)$userUnitId === $mossoroUnitId) {
                    $query->where('unit_id', $mossoroUnitId);
                } else {
                    $query->where('unit_id', '!=', $mossoroUnitId);
                }
            });
        } else {
            if ((int)$userUnitId === $mossoroUnitId) {
                $builder->where($model->getTable() . '.unit_id', $mossoroUnitId);
            } else {
                $builder->where($model->getTable() . '.unit_id', '!=', $mossoroUnitId);
            }
        }
    }
}