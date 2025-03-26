<?php

namespace Devanox\Core\Livewire;

use App\Trait\Livewire\Toast;
use Devanox\Core\Helpers\EnvEditor;
use Devanox\Core\Livewire\Forms\AppDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Database extends Component
{
    use Toast;

    public AppDatabase $form;

    public $isConfigured = false;

    public function mount()
    {
        $this->form->appUrl = request()->host();
        $this->form->host = config('database.connections.mysql.host');
        $this->form->port = config('database.connections.mysql.port');
        $this->form->database = config('database.connections.mysql.database');
        $this->form->dbUsername = config('database.connections.mysql.username');
        $this->form->dbPassword = config('database.connections.mysql.password');
    }

    public function submit()
    {
        $this->form->validate(
            [
                'appUrl' => 'required|string',
                'host' => 'required|string',
                'port' => 'required|integer',
                'database' => 'required|string',
                'dbUsername' => 'required|string',
                'dbPassword' => 'nullable|string',
            ],
            attributes: [
                'appUrl' => __('core::install.steps.database.form.appUrl'),
                'host' => __('core::install.steps.database.form.host'),
                'port' => __('core::install.steps.database.form.port'),
                'database' => __('core::install.steps.database.form.database'),
                'dbUsername' => __('core::install.steps.database.form.dbUsername'),
                'dbPassword' => __('core::install.steps.database.form.dbPassword'),
            ]
        );

        // $this->form->dbUsername = '';
        $this->checkDbConnection();
    }

    public function checkDbConnection() {

        $config = config('database.connections.mysql');
        $config['host'] = $this->form->host;
        $config['port'] = $this->form->port;
        $config['database'] = $this->form->database;
        $config['username'] = $this->form->dbUsername;
        $config['password'] = $this->form->dbPassword;

        config(['database.connections.mysql' => $config]);

        DB::purge();

        try {
            $connection = DB::connection()->getPdo();
        } catch (\Exception $e) {
            $this->toast(__('core::install.steps.database.connection.title'), __('core::install.steps.database.connection.errorMessage', ['message' => $e->getMessage()]), 'error');
            return;
        }

        if (!$connection) {
            $this->toast(__('core::install.steps.database.connection.title'), __('core::install.steps.database.connection.error'), 'error');
            return;
        }

        $this->updateEnv();
    }

    public function updateEnv()
    {
        EnvEditor::setMultiple([
            'DB_HOST' => $this->form->host,
            'DB_PORT' => $this->form->port,
            'DB_DATABASE' => $this->form->database,
            'DB_USERNAME' => $this->form->dbUsername,
            'DB_PASSWORD' => $this->form->dbPassword,
            'APP_DOMAIN' => $this->form->appUrl,
        ]);

        $this->toast(__('core::install.steps.database.connection.title'), __('core::install.steps.database.connection.success'), 'success');
        $this->dispatch('stepReady', step: 'database')->to(Install::class);
        $this->isConfigured = true;
    }

    public function edit()
    {
        $this->isConfigured = false;
        $this->dispatch('unsetNextStep')->to(Install::class);
    }

    public function render()
    {
        return view('core::livewire.database');
    }
}
