<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class UnitScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Adicione esta linha temporariamente para testar:
        // dd('O Scope está sendo chamado!'); 

        $user = Auth::user();

        // Verifique se o nome da coluna no seu banco é exatamente 'is_admin'
        // Se no seu banco for 'role' ou outro nome, ele sempre retornará false e não filtrará
        if (! $user || $user->is_admin) { 
            return;
        }

        $builder->where($model->getTable() . '.unit_id', $user->unit_id);
}
}
