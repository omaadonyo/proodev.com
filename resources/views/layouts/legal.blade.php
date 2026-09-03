<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title', config('app.name', 'ProoDev'))</title>

        <meta name="description" content="@yield('meta_description', 'Legal policies for ' . config('app.name', 'ProoDev'))">

        <meta name="keywords" content="{{ ($metaKeywords ?? null) ?: app(\App\Services\SiteSettings::class)->metaKeywords() }}">

        <link rel="canonical" href="{{ url()->current() }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'ProoDev') }}">
        <meta property="og:title" content="@yield('title', config('app.name', 'ProoDev'))">
        <meta property="og:description" content="@yield('meta_description', 'Legal policies for ' . config('app.name', 'ProoDev'))">
        <meta property="og:url" content="{{ url()->current() }}">

        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="@yield('title', config('app.name', 'ProoDev'))">
        <meta name="twitter:description" content="@yield('meta_description', 'Legal policies for ' . config('app.name', 'ProoDev'))">

  
        <link rel="icon" href="/images/favicon-128.png" sizes="128x128" type="image/png">
        <link rel="icon" href="/images/favicon-64.png" sizes="64x64" type="image/png">

        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @fluxAppearance
    </head>
    <body class="min-h-screen bg-white text-zinc-900 antialiased selection:bg-[#3750eb]/30 dark:bg-zinc-950 dark:text-zinc-100">

        <header class="border-b border-zinc-200 dark:border-white/5">
            <nav class="mx-auto flex h-16 max-w-3xl items-center justify-between gap-4 px-4 sm:px-6">
                <a href="{{ route('welcome') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo-black.png') }}" alt="ProoDev" class="h-7 w-auto dark:hidden" />
                    <img src="{{ asset('images/logo-white.png') }}" alt="ProoDev" class="hidden h-7 w-auto dark:block" />
                </a>
                <div class="flex items-center gap-4">
                    <a href="{{ route('welcome') }}" class="text-sm font-medium text-zinc-500 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Back to home</a>
                    <x-theme-toggle />
                </div>
            </nav>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">@yield('title')</h1>
            <p class="mt-2 text-sm text-zinc-500">Last updated: @yield('last_updated')</p>
            <div class="mt-8 space-y-8 text-[15px] leading-relaxed text-zinc-700 dark:text-zinc-300">
                @yield('content')
            </div>
        </main>

        <footer class="border-t border-zinc-200 dark:border-white/5">
            <div class="mx-auto max-w-3xl px-4 py-6 text-center sm:px-6">
                <a href="mailto:info@proodev.com" class="text-sm text-zinc-500 transition hover:text-zinc-900 dark:hover:text-white">For inquiries: info@proodev.com</a>
            </div>
            <div class="mx-auto flex max-w-3xl flex-col items-center justify-between gap-4 px-4 py-8 sm:flex-row sm:px-6">
                <p class="text-sm text-zinc-500">(c) {{ date('Y') }} {{ config('app.name', 'ProoDev') }}. Proof over claims.</p>
                <div class="flex items-center gap-4 text-sm text-zinc-500">
                    <a href="{{ route('privacy') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Privacy</a>
                    <a href="{{ route('terms') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Terms</a>
                    <a href="{{ route('cookies') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Cookies</a>
                </div>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
