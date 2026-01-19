<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @php
        $options = $getOptions();
    @endphp

    <div x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}'),
            search: '',
            open: false,
            options: @js($options),
            
            get filteredOptions() {
                if (!this.search) return this.options;
                const lowerSearch = this.search.toLowerCase();
                
                // Return filtered object
                const filtered = {};
                for (const [key, value] of Object.entries(this.options)) {
                    // Try to search in the hidden span if it exists, roughly
                    if (value.toLowerCase().includes(lowerSearch) || key.includes(lowerSearch)) {
                        filtered[key] = value;
                    }
                }
                return filtered;
            },
            
            get selectedLabel() {
                return this.options[this.state] || null;
            }
        }" class="relative select-icon-container" @click.outside="open = false">
        <!-- Custom CSS for this component -->
        <style>
            .select-icon-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(44px, 1fr));
                gap: 8px;
                padding: 50px 30px 8px 30px;
                max-height: 350px;
                overflow-y: auto;
                overflow-x: hidden;
            }

            .select-icon-item {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 44px;
                height: 44px;
                border: 1px solid transparent;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.2s;
            }

            .select-icon-tooltip {
                visibility: hidden;
                position: absolute;
                bottom: 65%;
                left: 50%;
                transform: translateX(-50%) translateY(5px);
                background-color: #111827;
                color: white;
                padding: 5px 12px;
                border-radius: 6px;
                font-size: 11px;
                font-weight: 500;
                white-space: normal !important;
                text-align: center;
                width: 100px !important;
                max-width: none !important;
                height: auto !important;
                z-index: 100;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.2s, transform 0.2s;
            }

            .dark .select-icon-tooltip {
                background-color: #252a35;
                color: #f3f4f6;
            }

            .select-icon-item:hover .select-icon-tooltip {
                visibility: visible;
                opacity: 1;
                transform: translateX(-50%) translateY(-5px);
            }

            .select-icon-item:hover {
                background-color: rgba(var(--primary-500), 0.1);
                border-color: rgba(var(--primary-500), 0.5);
            }

            .select-icon-item.selected {
                background-color: rgba(var(--primary-500), 0.2);
                border-color: rgb(var(--primary-500));
            }

            .select-icon-item span {
                display: none !important;
            }

            /* Styling the inner content passed by user */
            .select-icon-item>div {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                height: 100%;
            }
        </style>

        <!-- Trigger Button -->
        <button type="button" @click="open = !open" class="fi-btn fi-size-md" style="margin-bottom: 10px;">
            <span class="flex items-center gap-2 truncate min-h-[24px]">
                <template x-if="selectedLabel">
                    <div class="flex items-center" x-html="selectedLabel"></div>
                </template>
                <template x-if="!selectedLabel">
                    <span class="text-gray-400">{{ __('Select an icon') }}</span>
                </template>
            </span>
        </button>


        <!-- Dropdown Panel -->
        <div x-show="open" x-transition style="display: none;"
            class="absolute z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <!-- Search Input -->
            <div
                class="p-2 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10 rounded-t-lg">
                <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                    <x-filament::input type="text" x-model="search" :placeholder="__('Search icons...')"
                        @keydown.escape="open = false" autocomplete="off" />
                </x-filament::input.wrapper>
            </div>

            <!-- Grid Options -->
            <div class="select-icon-grid" x-show="Object.keys(filteredOptions).length > 0">
                <template x-for="[value, label] in Object.entries(filteredOptions)" :key="value">
                    <div @click="state = value; open = false;" class="select-icon-item"
                        :class="{'selected': state === value}">
                        <!-- Custom Tooltip -->
                        <div class="select-icon-tooltip"
                            x-text="value.replace('heroicon-o-', '').replace('heroicon-m-', '').replace('heroicon-s-', '').split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')">
                        </div>

                        <!-- Render the HTML provided in options -->
                        <div x-html="label"></div>
                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="Object.keys(filteredOptions).length === 0" class="py-12 text-center"
                style="text-align: center;padding-top: 10px;">
                <span class="text-sm text-gray-400 dark:text-gray-500" style="opacity: 0.4; font-style: italic;">
                    {{ __('No icons found.') }}
                </span>
            </div>
        </div>
    </div>
</x-dynamic-component>