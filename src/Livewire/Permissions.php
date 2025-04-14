<?php

namespace Devanox\Core\Livewire;

use Livewire\Attributes\Locked;
use Livewire\Component;

class Permissions extends Component
{
    #[Locked]
    public array $permissions = [];

    public function mount()
    {
        $this->checkPermissions();
    }

    public function checkPermissions()
    {
        $permissions = collect();

        foreach (config('core.permissions', []) as $folder => $permission) {
            $permissions->push([
                'folder' => $folder,
                'status' => substr(sprintf('%o', fileperms(base_path($folder))), -4) >= $permission,
                'permission' => $permission,
            ]);
        }

        $this->permissions = $permissions->toArray();

        $status = $permissions->every(function ($permission) {
            return $permission['status'] === true;
        });

        if ($status) {
            $this->dispatch('stepReady', step: 'permissions')->to(Install::class);
        }
    }

    public function fixPermissions($folder, $permission)
    {
        $path = base_path($folder);

        if (file_exists($path)) {
            if (!is_dir($path)) {
                chmod($path, octdec($permission));
                $this->checkPermissions();

                return;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    chmod($item->getPathname(), octdec($permission));
                } else {
                    chmod($item->getPathname(), octdec(644));
                }
            }
        }

        $this->checkPermissions();
    }

    public function render()
    {
        return view('core::livewire.permissions');
    }
}
