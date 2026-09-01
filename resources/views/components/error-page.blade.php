@props([
    'code' => 'Error',
    'title' => 'Something went wrong',
    'message' => 'An unexpected error occurred. Please try again.',
    'primaryUrl' => '/',
    'primaryLabel' => 'Back to home',
    'secondaryUrl' => null,
    'secondaryLabel' => null,
])

@php
    $isAuth = auth()->check();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $code }} · {{ config('app.name', 'ProoDev') }}</title>

        <meta name="description" content="{{ $title }}">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/images/favicon-128.png" sizes="128x128" type="image/png">
        <link rel="icon" href="/images/favicon-64.png" sizes="64x64" type="image/png">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @fluxAppearance
    </head>
    <body class="flex min-h-screen flex-col bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <header class="mx-auto flex w-full max-w-5xl items-center justify-between px-4 py-6 sm:px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo-black.png') }}" alt="ProoDev" class="h-7 w-auto dark:hidden" />
                <img src="{{ asset('images/logo-white.png') }}" alt="ProoDev" class="hidden h-7 w-auto dark:block" />
            </a>

            <div class="flex items-center gap-3">
                <x-theme-toggle />
                @if ($isAuth)
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex cursor-pointer items-center rounded-[0.35rem] bg-zinc-900 px-[1.25rem] py-[0.45rem] text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                        >
                            Log out
                        </button>
                    </form>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex items-center rounded-[0.35rem] bg-zinc-900 px-[1.25rem] py-[0.45rem] text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                    >
                        Sign in
                    </a>
                @endif
            </div>
        </header>

        <main class="mx-auto flex w-full max-w-3xl flex-1 flex-col items-center justify-center px-4 pb-24 pt-10 text-center">
            <p class="font-mono text-sm font-semibold uppercase tracking-[0.2em] text-zinc-400 dark:text-zinc-500">{{ $code }}</p>

            <h1 class="mt-3 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                {{ $title }}
            </h1>

            <p class="mx-auto mt-4 max-w-md text-base leading-relaxed text-zinc-600 dark:text-zinc-400">
                {{ $message }}
            </p>

            <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row">
                <a
                    href="{{ $primaryUrl }}"
                    class="inline-flex items-center justify-center rounded-[0.35rem] bg-zinc-900 px-[1.25rem] py-[0.45rem] text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                >
                    {{ $primaryLabel }}
                </a>

                @if ($secondaryUrl && $secondaryLabel)
                    <a
                        href="{{ $secondaryUrl }}"
                        class="inline-flex items-center justify-center rounded-[0.35rem] border border-zinc-200 bg-white px-[1.25rem] py-[0.45rem] text-sm font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-white/15 dark:bg-transparent dark:text-zinc-200 dark:hover:border-white/30 dark:hover:bg-white/5"
                    >
                        {{ $secondaryLabel }}
                    </a>
                @endif
            </div>
        </main>

        <footer class="border-t border-zinc-200 py-5 text-center text-xs text-zinc-400 dark:border-white/10">
            &copy; {{ date('Y') }} {{ config('app.name', 'ProoDev') }} · proof over claims.
        </footer>
    </body>
</html>
