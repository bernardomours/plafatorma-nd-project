<?php

namespace App\Models\Scopes;

use App\Models\Appointment;
use App\Models\RequestedService;
use App\Models\Schedule;
use App\Models\PatientService;
use App\Models\Professional; 
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

        $userUnitIds = [];

        if ($user->unit_id) {
            $userUnitIds[] = $user->unit_id;
        } else {
            $profissional = Professional::withoutGlobalScopes()
                                ->with('units')
                                ->where('email', $user->email)
                                ->first();
            
            if ($profissional) {
                $userUnitIds = $profissional->units->pluck('id')->toArray();
            }
        }

        if (empty($userUnitIds)) {
            $builder->whereRaw('1 = 0');
            return;
        }

        $mossoroUnitId = 1;
        $modelClass = get_class($model);   
        $isMossoro = in_array($mossoroUnitId, $userUnitIds);        
        if (
            $modelClass === Appointment::class ||
            $modelClass === RequestedService::class ||
            $modelClass === Schedule::class
        ) {
            $builder->whereHas('patient', function (Builder $query) use ($isMossoro, $mossoroUnitId) {
                if ($isMossoro) {
                    $query->where('unit_id', $mossoroUnitId);
                } else {
                    $query->where('unit_id', '!=', $mossoroUnitId);
                }
            });
        } 
        
        elseif ($modelClass === Professional::class) {
            $builder->whereHas('units', function (Builder $query) use ($isMossoro, $mossoroUnitId) {
                if ($isMossoro) {
                    $query->where('units.id', $mossoroUnitId);
                } else {
                    $query->where('units.id', '!=', $mossoroUnitId);
                }
            });
        }
        
        else {
            if ($isMossoro) {
                $builder->where($model->getTable() . '.unit_id', $mossoroUnitId);
            } else {
                $builder->where($model->getTable() . '.unit_id', '!=', $mossoroUnitId);
            }
        }
    }
}