<?php

declare(strict_types=1);

use App\Traits\Livewire\HasToast;
use Devanox\Core\Helpers\EnvEditor;
use Devanox\Core\Helpers\InstallerInfo;
use Devanox\Core\Livewire\Forms\AppDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    use HasToast;

    public AppDatabase $form;

    #[Locked]
    public bool $isConfigured = false;

    public function mount(): void
    {
        $this->form->appUrl = request()->host();
        $this->form->host = config('database.connections.mysql.host');
        $this->form->port = config('database.connections.mysql.port');
        $this->form->database = config('database.connections.mysql.database');
        $this->form->dbUsername = config('database.connections.mysql.username');
        $this->form->dbPassword = config('database.connections.mysql.password');
    }

    public function submit(): void
    {
        $this->validate();
        $this->checkDbConnection();
    }

    public function checkDbConnection(): void
    {
        $config = config('database.connections.mysql');
        $config['host'] = $this->form->host;
        $config['port'] = $this->form->port;
        $config['database'] = $this->form->database;
        $config['username'] = $this->form->dbUsername;
        $config['password'] = $this->form->dbPassword;

        config(['database.connections.mysql' => $config]);

        DB::purge('mysql');

        try {
            $connection = DB::connection('mysql')->getPdo();
        } catch (Exception $exception) {
            $this->toast(__('core::install.steps.database.connection.title'), __('core::install.steps.database.connection.error_message', ['message' => $exception->getMessage()]), 'error');

            return;
        }

        if (! $connection) {
            $this->toast(__('core::install.steps.database.connection.title'), __('core::install.steps.database.connection.error'), 'error');

            return;
        }

        InstallerInfo::setStatus(InstallerInfo::DB_CONFIGURING);

        $this->updateEnv();
    }

    public function updateEnv(): void
    {
        EnvEditor::insertMultiple([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $this->form->host,
            'DB_PORT' => $this->form->port,
            'DB_DATABASE' => $this->form->database,
            'DB_USERNAME' => $this->form->dbUsername,
            'DB_PASSWORD' => $this->form->dbPassword,
            'APP_DOMAIN' => $this->form->appUrl,
        ]);

        $this->toast(__('core::install.steps.database.connection.title'), __('core::install.steps.database.connection.success'), 'success');
        $this->dispatch('stepReady', step: 'database')->to('core::install');
        $this->isConfigured = true;
        InstallerInfo::setStatus(InstallerInfo::DB_CONFIGURED);
    }

    public function edit(): void
    {
        $this->isConfigured = false;
        $this->dispatch('unsetNextStep')->to('core::install');
    }
};
