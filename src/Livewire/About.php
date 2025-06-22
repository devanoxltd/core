<?php

namespace Devanox\Core\Livewire;

use Devanox\Core\Models\License;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class About extends Component
{
    public ?License $license = null;

    public ?string $dbVersion = null;

    public function mount()
    {
        $this->license = License::where('is_module', false)->first();
        $this->dbVersion = DB::selectOne('SELECT version() as version')->version ?? null;
    }

    public function render()
    {
        return view('core::livewire.about');
    }
}
