<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen font-sans antialiased">

<nav class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <div class="flex items-center gap-6">
                <span class="font-semibold text-gray-900 text-lg">{{ config('app.name') }}</span>
                @can('alerts.read')
                    <a href="{{ route('admin.alerts') }}"
                       class="text-sm font-medium {{ request()->routeIs('admin.alerts*') ? 'text-blue-600 border-b-2 border-blue-600 pb-0.5' : 'text-gray-600 hover:text-gray-900' }}">
                        Alerts
                    </a>
                @endcan
                @can('users.read')
                    <a href="{{ route('admin.users') }}"
                       class="text-sm font-medium {{ request()->routeIs('admin.users') ? 'text-blue-600 border-b-2 border-blue-600 pb-0.5' : 'text-gray-600 hover:text-gray-900' }}">
                        Users
                    </a>
                @endcan
                @can('roles.read')
                    <a href="{{ route('admin.roles') }}"
                       class="text-sm font-medium {{ request()->routeIs('admin.roles') ? 'text-blue-600 border-b-2 border-blue-600 pb-0.5' : 'text-gray-600 hover:text-gray-900' }}">
                        Roles
                    </a>
                @endcan
                @if (auth()->user()->canAny(['tokens.manage', 'tokens.manage-own']))
                    <a href="{{ route('admin.tokens') }}"
                       class="text-sm font-medium {{ request()->routeIs('admin.tokens') ? 'text-blue-600 border-b-2 border-blue-600 pb-0.5' : 'text-gray-600 hover:text-gray-900' }}">
                        API Tokens
                    </a>
                @endif
                @can('queue.monitor')
                    @php $queueFailedCount = \Illuminate\Support\Facades\DB::table('failed_jobs')->count(); @endphp
                    <a href="{{ route('admin.queue') }}"
                       class="relative text-sm font-medium {{ request()->routeIs('admin.queue') ? 'text-blue-600 border-b-2 border-blue-600 pb-0.5' : 'text-gray-600 hover:text-gray-900' }}">
                        Queue
                        @if($queueFailedCount > 0)
                            <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-xs font-bold bg-red-500 text-white leading-none">
                                {{ $queueFailedCount > 99 ? '99+' : $queueFailedCount }}
                            </span>
                        @endif
                    </a>
                @endcan
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">Sign out</button>
                </form>
            </div>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{ $slot }}
</main>

@livewireScripts
</body>
</html>
