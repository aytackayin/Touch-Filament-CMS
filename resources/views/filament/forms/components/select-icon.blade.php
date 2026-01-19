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
                padding: 8px;
                max-height: 300px;
                overflow-y: auto;
            }

            .select-icon-item {
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
        <button type="button" @click="open = !open"
            class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2 text-left shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
            <span class="flex items-center gap-2 truncate min-h-[24px]">
                <template x-if="selectedLabel">
                    <div class="flex items-center" x-html="selectedLabel"></div>
                </template>
                <template x-if="!selectedLabel">
                    <span class="text-gray-400">Select an icon</span>
                </template>
            </span>
        </button>


        <!-- Dropdown Panel -->
        <div x-show="open" x-transition style="display: none;"
            class="absolute z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <!-- Search Input -->
            <div
                class="p-2 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10 rounded-t-lg">
                <input type="text" x-model="search" placeholder="Search icons..."
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6 dark:bg-gray-700 dark:text-gray-200 dark:ring-gray-600"
                    @keydown.escape="open = false">
            </div>

            <!-- Grid Options -->
            <div class="select-icon-grid">
                <template x-for="[value, label] in Object.entries(filteredOptions)" :key="value">
                    <div @click="state = value; open = false;" class="select-icon-item"
                        :class="{'selected': state === value}" :title="value">
                        <!-- Render the HTML provided in options -->
                        <div x-html="label"></div>
                    </div>
                </template>

                <template x-if="Object.keys(filteredOptions).length === 0">
                    <div class="p-4 text-center text-sm text-gray-500 dark:text-gray-400 col-span-full">
                        No icons found.
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-dynamic-component>