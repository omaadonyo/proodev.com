<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="min-w-0 mx-auto w-full lg:w-3/4">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
