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
}
