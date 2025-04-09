<?php

namespace Devanox\Core\Livewire\Forms;

use Livewire\Form;

class AppDatabase extends Form
{
    public string $appUrl = '';

    public string $host = '';

    public string $port = '';

    public string $database = '';

    public string $dbUsername = '';

    public string $dbPassword = '';

    public function rules(): array
    {
        return [
            'appUrl' => 'required|string',
            'host' => 'required|string',
            'port' => 'required|integer',
            'database' => 'required|string',
            'dbUsername' => 'required|string',
            'dbPassword' => 'nullable|string',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'appUrl' => __('core::install.steps.database.form.appUrl'),
            'host' => __('core::install.steps.database.form.host'),
            'port' => __('core::install.steps.database.form.port'),
            'database' => __('core::install.steps.database.form.database'),
            'dbUsername' => __('core::install.steps.database.form.dbUsername'),
            'dbPassword' => __('core::install.steps.database.form.dbPassword'),
        ];
    }
}
