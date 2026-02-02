<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-3">
            <x-filament::button type="submit" size="lg">
                Save Theme Settings
            </x-filament::button>
        </div>
    </form>

    <x-filament::section class="mt-8">
        <x-slot name="heading">Preview</x-slot>
        <x-slot name="description">See how your sidebar will look with current settings.</x-slot>

        <div class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
            <div
                class="w-64 h-80 flex flex-col"
                style="background-color: {{ $this->data['sidebar_bg'] ?? '#1e293b' }};"
            >
                {{-- Brand Area --}}
                <div
                    class="px-4 py-4 border-b"
                    style="background-color: {{ $this->data['sidebar_brand_bg'] ?? '#0f172a' }}; border-color: {{ $this->data['sidebar_border'] ?? '#334155' }};"
                >
                    <span style="color: {{ $this->data['sidebar_text'] ?? '#e2e8f0' }}; font-weight: 600;">
                        {{ $this->data['brand_name'] ?? 'QuickBooks Clone' }}
                    </span>
                </div>

                {{-- Navigation Items --}}
                <div class="flex-1 p-2 space-y-1">
                    {{-- Active Item --}}
                    <div
                        class="px-3 py-2 rounded-md flex items-center gap-2"
                        style="background-color: {{ $this->data['sidebar_active_bg'] ?? '#0f172a' }}; border-left: 3px solid {{ $this->data['sidebar_accent_color'] ?? '#10b981' }};"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="color: {{ $this->data['sidebar_accent_color'] ?? '#10b981' }};">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        <span style="color: #fff; font-weight: 500;">Dashboard</span>
                    </div>

                    {{-- Normal Items --}}
                    <div
                        class="px-3 py-2 rounded-md flex items-center gap-2 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="color: {{ $this->data['sidebar_text_muted'] ?? '#94a3b8' }};">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span style="color: {{ $this->data['sidebar_text'] ?? '#e2e8f0' }};">Sales</span>
                    </div>

                    <div
                        class="px-3 py-2 rounded-md flex items-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="color: {{ $this->data['sidebar_text_muted'] ?? '#94a3b8' }};">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                        </svg>
                        <span style="color: {{ $this->data['sidebar_text'] ?? '#e2e8f0' }};">Expenses</span>
                    </div>

                    {{-- Group Label --}}
                    <div class="px-3 py-2 mt-4">
                        <span style="color: {{ $this->data['sidebar_text_muted'] ?? '#94a3b8' }}; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            Reports
                        </span>
                    </div>

                    <div
                        class="px-3 py-2 rounded-md flex items-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="color: {{ $this->data['sidebar_text_muted'] ?? '#94a3b8' }};">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                        <span style="color: {{ $this->data['sidebar_text'] ?? '#e2e8f0' }};">Analytics</span>
                    </div>
                </div>

                {{-- Footer Area --}}
                <div
                    class="px-4 py-3 border-t"
                    style="background-color: {{ $this->data['sidebar_brand_bg'] ?? '#0f172a' }}; border-color: {{ $this->data['sidebar_border'] ?? '#334155' }};"
                >
                    <span style="color: {{ $this->data['sidebar_text_muted'] ?? '#94a3b8' }}; font-size: 0.875rem;">
                        User Menu
                    </span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
