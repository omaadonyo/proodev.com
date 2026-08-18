<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen overflow-x-clip bg-zinc-50 dark:bg-zinc-950">
        <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-[32rem] glow" aria-hidden="true"></div>
        <main class="mx-auto flex min-h-dvh w-full max-w-6xl flex-col px-6 py-8">
            <a href="{{ route('welcome') }}" class="inline-flex w-fit items-center gap-2" wire:navigate>
                <x-app-logo />
            </a>
            <div class="flex flex-1 items-center py-10">
                {{ $slot }}
            </div>
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
