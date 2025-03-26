<?php

namespace Devanox\Core\Livewire;

use App\Models\User;
use Devanox\Core\Livewire\Forms\UserAccount;
use Livewire\Component;

class AdminAccount extends Component
{
    public UserAccount $userAccount;

    public $isCreated = false;

    public function mount()
    {
        if ($user = User::first()) {
            $this->isCreated = true;
            $this->dispatch('stepReady', step: 'admin')->to(Install::class);
            $this->userAccount->email = $user->email;
        }

        $this->userAccount->username = 'admin';
        $this->userAccount->email = 'admin@' . request()->host();
    }

    public function submit()
    {
        $this->userAccount->save();
        $this->dispatch('stepReady', step: 'admin')->to(Install::class);
        $this->isCreated = true;
    }

    public function render()
    {
        return view('core::livewire.admin-account');
    }
}
