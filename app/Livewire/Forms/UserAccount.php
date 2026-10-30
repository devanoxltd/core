<?php

declare(strict_types=1);

namespace Devanox\Core\Livewire\Forms;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

final class UserAccount extends Form
{
    public string $username;

    public string $email;

    #[Locked]
    public ?int $userId = null;

    public string $password;

    public string $passwordConfirmation;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'username' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userId)],
            'password' => 'required|string|min:8|confirmed:passwordConfirmation',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'username' => __('core::install.steps.admin.form.username'),
            'email' => __('core::install.steps.admin.form.email'),
            'password' => __('core::install.steps.admin.form.password'),
            'passwordConfirmation' => __('core::install.steps.admin.form.password_confirmation'),
        ];
    }

    public function save(): User
    {
        $this->validate();

        /** @var class-string<User> $userClass */
        $userClass = Config::get('auth.providers.users.model', User::class);

        /** @var User $user */
        $user = $this->userId !== null ? $userClass::query()->findOrFail($this->userId) : new $userClass;
        $user->fill([
            'name' => $this->username,
            'email' => $this->email,
            'password' => $this->password,
            'email_verified_at' => now(),
        ]);
        $user->save();

        // assignRole method exists on User model the we call it
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('admin');
        }

        return $user;
    }
}
