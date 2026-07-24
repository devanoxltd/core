<div class="mx-2 mt-2 overflow-hidden rounded-lg border border-gray-300 dark:border-gray-600">
    <div class="max-h-64 snap-y snap-mandatory overflow-x-hidden overflow-y-auto">
        <table class="w-full table-auto text-left text-sm text-gray-500 dark:text-gray-400">
            <thead class="sticky top-0 bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="py-2 pl-2 font-medium text-gray-900 dark:text-white">
                        @lang('core::install.steps.permissions.table.name')
                    </th>
                    <th scope="col" class="py-2 pr-2 text-right font-medium text-gray-900 dark:text-white">
                        @lang('core::install.steps.permissions.table.current')
                    </th>
                    <th scope="col" class="py-2 pr-2 text-right font-medium text-gray-900 dark:text-white">
                        @lang('core::install.steps.permissions.table.required')
                    </th>
                    <th scope="col" class="py-2 pr-2 text-right font-medium text-gray-900 dark:text-white">
                        @lang('core::install.steps.permissions.table.status')
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($permissions as $permission)
                    <tr wire:key="permission-{{ md5($permission['folder']) }}" class="snap-end border-b bg-white dark:border-gray-600 dark:bg-gray-800">
                        <td class="py-2 pl-2 font-medium whitespace-nowrap text-gray-900 dark:text-white">
                            {{ $permission['folder'] }}
                        </td>
                        <td class="py-2 pr-2 text-right font-medium whitespace-nowrap text-gray-900 dark:text-white">
                            {{ $permission['current'] }}
                        </td>
                        <td
                            class="py-2 pr-2 text-right font-medium whitespace-nowrap text-gray-900 dark:text-white"
                        >
                            {{ $permission['required'] }}
                        </td>
                        <td class="flex items-center justify-end space-x-1.5 py-2 pr-2 text-right font-medium whitespace-nowrap text-gray-900 dark:text-white">
                                @if ($permission['status'])
                                <x-ui.icon name="solid.check" class="size-4 text-green-500 dark:text-green-400" />
                            @else
                                <x-ui.form.button
                                    aria-label="{{__('core::install.steps.permissions.table.fix_permissions', ['folder' => $permission['folder']])}}"
                                    color="gray"
                                    size="xs"
                                    wire:click="fixPermissions('{{ $permission['folder'] }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="fixPermissions"
                                    wire:loading.class="opacity-50 cursor-not-allowed"
                                >
                                    <x-slot:before>
                                        <x-ui.icon name="solid.auto_fix_high" class="size-5" />
                                    </x-slot>
                                </x-ui.form.button>

                                <x-ui.form.button
                                    aria-label="{{__('core::install.steps.permissions.table.refresh_permissions', ['folder' => $permission['folder']])}}"
                                    color="gray"
                                    size="xs"
                                    wire:click="checkPermissions"
                                    wire:loading.attr="disabled"
                                    wire:target="checkPermissions"
                                    wire:loading.class="opacity-50 cursor-not-allowed"
                                >
                                    <x-slot:before>
                                        <x-ui.icon name="solid.refresh" class="size-5" />
                                    </x-slot>
                                </x-ui.form.button>
                                <x-ui.icon name="solid.x" class="size-4 text-red-500 dark:text-red-400" />
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
