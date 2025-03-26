<div class="mx-2">
    {{-- Table --}}
    <div class="overflow-hidden rounded-lg border border-gray-300 dark:border-gray-600 mt-2">
        <div class="overflow-x-hidden max-h-64 overflow-y-auto snap-y snap-mandatory">
            <table class="w-full table-auto text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                    <tr>
                        <th scope="col" class="pl-2 py-2 font-medium text-gray-900 dark:text-white">
                            @lang('core::install.steps.requirements.table.name')
                        </th>
                        <th scope="col" class="pr-2 py-2 font-medium text-gray-900 dark:text-white text-right">
                            @lang('core::install.steps.requirements.table.status')
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requirements as $requirement)
                        <tr class="border-b bg-white dark:border-gray-600 dark:bg-gray-800 snap-end">
                            <td class="whitespace-nowrap pl-2 py-2 font-medium text-gray-900 dark:text-white">
                                {{ $requirement['name'] }}
                            </td>
                            <td class="whitespace-nowrap pr-2 py-2 font-medium text-gray-900 dark:text-white text-right flex items-center justify-end">
                                @if ($requirement['status'])
                                    <x-icon name="solid.check" class="text-green dark:text-green-400 size-4" />
                                @else
                                    <x-form.button
                                        wire:click="checkRequirements"
                                        wire:loading.attr="disabled"
                                        wire:target="checkRequirements"
                                        wire:loading.class="opacity-50 cursor-not-allowed"
                                        class="mr-1 text-xs text-white p-0 size-6 flex items-center"
                                    >
                                            <x-icon name="solid.refresh" class="size-5" />
                                    </x-form.button>
                                    <x-icon name="solid.x" class="text-red dark:text-red-400 size-4" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
