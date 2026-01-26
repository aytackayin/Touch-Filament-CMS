<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Profile Header --}}
        <div class="overflow-hidden bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-white/10">
            <div class="h-32 bg-gradient-to-r from-primary-500 to-primary-600 dark:from-primary-600 dark:to-primary-700"></div>
            <div class="px-6 pb-6 mt-[-4rem] flex flex-col md:flex-row items-end gap-6">
                <div class="relative group">
                    <img 
                        src="{{ $record->avatar_url ? Storage::disk('attachments')->url($record->avatar_url) : 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=random' }}" 
                        alt="{{ $record->name }}"
                        class="w-32 h-32 rounded-2xl object-cover border-4 border-white shadow-lg dark:border-gray-900 group-hover:scale-[1.02] transition-transform duration-300"
                    >
                </div>
                <div class="flex-1 pb-2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $record->name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">{{ $record->email }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($record->roles as $role)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-100 text-primary-800 dark:bg-primary-500/10 dark:text-primary-400 border border-primary-200 dark:border-primary-500/20">
                                {{ $role->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    {{ $this->editAction }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Contact Information --}}
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-white/10">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">{{ __('user.label.personal_info') }}</h3>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 text-gray-400 dark:text-gray-500">
                                <x-heroicon-o-phone class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('user.label.phone') }}</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $record->phone ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 text-gray-400 dark:text-gray-500">
                                <x-heroicon-o-map-pin class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('user.label.address') }}</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white whitespace-pre-line">{{ $record->address ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Social Links --}}
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-white/10">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">{{ __('user.label.social_media') }}</h3>
                    <div class="grid grid-cols-1 gap-3">
                        @forelse($record->social_links ?? [] as $link)
                            <a href="{{ str_starts_with($link['url'], 'http') ? $link['url'] : 'https://' . $link['url'] }}" 
                               target="_blank"
                               class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:bg-gray-50 transition-colors dark:border-white/5 dark:hover:bg-white/5 group"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="text-primary-500">
                                        @switch($link['platform'])
                                            @case('instagram') <x-heroicon-o-camera class="w-5 h-5" /> @break
                                            @case('twitter') <x-heroicon-o-chat-bubble-left class="w-5 h-5" /> @break
                                            @case('linkedin') <x-heroicon-o-briefcase class="w-5 h-5" /> @break
                                            @case('github') <x-heroicon-o-code-bracket class="w-5 h-5" /> @break
                                            @default <x-heroicon-o-link class="w-5 h-5" />
                                        @endswitch
                                    </div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ $link['platform'] }}</span>
                                </div>
                                <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4 text-gray-400 group-hover:text-primary-500 transition-colors" />
                            </a>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">{{ __('user.label.social_links') }} bulunamadı.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Main Content / Meta Info --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white p-8 rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-white/10">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">{{ __('user.label.preferences') }}</h3>
                            <div class="space-y-4">
                                <div class="bg-gray-50 px-4 py-3 rounded-lg dark:bg-white/5">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">{{ __('user.label.default_editor') }}</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white uppercase">{{ $record->default_editor ?? 'richtext' }}</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Sistem Bilgileri</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">{{ __('user.label.created_at') }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $record->created_at->translatedFormat('j F Y, H:i') }}</span>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">{{ __('user.label.updated_at') }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $record->updated_at->translatedFormat('j F Y, H:i') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
