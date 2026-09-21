<div class="mx-6 mt-2">
    <x-ui.data.table>
        <x-slot name="tableWrapper" class="max-h-64 overflow-y-auto"></x-slot>
        <x-slot name="head" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
            <x-ui.data.table.tr>
                <x-ui.data.table.th>
                    @lang('core::install.steps.permissions.table.name')
                </x-ui.data.table.th>
                <x-ui.data.table.th class="text-right">
                    @lang('core::install.steps.permissions.table.current')
                </x-ui.data.table.th>
                <x-ui.data.table.th class="text-right">
                    @lang('core::install.steps.permissions.table.required')
                </x-ui.data.table.th>
                <x-ui.data.table.th class="text-right">
                    @lang('core::install.steps.permissions.table.status')
                </x-ui.data.table.th>
            </x-ui.data.table.tr>
        </x-slot>
        <x-slot name="body">
            @foreach ($permissions as $permission)
            <x-ui.data.table.tr wire:key="permission-{{ md5($permission['folder']) }}">
                <x-ui.data.table.td class="font-medium whitespace-nowrap">
                    {{ $permission['folder'] }}
                </x-ui.data.table.td>
                <x-ui.data.table.td class="text-right font-medium whitespace-nowrap">
                    {{ $permission['current'] }}
                </x-ui.data.table.td>
                <x-ui.data.table.td class="text-right font-medium whitespace-nowrap">
                    {{ $permission['required'] }}
                </x-ui.data.table.td>
                <x-ui.data.table.td>
                    <div class="flex items-center justify-end space-x-1.5">
                        @if ($permission['status'])
                            <x-ui.icon name="solid.check" class="size-4 text-green-500 dark:text-green-400" />
                        @else
                            @if (PHP_OS_FAMILY !== 'Windows')
                                <x-ui.form.button
                                    aria-label="{{ __('core::install.steps.permissions.table.fix_permissions', ['folder' => $permission['folder']]) }}"
                                    color="gray"
                                    size="xs"
                                    wire:click="fixPermissions('{{ $permission['folder'] }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="fixPermissions"
                                    wire:loading.class="opacity-50 cursor-not-allowed"
                                    wire:key="fix-permissions-{{ md5($permission['folder']) }}"
                                >
                                    <x-slot:before>
                                        <x-ui.icon name="solid.auto_fix_high" class="size-5" />
        </x-slot>
        </x-ui.form.button>
        @endif

        <x-ui.form.button
            aria-label="{{ __('core::install.steps.permissions.table.refresh_permissions', ['folder' => $permission['folder']]) }}"
            color="gray"
            size="xs"
            wire:click="checkPermissions"
            wire:loading.attr="disabled"
            wire:target="checkPermissions"
            wire:loading.class="opacity-50 cursor-not-allowed"
            wire:key="refresh-permissions-{{ md5($permission['folder']) }}"
        >
            <x-slot:before>
                <x-ui.icon name="solid.refresh" class="size-5" />
            </x-slot:before>
        </x-ui.form.button>
        <x-ui.icon name="solid.x" class="size-4 text-red-500 dark:text-red-400" />
        @endif
</div>
</x-ui.data.table.td>
</x-ui.data.table.tr>
@endforeach
</x-slot>
</x-ui.data.table>
</div>
