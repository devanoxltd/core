<?php

declare(strict_types=1);

namespace Devanox\Core\Livewire\Forms;

use App\Models\User;
use Livewire\Form;

final class UserAccount extends Form
{
    public string $username;

    public string $email;

    public string $password;

    public string $passwordConfirmation;

    public function rules(): array
    {
        return [
            'username' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed:passwordConfirmation',
        ];
    }

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

        $user = new User;
        $user->name = $this->username;
        $user->email = $this->email;
        $user->password = $this->password;
        $user->email_verified_at = now();
        $user->save();

        // assignRole method exists on User model the we call it
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('admin');
        }

        return $user;
    }
}
