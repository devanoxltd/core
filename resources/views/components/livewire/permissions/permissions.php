<?php

declare(strict_types=1);

use Devanox\Core\Helpers\InstallerInfo;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public array $permissions = [];

    public function mount(): void
    {
        $this->checkPermissions();
    }

    public function checkPermissions(): void
    {
        InstallerInfo::setStatus(InstallerInfo::PERMISSIONS_CHECKING);

        $permissions = collect();

        foreach (config('core.permissions', []) as $folder => $permission) {
            $path = base_path($folder);
            $exists = file_exists($path);

            $permissions->push([
                'folder' => $folder,
                'status' => $exists && (fileperms($path) & 0777) >= octdec((string) $permission),
                'required' => $permission,
                'current' => $exists ? sprintf('%04o', fileperms($path) & 0777) : '0000',
            ]);
        }

        $this->permissions = $permissions->toArray();

        $status = $permissions->every(fn (array $permission): bool => $permission['status'] === true);

        if ($status) {
            InstallerInfo::setStatus(InstallerInfo::PERMISSIONS_PASSED);
            $this->dispatch('stepReady', step: 'permissions')->to('core::install');
        }
    }

    public function fixPermissions(string $folder): void
    {
        $path = base_path($folder);
        $required = config('core.permissions.' . $folder);

        if (file_exists($path)) {
            chmod($path, octdec((string) $required));

            if (! is_dir($path)) {
                $this->checkPermissions();

                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    chmod($item->getPathname(), octdec((string) $required));
                } else {
                    chmod($item->getPathname(), 0644);
                }
            }
        }

        $this->checkPermissions();
    }
};
