<?php

declare(strict_types=1);

use Devanox\Core\Helpers\InstallerInfo;
use Devanox\Core\Livewire\Forms\UserAccount;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Config;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    public UserAccount $userAccount;

    #[Locked]
    public bool $isCreated = false;

    public function mount(): void
    {
        $isAlreadyCreated = InstallerInfo::getStatus() === InstallerInfo::ADMIN_CREATED;

        InstallerInfo::setStatus(InstallerInfo::ADMIN_CREATING);

        $this->userAccount->username = 'admin';
        $this->userAccount->email = 'admin@' . request()->host();

        /** @var class-string<User> $userClass */
        $userClass = Config::get('auth.providers.users.model', User::class);

        if ($user = $userClass::query()->first()) {
            $this->userAccount->email = $user->email;

            if ($isAlreadyCreated) {
                $this->isCreated = true;
                $this->dispatch('stepReady', step: 'admin')->to('core::install');
                InstallerInfo::setStatus(InstallerInfo::ADMIN_CREATED);

                return;
            }

            // A seeded user (e.g. test@example.com) can be edited and used as the admin account.
            $this->userAccount->userId = $user->getKey();
            $this->userAccount->username = (string) $user->getAttribute('name');
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
