<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;

class CustomProfile extends BaseEditProfile
{
    public function save(): void
    {
        $senhaFoiAlterada = !empty($this->data['password']);
        parent::save();

        if ($senhaFoiAlterada) {
            auth()->logout();
            $this->redirect(filament()->getLoginUrl());
        }
    }
}