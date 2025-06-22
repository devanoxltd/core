<div>
    <x-data.table>
        <x-slot:head>
            <x-data.table.tr>
                <x-data.table.th>@lang('core::app.about.system.title')</x-data.table.th>
                <x-data.table.th class="text-right"></x-data.table.th>
            </x-data.table.tr>
        </x-slot>
        <x-slot:body>
            <x-data.table.tr>
                <x-data.table.td class="">@lang('core::app.about.system.app_version')</x-data.table.td>
                <x-data.table.td class="text-right">
                    {{ config('app.version') }}
                </x-data.table.td>
            </x-data.table.tr>
            <x-data.table.tr>
                <x-data.table.td class="">@lang('core::app.about.system.php_version')</x-data.table.td>
                <x-data.table.td class="text-right">
                    {{ PHP_VERSION }}
                </x-data.table.td>
            </x-data.table.tr>
            <x-data.table.tr>
                <x-data.table.td class="">@lang('core::app.about.system.laravel_version')</x-data.table.td>
                <x-data.table.td class="text-right">
                    {{ Illuminate\Foundation\Application::VERSION }}
                </x-data.table.td>
            </x-data.table.tr>
            <x-data.table.tr>
                <x-data.table.td class="">@lang('core::app.about.system.db_version')</x-data.table.td>
                <x-data.table.td class="text-right">
                    {{ $dbVersion }}
                </x-data.table.td>
            </x-data.table.tr>
        </x-slot>
    </x-data.table>

    @if ($license)
        <x-data.table>
            <x-slot:head>
                <x-data.table.tr>
                    <x-data.table.th>@lang('core::app.about.license.title')</x-data.table.th>
                    <x-data.table.th class="text-right"></x-data.table.th>
                </x-data.table.tr>
            </x-slot>
            <x-slot:body>
                <x-data.table.tr>
                    <x-data.table.td class="">@lang('core::app.about.license.key')</x-data.table.td>
                    <x-data.table.td class="flex justify-end text-right">
                        <div class="flex items-center" x-data="{ show: false }">
                            <div :class="{'blur-sm': !show}">
                                {{ $license->key }}
                            </div>
                            <x-form.button.simple class="ml-2 flex items-center px-1" @click="show = !show">
                                <x-icon name="solid.eye" class="inline size-4" x-show="!show" />
                                <x-icon name="solid.eye-slash" class="inline size-4" x-show="show" />
                            </x-form.button.simple>
                            <x-utilities.copy-to-clipboard
                                class="ml-2"
                                :text="$license->key"
                            ></x-utilities.copy-to-clipboard>
                        </div>
                    </x-data.table.td>
                </x-data.table.tr>
                <x-data.table.tr>
                    <x-data.table.td class="">@lang('core::app.about.license.purchase_at')</x-data.table.td>
                    <x-data.table.td class="flex justify-end text-right">
                        <div class="flex items-center" x-data="{ show: false }">
                            <div :class="{'blur-sm': !show}">
                                {{ $license->purchase_code }}
                            </div>
                            <x-form.button.simple class="ml-2 flex items-center px-1" @click="show = !show">
                                <x-icon name="solid.eye" class="inline size-4" x-show="!show" />
                                <x-icon name="solid.eye-slash" class="inline size-4" x-show="show" />
                            </x-form.button.simple>
                            <x-utilities.copy-to-clipboard
                                class="ml-2"
                                :text="$license->purchase_code"
                            ></x-utilities.copy-to-clipboard>
                        </div>
                    </x-data.table.td>
                </x-data.table.tr>
                <x-data.table.tr>
                    <x-data.table.td class="">@lang('core::app.about.license.purchase_at')</x-data.table.td>
                    <x-data.table.td class="text-right">
                        @if ($license->purchase_at)
                            {{ $license->purchase_at->format('Y-m-d') }}
                            ({{ $license->purchase_at->diffForHumans() }})
                        @endif
                    </x-data.table.td>
                </x-data.table.tr>
                <x-data.table.tr>
                    <x-data.table.td class="">@lang('core::app.about.license.support_until')</x-data.table.td>
                    <x-data.table.td class="text-right">
                        @if ($license->support_until)
                            {{ $license->support_until->format('Y-m-d') }}
                            ({{ $license->support_until->diffForHumans() }})
                            @if ($license->support_until->isPast())
                                <span class="text-red-500">@lang('core::app.about.license.expired')</span>
                            @endif
                        @endif
                    </x-data.table.td>
                </x-data.table.tr>
                <x-data.table.tr>
                    <x-data.table.td class="">@lang('core::app.about.license.type')</x-data.table.td>
                    <x-data.table.td class="text-right">
                        {{ $license->type }}
                    </x-data.table.td>
                </x-data.table.tr>
            </x-slot>
        </x-data.table>
    @endif
</div>
