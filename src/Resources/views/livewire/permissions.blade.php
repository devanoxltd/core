<div class="mx-2">
    {{-- Table --}}
    <div class="overflow-hidden rounded-lg border border-gray-300 dark:border-gray-600 mt-2">
        <div class="overflow-x-hidden max-h-64 overflow-y-auto snap-y snap-mandatory">
            <table class="w-full table-auto text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                    <tr>
                        <th scope="col" class="pl-2 py-2 font-medium text-gray-900 dark:text-white">
                            @lang('core::install.steps.permissions.table.name')
                        </th>
                        <th scope="col" class="pr-2 py-2 font-medium text-gray-900 dark:text-white text-right">
                            @lang('core::install.steps.permissions.table.permission')
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($permissions as $permission)
                        <tr class="border-b bg-white dark:border-gray-600 dark:bg-gray-800 snap-end">
                            <td class="whitespace-nowrap pl-2 py-2 font-medium text-gray-900 dark:text-white">
                                {{ $permission['folder'] }}
                            </td>
                            <td class="whitespace-nowrap pr-2 py-2 font-medium text-gray-900 dark:text-white text-right flex items-center justify-end">
                                @if ($permission['status'])
                                    <x-ui.icon name="solid.check" class="text-green-500 dark:text-green-400 size-4" />
                                @else
                                    <x-form.button
                                        wire:click="fixPermissions('{{ $permission['folder'] }}', '{{ $permission['permission'] }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="checkPermissions"
                                        wire:loading.class="opacity-50 cursor-not-allowed"
                                        class="mr-1 text-xs text-white p-0 size-6 flex items-center"
                                    >
                                            <x-ui.icon name="solid.auto_fix_high" class="size-5" />
                                    </x-form.button>
                                    <x-form.button
                                        wire:click="checkPermissions"
                                        wire:loading.attr="disabled"
                                        wire:target="checkPermissions"
                                        wire:loading.class="opacity-50 cursor-not-allowed"
                                        class="mr-1 text-xs text-white p-0 size-6 flex items-center"
                                    >
                                            <x-ui.icon name="solid.refresh" class="size-5" />
                                    </x-form.button>
                                    <x-ui.icon name="solid.x" class="text-red-500 dark:text-red-400 size-4" />
                                @endif

                                    <span class="ml-1">
                                        {{ $permission['permission'] }}
                                    </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
