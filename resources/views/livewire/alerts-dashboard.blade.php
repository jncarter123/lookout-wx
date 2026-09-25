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
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-100">NWS Alerts Dashboard</h1>
                <p class="text-sm text-gray-300">
                    Overview + recent alerts by update time.
                </p>
            </div>

            <div class="w-full sm:w-64">
                <label class="block text-sm font-medium text-gray-200">Time window</label>
                <select
                        class="mt-1 block w-full rounded-md border border-gray-800 bg-gray-900 text-gray-100 shadow-sm
                               focus:border-gray-600 focus:ring-gray-600"
                        wire:model.live="periodMinutes"
                >
                    @foreach($periodOptions as $minutes => $label)
                        <option value="{{ $minutes }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-gray-800 bg-gray-900 p-4">
                <div class="text-sm text-gray-300">Total alerts</div>
                <div class="mt-1 text-3xl font-semibold text-gray-100">{{ number_format($totalAlerts) }}</div>
            </div>

            <div class="rounded-lg border border-gray-800 bg-gray-900 p-4">
                <div class="text-sm text-gray-300">Updated in the last hour</div>
                <div class="mt-1 text-3xl font-semibold text-gray-100">{{ number_format($lastHourCount) }}</div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-800 bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-800 p-4">
                <div>
                    <div class="text-lg font-semibold text-gray-100">Recent alerts</div>
                    <div class="text-sm text-gray-300">
                        Showing alerts updated since <span class="font-medium text-gray-100">
                            <x-local-time :value="$cutoff" />
                        </span>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-800">
                    <thead class="bg-gray-900/60 text-left text-sm font-medium text-gray-200">
                    <tr>
                        <th class="px-4 py-3">Updated</th>
                        <th class="px-4 py-3">Event</th>
                        <th class="px-4 py-3">Severity</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Headline</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800 bg-gray-900 text-sm">
                    @forelse($alerts as $alert)
                        <tr
                                wire:key="alert-row-{{ $alert->id }}"
                                class="cursor-pointer hover:bg-gray-800/60"
                                wire:click="openAlert('{{ $alert->id }}')"
                        >
                            <td class="whitespace-nowrap px-4 py-3 text-gray-200">
                                <x-local-time :value="$alert->nws_updated_at" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-100">
                                {{ $alert->event ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-100">
                                {{ $alert->severity ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-100">
                                {{ $alert->status ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-100">
                                {{ $alert->headline ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-gray-300" colspan="5">
                                No alerts found for this time window.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-800 p-4">
                {{ $alerts->links() }}
            </div>
        </div>

        @if($showAlertModal && $selectedAlert)
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
                                    <div class="break-words text-lg font-semibold leading-tight text-gray-100">
                                        {{ $selectedAlert->event ?? 'Alert details' }}
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

                            <div class="mt-2 text-xs text-gray-400">
                                Tip: press <span class="font-medium text-gray-200">Esc</span> or click outside the modal to close.
                            </div>
                        </div>

                        <div class="max-h-[70vh] space-y-4 overflow-y-auto p-4">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Updated</div>
                                    <div class="mt-1 text-sm text-gray-100">
                                        {{ optional($selectedAlert->nws_updated_at)->toDayDateTimeString() ?? '—' }}
                                    </div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Status</div>
                                    <div class="mt-1 text-sm text-gray-100">{{ $selectedAlert->status ?? '—' }}</div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Severity</div>
                                    <div class="mt-1 text-sm text-gray-100">{{ $selectedAlert->severity ?? '—' }}</div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Message type</div>
                                    <div class="mt-1 text-sm text-gray-100">{{ $selectedAlert->message_type ?? '—' }}</div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Certainty</div>
                                    <div class="mt-1 text-sm text-gray-100">{{ $selectedAlert->certainty ?? '—' }}</div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Urgency</div>
                                    <div class="mt-1 text-sm text-gray-100">{{ $selectedAlert->urgency ?? '—' }}</div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Sent</div>
                                    <div class="mt-1 text-sm text-gray-100">
                                        {{ optional($selectedAlert->sent)->toDayDateTimeString() ?? '—' }}
                                    </div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Effective</div>
                                    <div class="mt-1 text-sm text-gray-100">
                                        {{ optional($selectedAlert->effective)->toDayDateTimeString() ?? '—' }}
                                    </div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Onset</div>
                                    <div class="mt-1 text-sm text-gray-100">
                                        {{ optional($selectedAlert->onset)->toDayDateTimeString() ?? '—' }}
                                    </div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Expires</div>
                                    <div class="mt-1 text-sm text-gray-100">
                                        {{ optional($selectedAlert->expires)->toDayDateTimeString() ?? '—' }}
                                    </div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Ends</div>
                                    <div class="mt-1 text-sm text-gray-100">
                                        {{ optional($selectedAlert->ends)->toDayDateTimeString() ?? '—' }}
                                    </div>
                                </div>

                                <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                    <div class="text-xs font-medium text-gray-400">Category</div>
                                    <div class="mt-1 text-sm text-gray-100">{{ $selectedAlert->category ?? '—' }}</div>
                                </div>
                            </div>

                            <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                <div class="text-xs font-medium text-gray-400">Counties</div>
                                <div class="mt-2 text-sm text-gray-100">
                                    @if($selectedAlert->counties->isEmpty())
                                        <span class="text-gray-300">—</span>
                                    @else
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($selectedAlert->counties as $county)
                                                <span class="rounded-full border border-gray-800 bg-gray-800/60 px-2.5 py-1 text-xs text-gray-100">
                                                    {{ $county->name ?? 'County' }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="rounded-md border border-gray-800 bg-gray-950/40 p-3">
                                <div class="text-xs font-medium text-gray-400">Raw</div>
                                <pre class="mt-2 overflow-x-auto rounded bg-black/30 p-3 text-xs text-gray-100">{{ json_encode($selectedAlert->raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
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