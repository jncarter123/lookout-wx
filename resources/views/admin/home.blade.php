<x-layouts.app title="Home">
    <div class="text-center py-16">
        <h1 class="text-2xl font-semibold text-gray-900">Welcome, {{ auth()->user()->name }}</h1>
        <p class="mt-2 text-gray-500">Select an option from the menu above to get started.</p>
    </div>
</x-layouts.app>
