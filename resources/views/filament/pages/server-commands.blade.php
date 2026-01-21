<x-filament-panels::page>
    <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2">
        {{-- Önbellek Yönetimi --}}
        <x-filament::section :heading="__('server-commands.categories.cache_management')" icon="heroicon-o-archive-box">
            <x-slot name="description">
                {{ __('server-commands.categories.cache_management_description') }}
            </x-slot>
            <div class="space-y-4">
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-3">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">Optimize Clear</strong>
                        <code
                            class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan optimize:clear</code>
                    </div>
                    <span
                        class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.optimize_clear_desc') }}</span>
                </div>
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-3">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">Cache Clear</strong>
                        <code
                            class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan cache:clear</code>
                    </div>
                    <span
                        class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.cache_clear_desc') }}</span>
                </div>
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-3">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">Config Cache</strong>
                        <code
                            class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan config:cache</code>
                    </div>
                    <span
                        class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.config_cache_desc') }}</span>
                </div>
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-3">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">Route Cache</strong>
                        <code
                            class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan route:cache</code>
                    </div>
                    <span
                        class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.route_cache_desc') }}</span>
                </div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">View Clear</strong>
                        <code
                            class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan view:clear</code>
                    </div>
                    <span class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.view_clear_desc') }}</span>
                </div>
            </div>
        </x-filament::section>

        {{-- Uygulama & Sistem --}}
        <x-filament::section :heading="__('server-commands.categories.app_system')" icon="heroicon-o-cpu-chip">
            <x-slot name="description">
                {{ __('server-commands.categories.app_system_description') }}
            </x-slot>
            <div class="space-y-4">
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-3">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">Storage Link</strong>
                        <code
                            class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan storage:link</code>
                    </div>
                    <span
                        class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.storage_link_desc') }}</span>
                </div>
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-3">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">Maintenance Mode (Down)</strong>
                        <code class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan down</code>
                    </div>
                    <span class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.down_desc') }}</span>
                </div>
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-3">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">Maintenance Mode (Up)</strong>
                        <code class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan up</code>
                    </div>
                    <span class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.up_desc') }}</span>
                </div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">About</strong>
                        <code class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan about</code>
                    </div>
                    <span class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.about_desc') }}</span>
                </div>
            </div>
        </x-filament::section>

        {{-- Veritabanı & Migrasyon --}}
        <x-filament::section :heading="__('server-commands.categories.db_migration')" icon="heroicon-o-circle-stack">
            <x-slot name="description">
                {{ __('server-commands.categories.db_migration_description') }}
            </x-slot>
            <div class="space-y-4">
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-3">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">Migrate</strong>
                        <code class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan migrate</code>
                    </div>
                    <span class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.migrate_desc') }}</span>
                </div>
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-3">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">Migrate Status</strong>
                        <code
                            class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan migrate:status</code>
                    </div>
                    <span
                        class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.migrate_status_desc') }}</span>
                </div>
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-3">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">DB Seed</strong>
                        <code class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan db:seed</code>
                    </div>
                    <span class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.db_seed_desc') }}</span>
                </div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">Migrate Rollback</strong>
                        <code
                            class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan migrate:rollback</code>
                    </div>
                    <span
                        class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.migrate_rollback_desc') }}</span>
                </div>
            </div>
        </x-filament::section>

        {{-- Kuyruk & Zamanlayıcı --}}
        <x-filament::section :heading="__('server-commands.categories.queue_schedule')" icon="heroicon-o-clock">
            <x-slot name="description">
                {{ __('server-commands.categories.queue_schedule_description') }}
            </x-slot>
            <div class="space-y-4">
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-3">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">Queue Work</strong>
                        <code
                            class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan queue:work</code>
                    </div>
                    <span class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.queue_work_desc') }}</span>
                </div>
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-3">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">Queue Restart</strong>
                        <code
                            class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan queue:restart</code>
                    </div>
                    <span
                        class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.queue_restart_desc') }}</span>
                </div>
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-3">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">Schedule Run</strong>
                        <code
                            class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan schedule:run</code>
                    </div>
                    <span
                        class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.schedule_run_desc') }}</span>
                </div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-bold">Schedule List</strong>
                        <code
                            class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan schedule:list</code>
                    </div>
                    <span
                        class="text-xs text-gray-500 mt-1">{{ __('server-commands.commands.schedule_list_desc') }}</span>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>