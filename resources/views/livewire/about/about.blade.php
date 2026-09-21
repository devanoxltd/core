<div class="space-y-6">
    <x-ui.data.table>
        <x-slot:head>
            <x-ui.data.table.tr>
                <x-ui.data.table.th>@lang('core::app.about.system.title')</x-ui.data.table.th>
                <x-ui.data.table.th class="text-right"></x-ui.data.table.th>
            </x-ui.data.table.tr>
        </x-slot:head>
        <x-slot:body>
            <x-ui.data.table.tr>
                <x-ui.data.table.td class="">@lang('core::app.about.system.app_version')</x-ui.data.table.td>
                <x-ui.data.table.td class="text-right"> {{ config('app.version') }} </x-ui.data.table.td>
            </x-ui.data.table.tr>
            <x-ui.data.table.tr>
                <x-ui.data.table.td class="">@lang('core::app.about.system.php_version')</x-ui.data.table.td>
                <x-ui.data.table.td class="text-right"> {{ PHP_VERSION }} </x-ui.data.table.td>
            </x-ui.data.table.tr>
            <x-ui.data.table.tr>
                <x-ui.data.table.td class="">@lang('core::app.about.system.laravel_version')</x-ui.data.table.td>
                <x-ui.data.table.td class="text-right"> {{ app()->version() }} </x-ui.data.table.td>
            </x-ui.data.table.tr>
            <x-ui.data.table.tr>
                <x-ui.data.table.td class="">@lang('core::app.about.system.db_version')</x-ui.data.table.td>
                <x-ui.data.table.td class="text-right"> {{ $dbVersion }} </x-ui.data.table.td>
            </x-ui.data.table.tr>
        </x-slot:body>
    </x-ui.data.table>

    @if ($license)
        <x-ui.data.table>
            <x-slot:head>
                <x-ui.data.table.tr>
                    <x-ui.data.table.th>@lang('core::app.about.license.title')</x-ui.data.table.th>
                    <x-ui.data.table.th class="text-right"></x-ui.data.table.th>
                </x-ui.data.table.tr>
            </x-slot:head>
            <x-slot:body>
                <x-ui.data.table.tr>
                    <x-ui.data.table.td class="">@lang('core::app.about.license.key')</x-ui.data.table.td>
                    <x-ui.data.table.td class="flex justify-end text-right">
                        <div class="flex items-center" x-data="{ show: false }">
                            <div :class="{ 'blur-sm': ! show }">{{ $license->key }}</div>
                            <x-ui.form.button class="ml-2" @click="show = ! show" color="gray" size="xs">
                                <x-slot:before>
                                    <x-ui.icon name="solid.eye" class="inline size-4" x-show="! show" />
                                    <x-ui.icon name="solid.eye-slash" class="inline size-4" x-show="show" />
                                </x-slot:before>
                            </x-ui.form.button>
                            <x-ui.utilities.copy-to-clipboard
                                class="ml-2"
                                :text="$license->key"
                                size="xs"
                            ></x-ui.utilities.copy-to-clipboard>
                        </div>
                    </x-ui.data.table.td>
                </x-ui.data.table.tr>
                <x-ui.data.table.tr>
                    <x-ui.data.table.td class="">@lang('core::app.about.license.purchase_code')</x-ui.data.table.td>
                    <x-ui.data.table.td class="flex justify-end text-right">
                        <div class="flex items-center" x-data="{ show: false }">
                            <div :class="{ 'blur-sm': ! show }">{{ $license->purchase_code }}</div>
                            <x-ui.form.button class="ml-2" @click="show = ! show" color="gray" size="xs">
                                <x-slot:before>
                                    <x-ui.icon name="solid.eye" class="inline size-4" x-show="! show" />
                                    <x-ui.icon name="solid.eye-slash" class="inline size-4" x-show="show" />
                                </x-slot:before>
                            </x-ui.form.button>
                            <x-ui.utilities.copy-to-clipboard
                                class="ml-2"
                                :text="$license->purchase_code"
                                size="xs"
                            ></x-ui.utilities.copy-to-clipboard>
                        </div>
                    </x-ui.data.table.td>
                </x-ui.data.table.tr>
                <x-ui.data.table.tr>
                    <x-ui.data.table.td class="">@lang('core::app.about.license.purchase_at')</x-ui.data.table.td>
                    <x-ui.data.table.td class="text-right">
                        @if ($license->purchase_at)
                            {{ $license->purchase_at->format('Y-m-d') }}
                            ({{ $license->purchase_at->diffForHumans() }})
                        @endif
                    </x-ui.data.table.td>
                </x-ui.data.table.tr>
                <x-ui.data.table.tr>
                    <x-ui.data.table.td class="">@lang('core::app.about.license.support_until')</x-ui.data.table.td>
                    <x-ui.data.table.td class="text-right">
                        @if ($license->support_until)
                            {{ $license->support_until->format('Y-m-d') }}
                            ({{ $license->support_until->diffForHumans() }})
                            @if ($license->support_until->isPast())
                                <span class="text-red-500">@lang('core::app.about.license.expired')</span>
                            @endif
                        @endif
                    </x-ui.data.table.td>
                </x-ui.data.table.tr>
                <x-ui.data.table.tr>
                    <x-ui.data.table.td class="">@lang('core::app.about.license.type')</x-ui.data.table.td>
                    <x-ui.data.table.td class="text-right"> {{ $license->type }} </x-ui.data.table.td>
                </x-ui.data.table.tr>
            </x-slot:body>
        </x-ui.data.table>
    @endif
</div>
