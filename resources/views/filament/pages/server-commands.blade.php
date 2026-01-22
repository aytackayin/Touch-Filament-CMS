<x-filament-panels::page>
    <div
        style="display: grid !important; gap: 40px !important; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)) !important;">
        {{-- Önbellek Yönetimi --}}
        <div style="margin-bottom: 5px;">
            <x-filament::section :heading="__('server-commands.categories.cache_management')"
                icon="heroicon-o-archive-box">
                <x-slot name="description">
                    {{ __('server-commands.categories.cache_management_description') }}
                </x-slot>
                <div class="space-y-6">
                    @php
                        $cacheCommands = [
                            ['name' => 'Optimize Clear', 'cmd' => 'php artisan optimize:clear', 'desc' => __('server-commands.commands.optimize_clear_desc')],
                            ['name' => 'Cache Clear', 'cmd' => 'php artisan cache:clear', 'desc' => __('server-commands.commands.cache_clear_desc')],
                            ['name' => 'Config Cache', 'cmd' => 'php artisan config:cache', 'desc' => __('server-commands.commands.config_cache_desc')],
                            ['name' => 'Route Cache', 'cmd' => 'php artisan route:cache', 'desc' => __('server-commands.commands.route_cache_desc')],
                            ['name' => 'View Clear', 'cmd' => 'php artisan view:clear', 'desc' => __('server-commands.commands.view_clear_desc')],
                        ];
                    @endphp

                    @foreach($cacheCommands as $item)
                        <div class="flex flex-col">
                            <span style="font-size: 13px; font-weight: 700;"
                                class="text-gray-950 dark:text-white">{{ $item['name'] }}</span>

                            <div class="flex items-center gap-2 mt-0.5 relative" x-data="{ 
                                                copyText: '{{ $item['cmd'] }}',
                                                copied: false,
                                                copy() {
                                                    if (!navigator.clipboard) return;
                                                    navigator.clipboard.writeText(this.copyText).then(() => {
                                                        this.copied = true;
                                                        setTimeout(() => this.copied = false, 2000);
                                                    });
                                                }
                                            }">
                                <span
                                    style="font-size: 12px; color: #26b5dc; font-family: monospace; font-weight: 600;">{{ $item['cmd'] }}</span>
                                <button @click="copy()" type="button"
                                    class="group flex items-center justify-center p-1 rounded-md bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-danger-500 transition-all active:scale-95 hover:!opacity-100"
                                    style="width: 24px; height: 24px; opacity: 0.4; margin-left: 2px; position:absolute"
                                    x-tooltip="{
                                                        content: '{{ __('server-commands.actions.copy_to_clipboard') }}',
                                                        theme: $store.theme,
                                                    }">
                                    <x-filament::icon icon="heroicon-o-clipboard" x-show="!copied"
                                        class="w-3.5 h-3.5 text-gray-400 group-hover:text-danger-500" />
                                    <x-filament::icon icon="heroicon-o-check" x-show="copied" style="display: none;"
                                        class="w-3.5 h-3.5 text-success-600" />
                                </button>
                            </div>

                            <span style="font-size: 12px; color: #565e6aff; line-height: 1.4; opacity: 0.8;" class="mt-0.5">
                                {{ $item['desc'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        {{-- Uygulama & Sistem --}}
        <div style="margin-bottom: 5px;">
            <x-filament::section :heading="__('server-commands.categories.app_system')" icon="heroicon-o-cpu-chip">
                <x-slot name="description">
                    {{ __('server-commands.categories.app_system_description') }}
                </x-slot>
                <div class="space-y-6">
                    @php
                        $appCommands = [
                            ['name' => 'Storage Link', 'cmd' => 'php artisan storage:link', 'desc' => __('server-commands.commands.storage_link_desc')],
                            ['name' => 'Maintenance Mode (Down)', 'cmd' => 'php artisan down', 'desc' => __('server-commands.commands.down_desc')],
                            ['name' => 'Maintenance Mode (Up)', 'cmd' => 'php artisan up', 'desc' => __('server-commands.commands.up_desc')],
                            ['name' => 'About', 'cmd' => 'php artisan about', 'desc' => __('server-commands.commands.about_desc')],
                        ];
                    @endphp

                    @foreach($appCommands as $item)
                        <div class="flex flex-col">
                            <span style="font-size: 13px; font-weight: 700;"
                                class="text-gray-950 dark:text-white">{{ $item['name'] }}</span>

                            <div class="flex items-center gap-2 mt-0.5 relative" x-data="{ 
                                                copyText: '{{ $item['cmd'] }}',
                                                copied: false,
                                                copy() {
                                                    if (!navigator.clipboard) return;
                                                    navigator.clipboard.writeText(this.copyText).then(() => {
                                                        this.copied = true;
                                                        setTimeout(() => this.copied = false, 2000);
                                                    });
                                                }
                                            }">
                                <span
                                    style="font-size: 12px; color: #26b5dc; font-family: monospace; font-weight: 600;">{{ $item['cmd'] }}</span>
                                <button @click="copy()" type="button"
                                    class="group flex items-center justify-center p-1 rounded-md bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-danger-500 transition-all active:scale-95 hover:!opacity-100"
                                    style="width: 24px; height: 24px; opacity: 0.4; margin-left: 2px; position:absolute"
                                    x-tooltip="{
                                                        content: '{{ __('server-commands.actions.copy_to_clipboard') }}',
                                                        theme: $store.theme,
                                                    }">
                                    <x-filament::icon icon="heroicon-o-clipboard" x-show="!copied"
                                        class="w-3.5 h-3.5 text-gray-400 group-hover:text-danger-500" />
                                    <x-filament::icon icon="heroicon-o-check" x-show="copied" style="display: none;"
                                        class="w-3.5 h-3.5 text-success-600" />
                                </button>
                            </div>

                            <span style="font-size: 12px; color: #565e6aff; line-height: 1.4; opacity: 0.8;" class="mt-0.5">
                                {{ $item['desc'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        {{-- Veritabanı & Migrasyon --}}
        <div style="margin-bottom: 5px;">
            <x-filament::section :heading="__('server-commands.categories.db_migration')"
                icon="heroicon-o-circle-stack">
                <x-slot name="description">
                    {{ __('server-commands.categories.db_migration_description') }}
                </x-slot>
                <div class="space-y-6">
                    @php
                        $dbCommands = [
                            ['name' => 'Migrate', 'cmd' => 'php artisan migrate', 'desc' => __('server-commands.commands.migrate_desc')],
                            ['name' => 'Migrate Status', 'cmd' => 'php artisan migrate:status', 'desc' => __('server-commands.commands.migrate_status_desc')],
                            ['name' => 'DB Seed', 'cmd' => 'php artisan db:seed', 'desc' => __('server-commands.commands.db_seed_desc')],
                            ['name' => 'Migrate Rollback', 'cmd' => 'php artisan migrate:rollback', 'desc' => __('server-commands.commands.migrate_rollback_desc')],
                        ];
                    @endphp

                    @foreach($dbCommands as $item)
                        <div class="flex flex-col">
                            <span style="font-size: 13px; font-weight: 700;"
                                class="text-gray-950 dark:text-white">{{ $item['name'] }}</span>

                            <div class="flex items-center gap-2 mt-0.5 relative" x-data="{ 
                                                copyText: '{{ $item['cmd'] }}',
                                                copied: false,
                                                copy() {
                                                    if (!navigator.clipboard) return;
                                                    navigator.clipboard.writeText(this.copyText).then(() => {
                                                        this.copied = true;
                                                        setTimeout(() => this.copied = false, 2000);
                                                    });
                                                }
                                            }">
                                <span
                                    style="font-size: 12px; color: #26b5dc; font-family: monospace; font-weight: 600;">{{ $item['cmd'] }}</span>
                                <button @click="copy()" type="button"
                                    class="group flex items-center justify-center p-1 rounded-md bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-danger-500 transition-all active:scale-95 hover:!opacity-100"
                                    style="width: 24px; height: 24px; opacity: 0.4; margin-left: 2px; position:absolute"
                                    x-tooltip="{
                                                        content: '{{ __('server-commands.actions.copy_to_clipboard') }}',
                                                        theme: $store.theme,
                                                    }">
                                    <x-filament::icon icon="heroicon-o-clipboard" x-show="!copied"
                                        class="w-3.5 h-3.5 text-gray-400 group-hover:text-danger-500" />
                                    <x-filament::icon icon="heroicon-o-check" x-show="copied" style="display: none;"
                                        class="w-3.5 h-3.5 text-success-600" />
                                </button>
                            </div>

                            <span style="font-size: 12px; color: #565e6aff; line-height: 1.4; opacity: 0.8;" class="mt-0.5">
                                {{ $item['desc'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        {{-- Kuyruk & Zamanlayıcı --}}
        <div style="margin-bottom: 5px;">
            <x-filament::section :heading="__('server-commands.categories.queue_schedule')" icon="heroicon-o-clock">
                <x-slot name="description">
                    {{ __('server-commands.categories.queue_schedule_description') }}
                </x-slot>
                <div class="space-y-6">
                    @php
                        $queueCommands = [
                            ['name' => 'Queue Work', 'cmd' => 'php artisan queue:work', 'desc' => __('server-commands.commands.queue_work_desc')],
                            ['name' => 'Queue Restart', 'cmd' => 'php artisan queue:restart', 'desc' => __('server-commands.commands.queue_restart_desc')],
                            ['name' => 'Schedule Run', 'cmd' => 'php artisan schedule:run', 'desc' => __('server-commands.commands.schedule_run_desc')],
                            ['name' => 'Schedule List', 'cmd' => 'php artisan schedule:list', 'desc' => __('server-commands.commands.schedule_list_desc')],
                        ];
                    @endphp

                    @foreach($queueCommands as $item)
                        <div class="flex flex-col">
                            <span style="font-size: 13px; font-weight: 700;"
                                class="text-gray-950 dark:text-white">{{ $item['name'] }}</span>

                            <div class="flex items-center gap-2 mt-0.5 relative" x-data="{ 
                                                copyText: '{{ $item['cmd'] }}',
                                                copied: false,
                                                copy() {
                                                    if (!navigator.clipboard) return;
                                                    navigator.clipboard.writeText(this.copyText).then(() => {
                                                        this.copied = true;
                                                        setTimeout(() => this.copied = false, 2000);
                                                    });
                                                }
                                            }">
                                <span
                                    style="font-size: 12px; color: #26b5dc; font-family: monospace; font-weight: 600;">{{ $item['cmd'] }}</span>
                                <button @click="copy()" type="button"
                                    class="group flex items-center justify-center p-1 rounded-md bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-danger-500 transition-all active:scale-95 hover:!opacity-100"
                                    style="width: 24px; height: 24px; opacity: 0.4; margin-left: 2px; position:absolute"
                                    x-tooltip="{
                                                        content: '{{ __('server-commands.actions.copy_to_clipboard') }}',
                                                        theme: $store.theme,
                                                    }">
                                    <x-filament::icon icon="heroicon-o-clipboard" x-show="!copied"
                                        class="w-3.5 h-3.5 text-gray-400 group-hover:text-danger-500" />
                                    <x-filament::icon icon="heroicon-o-check" x-show="copied" style="display: none;"
                                        class="w-3.5 h-3.5 text-success-600" />
                                </button>
                            </div>

                            <span style="font-size: 12px; color: #565e6aff; line-height: 1.4; opacity: 0.8;" class="mt-0.5">
                                {{ $item['desc'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>