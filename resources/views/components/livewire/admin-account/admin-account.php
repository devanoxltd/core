<?php

declare(strict_types=1);

use App\Models\User;
use Devanox\Core\Helpers\InstallerInfo;
use Devanox\Core\Livewire\Forms\UserAccount;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    public UserAccount $userAccount;

    #[Locked]
    public bool $isCreated = false;

    public function mount(): void
    {
        InstallerInfo::setStatus(InstallerInfo::ADMIN_CREATING);

        $this->userAccount->username = 'admin';
        $this->userAccount->email = 'admin@' . request()->host();

        if ($user = User::first()) {
            $this->isCreated = true;
            $this->dispatch('stepReady', step: 'admin')->to('core::install');
            $this->userAccount->email = $user->email;
            InstallerInfo::setStatus(InstallerInfo::ADMIN_CREATED);
        }
    }

    public function submit(): void
    {
        $this->userAccount->save();
        $this->dispatch('stepReady', step: 'admin')->to('core::install');
        $this->isCreated = true;
        InstallerInfo::setStatus(InstallerInfo::ADMIN_CREATED);
    }
};
