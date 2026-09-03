<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen overflow-x-clip bg-white antialiased dark:bg-zinc-950">
        <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-[32rem] glow" aria-hidden="true"></div>
        <div class="bg-grid pointer-events-none fixed inset-0 -z-10 opacity-40" aria-hidden="true"></div>
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <a href="/" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <img src="{{ asset('images/favicon-64.png') }}" alt="ProoDev" class="size-10 rounded-xl shadow-lg shadow-violet-500/30" />

                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <div class="flex flex-col gap-6">
                    <div class="rounded-xl dark:bg-white/5 text-stone-800 dark:text-zinc-100 shadow-xs backdrop-blur">
                        <div class="px-10 py-8">{{ $slot }}</div>
                    </div>
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
