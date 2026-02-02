<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ImpersonationBanner extends Component
{
    public function stopImpersonating(): void
    {
        $impersonatorId = session()->pull('impersonator_id');

        if ($impersonatorId) {
            $impersonator = User::find($impersonatorId);

            if ($impersonator) {
                Auth::login($impersonator);
            }
        }

        $this->redirect(filament()->getHomeUrl());
    }

    public function render()
    {
        return view('livewire.impersonation-banner', [
            'isImpersonating' => session()->has('impersonator_id'),
            'impersonator' => session()->has('impersonator_id')
                ? User::find(session('impersonator_id'))
                : null,
        ]);
    }
}
