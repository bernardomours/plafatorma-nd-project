<?php

namespace App\Models\Scopes;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class UnitScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user || $user->is_admin) {
            return;
        }

        $mossoroUnitId = 1;

        if ($model instanceof Appointment) {
            $builder->whereHas('patient', function (Builder $query) use ($user, $mossoroUnitId) {
                if ((int)$user->unit_id === $mossoroUnitId) {
                    $query->where('unit_id', $mossoroUnitId);
                } else {
                    $query->where('unit_id', '!=', $mossoroUnitId);
                }
            });
        } else {
            if ((int)$user->unit_id === $mossoroUnitId) {
                $builder->where($model->getTable() . '.unit_id', $mossoroUnitId);
            } else {
                $builder->where($model->getTable() . '.unit_id', '!=', $mossoroUnitId);
            }
        }
    }
}
