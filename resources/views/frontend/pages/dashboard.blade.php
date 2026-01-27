<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-3xl text-slate-900 dark:text-white leading-tight tracking-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-card overflow-hidden shadow-xl sm:rounded-3xl">
                <div class="p-8 text-slate-900 dark:text-gray-100">
                    <h3 class="text-xl font-semibold mb-4">{{ __("Welcome back!") }}</h3>
                    <p class="text-slate-600 dark:text-slate-300">{{ __("You're logged in!") }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>