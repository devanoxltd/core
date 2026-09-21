<div class="mx-6 mt-2">
    <x-ui.data.table>
        <x-slot name="tableWrapper" class="max-h-64 overflow-y-auto"></x-slot>
        <x-slot name="head" class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
            <x-ui.data.table.tr>
                <x-ui.data.table.th>
                    @lang('core::install.steps.requirements.table.name')
                </x-ui.data.table.th>
                <x-ui.data.table.th class="text-right">
                    @lang('core::install.steps.requirements.table.status')
                </x-ui.data.table.th>
            </x-ui.data.table.tr>
        </x-slot>
        <x-slot name="body">
            @foreach ($requirements as $requirement)
            <x-ui.data.table.tr wire:key="requirement-{{ md5($requirement['name']) }}">
                <x-ui.data.table.td class="font-medium"> {{ $requirement['name'] }} </x-ui.data.table.td>
                <x-ui.data.table.td>
                    <div class="flex items-center justify-end">
                        @if ($requirement['status'])
                            <x-ui.icon name="solid.check" class="size-4 text-green-500 dark:text-green-400" />
                        @else
                            <x-ui.form.button
                                aria-label="{{ __('core::install.steps.requirements.table.refresh_requirements', ['name' => $requirement['name']]) }}"
                                class="mr-1"
                                color="gray"
                                size="xs"
                                wire:click="checkRequirements"
                                wire:loading.attr="disabled"
                                wire:target="checkRequirements"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                            >
                                <x-slot:before>
                                    <x-ui.icon name="solid.refresh" class="size-5" />
        </x-slot>
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
