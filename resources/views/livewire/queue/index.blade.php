<div wire:poll.10000ms>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Queue Monitor</h1>
        <a href="/horizon" target="_blank"
           class="text-sm text-gray-500 hover:text-gray-700 font-medium">
            Horizon Dashboard &rarr;
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pending</p>
            <p class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($pending) }}</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 {{ $failedCount > 0 ? 'border-red-300 bg-red-50' : '' }}">
            <p class="text-xs font-medium {{ $failedCount > 0 ? 'text-red-600' : 'text-gray-500' }} uppercase tracking-wide">Failed</p>
            <p class="mt-1 text-3xl font-semibold {{ $failedCount > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ number_format($failedCount) }}</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Workers</p>
            <p class="mt-1 text-3xl font-semibold text-gray-900">
                {{ $processes !== null ? number_format($processes) : '—' }}
            </p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Horizon</p>
            <div class="mt-2 flex items-center gap-2">
                @if($status === 'running')
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-green-500"></span>
                    <span class="text-sm font-medium text-green-700">Running</span>
                @elseif($status === 'inactive')
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-yellow-500"></span>
                    <span class="text-sm font-medium text-yellow-700">Inactive</span>
                @else
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                    <span class="text-sm font-medium text-gray-500">Unknown</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Failed Jobs --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Failed Jobs</h2>
            @if($failedCount > 0)
                <button
                    wire:click="retryAll"
                    wire:confirm="Retry all {{ $failedCount }} failed job(s)?"
                    class="text-xs font-medium text-blue-600 hover:text-blue-800"
                >
                    Retry All
                </button>
            @endif
        </div>

        @if($failedJobs->isEmpty())
            <div class="px-6 py-12 text-center">
                <p class="text-sm text-gray-500">No failed jobs. All clear.</p>
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Queue</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Failed At</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exception</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($failedJobs as $job)
                        <tr x-data="{ open: false }" class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700"
                                      title="{{ $job->full_name }}">
                                    {{ $job->display_name }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $job->queue }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                <span title="{{ $job->failed_at->toDateTimeString() }}">
                                    {{ $job->failed_at->diffForHumans() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 max-w-sm">
                                <button @click="open = !open"
                                        class="text-left w-full">
                                    <span class="block truncate text-red-600 text-xs font-mono hover:text-red-800">
                                        {{ $job->exception_msg }}
                                    </span>
                                </button>
                                <div x-show="open" x-cloak class="mt-2">
                                    <pre class="text-xs font-mono bg-gray-900 text-gray-100 rounded p-3 overflow-x-auto whitespace-pre-wrap max-h-64 overflow-y-auto">{{ $job->exception }}</pre>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                <div class="flex items-center justify-end gap-3">
                                    <button
                                        wire:click="retryJob('{{ $job->uuid }}')"
                                        class="text-blue-600 hover:text-blue-800 text-xs font-medium"
                                    >
                                        Retry
                                    </button>
                                    <button
                                        wire:click="deleteJob('{{ $job->uuid }}')"
                                        wire:confirm="Delete this failed job record?"
                                        class="text-red-500 hover:text-red-700 text-xs font-medium"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($failedCount > 100)
                <div class="px-5 py-3 border-t border-gray-200 text-xs text-gray-500 text-right">
                    Showing 100 of {{ number_format($failedCount) }} failed jobs.
                    <a href="/horizon/failed" target="_blank" class="text-blue-600 hover:underline">View all in Horizon &rarr;</a>
                </div>
            @endif
        @endif
    </div>
</div>
