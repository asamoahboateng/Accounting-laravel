<div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl shadow-2xl overflow-hidden">
    {{-- Header --}}
    <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-white">Finance Agent</h3>
                <p class="text-xs text-emerald-400">AI-Powered Analysis</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button
                wire:click="$toggle('showAnomalyPanel')"
                class="p-2 text-gray-400 hover:text-white transition relative"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                @if($this->openAnomalies->count() > 0)
                    <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                        {{ $this->openAnomalies->count() }}
                    </span>
                @endif
            </button>
            <button
                wire:click="runBooksCloseAnalysis"
                @disabled($isRunningAnalysis)
                class="px-3 py-1.5 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center gap-2"
            >
                @if($isRunningAnalysis)
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Analyzing...
                @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    Run Analysis
                @endif
            </button>
        </div>
    </div>

    {{-- Insights Cards --}}
    @if(count($this->financialInsights) > 0)
        <div class="px-6 py-4 border-b border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($this->financialInsights as $insight)
                    <div @class([
                        'p-3 rounded-lg flex items-start gap-3',
                        'bg-emerald-900/30 border border-emerald-800/50' => $insight['type'] === 'positive',
                        'bg-red-900/30 border border-red-800/50' => $insight['type'] === 'negative' || $insight['type'] === 'danger',
                        'bg-amber-900/30 border border-amber-800/50' => $insight['type'] === 'warning',
                        'bg-blue-900/30 border border-blue-800/50' => $insight['type'] === 'info',
                    ])>
                        <div @class([
                            'w-8 h-8 rounded-full flex items-center justify-center shrink-0',
                            'bg-emerald-600' => $insight['type'] === 'positive',
                            'bg-red-600' => $insight['type'] === 'negative' || $insight['type'] === 'danger',
                            'bg-amber-600' => $insight['type'] === 'warning',
                            'bg-blue-600' => $insight['type'] === 'info',
                        ])>
                            @if($insight['icon'] === 'trending-up')
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            @elseif($insight['icon'] === 'trending-down')
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                </svg>
                            @elseif($insight['icon'] === 'exclamation-circle')
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @elseif($insight['icon'] === 'calendar')
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            @endif
                        </div>
                        <p class="text-sm text-gray-300">{{ $insight['message'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Anomaly Panel --}}
    @if($showAnomalyPanel && $this->openAnomalies->count() > 0)
        <div class="px-6 py-4 border-b border-gray-700 bg-gray-800/50">
            <h4 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                Open Anomalies
            </h4>
            <div class="space-y-2 max-h-48 overflow-y-auto">
                @foreach($this->openAnomalies as $anomaly)
                    <div
                        wire:key="anomaly-{{ $anomaly->id }}"
                        @class([
                            'p-3 rounded-lg cursor-pointer transition',
                            'bg-red-900/20 border border-red-800/50 hover:bg-red-900/30' => $anomaly->severity === 'critical',
                            'bg-amber-900/20 border border-amber-800/50 hover:bg-amber-900/30' => $anomaly->severity === 'warning',
                            'bg-blue-900/20 border border-blue-800/50 hover:bg-blue-900/30' => $anomaly->severity === 'info',
                        ])
                        wire:click="$set('selectedAnomalyId', '{{ $anomaly->id }}')"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span @class([
                                        'px-2 py-0.5 text-xs font-medium rounded',
                                        'bg-red-600 text-white' => $anomaly->severity === 'critical',
                                        'bg-amber-600 text-white' => $anomaly->severity === 'warning',
                                        'bg-blue-600 text-white' => $anomaly->severity === 'info',
                                    ])>{{ ucfirst($anomaly->severity) }}</span>
                                    <span class="text-xs text-gray-400">{{ $anomaly->anomaly_code }}</span>
                                </div>
                                <p class="text-sm text-white mt-1">{{ $anomaly->title }}</p>
                                <p class="text-xs text-gray-400 mt-0.5 line-clamp-2">{{ $anomaly->description }}</p>
                            </div>
                            @if($selectedAnomalyId === $anomaly->id)
                                <div class="flex gap-1">
                                    <button
                                        wire:click.stop="resolveAnomaly('{{ $anomaly->id }}', 'resolve')"
                                        class="p-1.5 bg-emerald-600 text-white rounded hover:bg-emerald-700 transition"
                                        title="Resolve"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </button>
                                    <button
                                        wire:click.stop="resolveAnomaly('{{ $anomaly->id }}', 'dismiss')"
                                        class="p-1.5 bg-gray-600 text-white rounded hover:bg-gray-500 transition"
                                        title="Dismiss"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Chat Area --}}
    <div class="flex flex-col h-80">
        {{-- Messages --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            @foreach($chatMessages as $message)
                <div @class([
                    'flex',
                    'justify-end' => $message['role'] === 'user',
                    'justify-start' => $message['role'] === 'assistant',
                ])>
                    <div @class([
                        'max-w-[80%] rounded-2xl px-4 py-2',
                        'bg-emerald-600 text-white' => $message['role'] === 'user',
                        'bg-gray-700 text-gray-100' => $message['role'] === 'assistant',
                    ])>
                        <p class="text-sm whitespace-pre-line">{{ $message['content'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Input --}}
        <div class="p-4 border-t border-gray-700">
            <form wire:submit="sendChatMessage" class="flex gap-2">
                <input
                    type="text"
                    wire:model="chatInput"
                    placeholder="Ask about your finances..."
                    class="flex-1 bg-gray-700 border-gray-600 rounded-full px-4 py-2 text-white placeholder-gray-400 text-sm focus:ring-emerald-500 focus:border-emerald-500"
                >
                <button
                    type="submit"
                    class="p-2 bg-emerald-600 text-white rounded-full hover:bg-emerald-700 transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>
