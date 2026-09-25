<div class="min-h-[calc(100vh-4rem)] bg-gray-950 text-gray-100"
     {{-- Refresh on alert broadcasts, at most once per 3s: alert bursts would otherwise re-render every client per event. --}}
     x-data="{
        pending: null,
        handler: null,
        init() {
            this.handler = () => {
                if (this.pending) return;
                this.pending = setTimeout(() => { this.pending = null; this.$wire.$refresh(); }, 3000);
            };
            window.Echo?.channel('nws.alerts')
                .listen('.NwsAlert', this.handler)
                .listen('.NwsAlertsRemoved', this.handler);
        },
        destroy() {
            clearTimeout(this.pending);
            window.Echo?.channel('nws.alerts')
                .stopListening('.NwsAlert', this.handler)
                .stopListening('.NwsAlertsRemoved', this.handler);
        },
     }">
    <div class="max-w-7xl mx-auto px-4 py-8 space-y-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-100">Active NWS Alerts</h1>
                <p class="text-sm text-gray-300">
                    Currently-active alerts that have not yet expired.
                </p>
            </div>

            <div class="flex items-end gap-2">
                <div class="w-56">
                    <label for="marineArea" class="block text-xs font-medium text-gray-300">
                        Marine area (optional)
                    </label>
                    <select
                            id="marineArea"
                            wire:key="marine-area-select-{{ $marineArea ?? 'all' }}"
                            wire:model.live="marineArea"
                            class="mt-1 w-full rounded-md border border-gray-800 bg-gray-950/40 px-3 py-2 text-sm text-gray-100 focus:border-gray-600 focus:ring-2 focus:ring-gray-600"
                    >
                        <option value="">All areas</option>

                        @foreach($marineAreaOptions as $opt)
                            <option value="{{ $opt['code'] }}">
                                {{ $opt['code'] }} — {{ $opt['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if(!empty($marineArea))
                    <button
                            type="button"
                            wire:click="clearMarineArea"
                            class="h-10 rounded-md border border-gray-800 bg-gray-900 px-3 text-sm text-gray-200 hover:bg-gray-800/60"
                    >
                        Clear
                    </button>
                @endif
            </div>
        </div>

        <div class="rounded-lg border border-gray-800 bg-gray-900">

            <div class="p-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($alerts as $alert)
                        @php
                            $message =
                                data_get($alert->raw, 'properties.description')
                                ?? data_get($alert->raw, 'properties.instruction')
                                ?? $alert->headline
                                ?? $alert->event
                                ?? '—';

                            $severityKey = strtolower((string) ($alert->severity ?? ''));

                            $severityUi = match ($severityKey) {
                                'extreme' => [
                                    'dot' => 'bg-fuchsia-400',
                                    'badge' => 'border-fuchsia-500/40 bg-fuchsia-500/10 text-fuchsia-200',
                                    'card' => 'hover:border-fuchsia-500/40',
                                ],
                                'severe' => [
                                    'dot' => 'bg-red-400',
                                    'badge' => 'border-red-500/40 bg-red-500/10 text-red-200',
                                    'card' => 'hover:border-red-500/40',
                                ],
                                'moderate' => [
                                    'dot' => 'bg-amber-400',
                                    'badge' => 'border-amber-500/40 bg-amber-500/10 text-amber-200',
                                    'card' => 'hover:border-amber-500/40',
                                ],
                                'minor' => [
                                    'dot' => 'bg-sky-400',
                                    'badge' => 'border-sky-500/40 bg-sky-500/10 text-sky-200',
                                    'card' => 'hover:border-sky-500/40',
                                ],
                                default => [
                                    'dot' => 'bg-gray-400',
                                    'badge' => 'border-gray-800 bg-gray-900 text-gray-200',
                                    'card' => 'hover:border-gray-700',
                                ],
                            };
                        @endphp

                        <button
                                type="button"
                                wire:key="active-alert-card-{{ $alert->id }}"
                                wire:click="openAlert('{{ $alert->id }}')"
                                class="group text-left rounded-lg border border-gray-800 bg-gray-950/40 p-4 shadow-sm transition
                                   hover:bg-gray-800/40 focus:outline-none focus:ring-2 focus:ring-gray-600 {{ $severityUi['card'] }}"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-base font-semibold text-gray-100">
                                        {{ $alert->event ?? '—' }}
                                    </div>
                                    <div class="mt-1 text-xs text-gray-400">
                                        Updated:
                                        <span class="text-gray-200">
                                            <x-local-time :value="$alert->nws_updated_at" />
                                        </span>
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    <span class="inline-flex items-center gap-2 rounded-full border px-2.5 py-1 text-xs font-medium {{ $severityUi['badge'] }}">
                                        <span class="h-2 w-2 rounded-full {{ $severityUi['dot'] }}" aria-hidden="true"></span>
                                        {{ $alert->severity ?? '—' }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <div class="rounded-md border border-gray-800 bg-black/20 p-2.5">
                                    <div class="text-[11px] font-medium text-gray-400">Expires</div>
                                    <div class="mt-1 text-sm text-gray-100">
                                        <x-local-time :value="$alert->expires" />
                                    </div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-black/20 p-2.5">
                                    <div class="text-[11px] font-medium text-gray-400">Certainty</div>
                                    <div class="mt-1 text-sm text-gray-100">
                                        {{ $alert->certainty ?? '—' }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="text-[11px] font-medium text-gray-400">Message</div>
                                <div class="mt-1 text-sm text-gray-100 line-clamp-3 break-words">
                                    {{ $message }}
                                </div>
                            </div>

                            <div class="mt-3 text-xs text-gray-400">
                                Click for details
                            </div>
                        </button>
                    @empty
                        <div class="col-span-full rounded-lg border border-gray-800 bg-gray-950/40 p-6 text-center text-gray-300">
                            No active alerts right now.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="border-t border-gray-800 p-4">
                {{ $alerts->links() }}
            </div>
        </div>

        @if($showAlertModal && $selectedAlert)
            @php
                $selectedSeverityKey = strtolower((string) ($selectedAlert->severity ?? ''));

                $selectedSeverityUi = match ($selectedSeverityKey) {
                    'extreme' => [
                        'dot' => 'bg-fuchsia-400',
                        'badge' => 'border-fuchsia-500/40 bg-fuchsia-500/10 text-fuchsia-200',
                    ],
                    'severe' => [
                        'dot' => 'bg-red-400',
                        'badge' => 'border-red-500/40 bg-red-500/10 text-red-200',
                    ],
                    'moderate' => [
                        'dot' => 'bg-amber-400',
                        'badge' => 'border-amber-500/40 bg-amber-500/10 text-amber-200',
                    ],
                    'minor' => [
                        'dot' => 'bg-sky-400',
                        'badge' => 'border-sky-500/40 bg-sky-500/10 text-sky-200',
                    ],
                    default => [
                        'dot' => 'bg-gray-400',
                        'badge' => 'border-gray-800 bg-gray-900 text-gray-200',
                    ],
                };
            @endphp

            <div
                    class="fixed inset-0 z-50"
                    wire:keydown.escape.window="closeAlertModal"
                    role="dialog"
                    aria-modal="true"
            >
                <div class="absolute inset-0 bg-black/70" wire:click="closeAlertModal"></div>

                <div class="absolute inset-0 flex items-start justify-center p-4 sm:p-6">
                    <div class="mt-4 w-full max-w-3xl overflow-hidden rounded-lg border border-gray-800 bg-gray-900 shadow-2xl sm:mt-10">
                        <div class="border-b border-gray-800 p-4">
                            <div class="flex items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div class="break-words text-lg font-semibold leading-tight text-gray-100">
                                            {{ $selectedAlert->event ?? 'Alert details' }}
                                        </div>

                                        @if(!empty($selectedAlert->severity))
                                            <span class="inline-flex items-center gap-2 rounded-full border px-2.5 py-1 text-xs font-medium {{ $selectedSeverityUi['badge'] }}">
                                                <span class="h-2 w-2 rounded-full {{ $selectedSeverityUi['dot'] }}" aria-hidden="true"></span>
                                                {{ $selectedAlert->severity }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-1 break-words text-sm text-gray-300">
                                        {{ $selectedAlert->headline ?? '—' }}
                                    </div>
                                </div>

                                <button
                                        type="button"
                                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-gray-800
                                           text-gray-200 hover:bg-gray-800/60"
                                        wire:click="closeAlertModal"
                                        aria-label="Close modal"
                                        title="Close"
                                >
                                    <span class="text-lg leading-none">&times;</span>
                                </button>
                            </div>
                        </div>

                        <div class="max-h-[70vh] space-y-4 overflow-y-auto p-4">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Started (Onset)</div>
                                    <div class="mt-1 text-sm text-gray-100">
                                        {{ optional($selectedAlert->onset)->toDayDateTimeString() ?? '—' }}
                                    </div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Updated</div>
                                    <div class="mt-1 text-sm text-gray-100">
                                        {{ optional($selectedAlert->nws_updated_at)->toDayDateTimeString() ?? '—' }}
                                    </div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Expires</div>
                                    <div class="mt-1 text-sm text-gray-100">
                                        {{ optional($selectedAlert->expires)->toDayDateTimeString() ?? '—' }}
                                    </div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Category</div>
                                    <div class="mt-1 text-sm text-gray-100">{{ $selectedAlert->category ?? '—' }}</div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Severity</div>
                                    <div class="mt-1 text-sm text-gray-100">{{ $selectedAlert->severity ?? '—' }}</div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Certainty</div>
                                    <div class="mt-1 text-sm text-gray-100">{{ $selectedAlert->certainty ?? '—' }}</div>
                                </div>
                            </div>

                            <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                <div class="text-xs font-medium text-gray-400">Message</div>
                                <div class="mt-2 whitespace-pre-wrap text-sm text-gray-100">
                                    {{
                                        data_get($selectedAlert->raw, 'properties.description')
                                        ?? data_get($selectedAlert->raw, 'properties.instruction')
                                        ?? $selectedAlert->headline
                                        ?? '—'
                                    }}
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end border-t border-gray-800 p-4">
                            <button
                                    type="button"
                                    class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-white"
                                    wire:click="closeAlertModal"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>