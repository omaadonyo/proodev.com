<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>For Companies &amp; Recruiters - Hire engineers who can actually prove it</title>

        <meta name="description" content="ProoDev for companies and recruiters: evidence-backed candidate reports, side-by-side comparison, talent pools, interview scheduling, and an evidence-based search across verified engineers.">

        <meta name="keywords" content="{{ ($metaKeywords ?? null) ?: app(\App\Services\SiteSettings::class)->metaKeywords() }}">

        <link rel="canonical" href="{{ url()->current() }}">


        <link rel="icon" href="/images/favicon-128.png" sizes="128x128" type="image/png">
        <link rel="icon" href="/images/favicon-64.png" sizes="64x64" type="image/png">

        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @fluxAppearance

        <style>
            .glow {
                background: radial-gradient(60rem 28rem at 50% -10%, rgb(55 80 235 / 0.12), transparent 60%),
                    radial-gradient(40rem 20rem at 80% 10%, rgb(55 80 235 / 0.05), transparent 55%);
            }
            .text-gradient {
                background: linear-gradient(100deg, #3750eb, #5b6cff 60%, #8f9dff);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
            .dark .text-gradient {
                background: linear-gradient(100deg, #6f84ff, #9db8ff 60%, #c3cdff);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
            @keyframes marquee {
                from { transform: translateX(0); }
                to { transform: translateX(-50%); }
            }
            .animate-marquee {
                animation: marquee 36s linear infinite;
            }
            @keyframes float-slow {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-10px); }
            }
            .animate-float-slow { animation: float-slow 7s ease-in-out infinite; }
            @keyframes fade-up {
                from { opacity: 0; transform: translateY(16px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .animate-fade-up { animation: fade-up 0.6s ease-out both; }
            .delay-100 { animation-delay: 0.1s; }
            .delay-200 { animation-delay: 0.2s; }
            .delay-300 { animation-delay: 0.3s; }

            .site-header { transition: background-color .3s ease, border-color .3s ease, box-shadow .3s ease, backdrop-filter .3s ease; }
            .site-header.is-scrolled {
                background-color: rgb(255 255 255 / 0.82);
                backdrop-filter: blur(14px);
                -webkit-backdrop-filter: blur(14px);
                border-color: rgb(228 228 231 / 0.7);
                box-shadow: 0 8px 30px rgb(0 0 0 / 0.06);
            }
            .dark .site-header.is-scrolled {
                background-color: rgb(9 9 11 / 0.75);
                border-color: rgb(255 255 255 / 0.08);
                box-shadow: 0 8px 30px rgb(0 0 0 / 0.45);
            }
        </style>
    </head>
    <body class="min-h-screen overflow-x-clip bg-white text-zinc-900 antialiased selection:bg-[#3750eb]/30 dark:bg-zinc-950 dark:text-zinc-100">

        {{-- Ambient glow --}}
        <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-[40rem] glow" aria-hidden="true"></div>

        @php
            $iconPaths = [
                'check' => 'M4.5 12.75l6 6 9-13.5',
                'x' => 'M6 18 18 6M6 6l12 12',
                'x-mark' => 'M6 18 18 6M6 6l12 12',
                'chevron-down' => 'M19.5 8.25l-7.5 7.5-7.5-7.5',
                'arrow-right' => 'M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z',
                'bars' => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
                'document-text' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 12h4.5m-4.5 3h5.25m-9.75-6h.008v.008h-.008v-.008ZM7.5 21h9a3 3 0 0 0 3-3V7.5A4.5 4.5 0 0 0 15 3H7.5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3Z',
                'shield-check' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
                'magnifying-glass' => 'M21 21l-4.35-4.35M11 17a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z',
                'scale' => 'M12 3v18m0 0H5.25M12 21h6.75M12 6.75 7.5 12m4.5-5.25L16.5 12m-4.5 9v-7.5M9.75 21h4.5',
                'document-check' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75 1.5 1.5 3.75-3.75m-1.5-4.5H8.25M12 15.75h-3.75m9.375-5.25h.008v.008h-.008v-.008Zm0 3.75h.008v.008h-.008v-.008ZM15 3H7.5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h9a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3Z',
                'chat-bubble-oval-left-ellipsis' => 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z',
                'shield-exclamation' => 'M12 9v3.75m0 3.75h.008v.008H12v-.008Zm0-10.214A11.95 11.95 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286Z',
                'bell-alert' => 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5',
                'folder' => 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z',
                'check-badge' => 'M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z',
                'users' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
                'calendar-days' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
                'envelope' => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75',
                'arrow-trending-up' => 'M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941',
                'map-pin' => 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z',
                'sparkles' => 'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z',
                'user-circle' => 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
                'squares-2x2' => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
                'briefcase' => 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0',
            ];
        @endphp

        {{-- ===================== NAV ===================== --}}
        <header class="site-header fixed inset-x-0 top-0 z-50 border-b border-transparent">
            <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('welcome') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo-black.png') }}" alt="ProoDev" class="h-7 w-auto dark:hidden" />
                    <img src="{{ asset('images/logo-white.png') }}" alt="ProoDev" class="hidden h-7 w-auto dark:block" />
                    <span class="hidden rounded-full bg-[#3750eb]/10 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-[#3750eb] sm:inline-block dark:text-[#8f9dff]">For companies</span>
                </a>

                <div class="hidden items-center gap-1 text-sm text-zinc-500 md:flex dark:text-zinc-400">
                    <a href="#why" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">Why evidence</a>
                    <a href="#tools" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">The toolkit</a>
                    <a href="#preview" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">Live search</a>
                    <a href="#pricing" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">Pricing</a>
                    <a href="{{ route('developers') }}" class="rounded-lg px-3 py-2 font-medium text-[#3750eb] transition hover:text-[#3750eb]/80 dark:text-[#8f9dff] dark:hover:text-[#9db8ff]">For developers</a>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 transition hover:text-zinc-900 sm:inline-block dark:text-zinc-300 dark:hover:text-white">Sign in</a>
                    <a href="{{ route('register', ['role' => 'company']) }}" class="inline-flex items-center rounded-full bg-[#3750eb] px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-[#3750eb]/25 transition hover:opacity-90">
                        Create company account
                    </a>
                    <x-theme-toggle />
                    <button type="button" data-mobile-menu-toggle class="inline-flex size-9 items-center justify-center rounded-lg border border-zinc-200 text-zinc-600 md:hidden dark:border-white/10 dark:text-zinc-300" aria-label="Toggle menu">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="{{ $iconPaths['bars'] }}" clip-rule="evenodd"/></svg>
                    </button>
                </div>
            </nav>

            <div data-mobile-menu class="hidden border-t border-zinc-200 bg-white/95 px-4 py-4 md:hidden dark:border-white/5 dark:bg-zinc-950/95">
                <div class="grid gap-1 text-sm">
                    <a href="#why" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">Why evidence</a>
                    <a href="#tools" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">The toolkit</a>
                    <a href="#preview" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">Live search</a>
                    <a href="#pricing" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">Pricing</a>
                    <a href="{{ route('developers') }}" class="rounded-lg px-3 py-2 font-medium text-[#3750eb] dark:text-[#8f9dff]">For developers</a>
                    <a href="{{ route('login') }}" class="mt-2 rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">Sign in</a>
                </div>
            </div>
        </header>

        {{-- ===================== HERO + LIVE SEARCH PREVIEW ===================== --}}
        <section id="top" class="relative mx-auto max-w-7xl px-4 pb-16 pt-28 sm:px-6 sm:pt-36 lg:px-8">
            <div class="mx-auto max-w-4xl animate-fade-up text-center">
                <a href="#preview" class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white/60 px-4 py-1.5 text-xs font-medium text-zinc-600 transition hover:border-[#3750eb]/40 hover:text-zinc-900 dark:border-white/10 dark:bg-white/5 dark:text-zinc-300 dark:hover:text-white">
                    <span class="relative flex size-2">
                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-[#3750eb] opacity-60"></span>
                        <span class="relative inline-flex size-2 rounded-full bg-[#3750eb]"></span>
                    </span>
                    {{ $onlineCount }} engineers online right now
                </a>

                <h1 class="mt-8 text-4xl font-bold tracking-tight text-zinc-900 sm:text-6xl lg:text-7xl dark:text-white">
                    Hire engineers who can <span class="text-gradient">actually prove it</span>
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-zinc-600 sm:text-xl dark:text-zinc-400">
                    Stop sifting self-reported resumes. Every candidate on ProoDev has a DevID of real work - analyzed evidence,
                    verified skills, community vouches, and an explainable magnitude score. Recruit against proof.
                </p>

                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('register', ['role' => 'company']) }}" class="inline-flex w-full items-center justify-center gap-2 rounded bg-[#3750eb] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#3750eb]/25 transition hover:opacity-90 sm:w-auto">
                        Create your company account - free
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                    </a>
                    <a href="#tools" class="inline-flex w-full items-center justify-center gap-2 rounded border border-zinc-200 bg-white/60 px-6 py-3 text-sm font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-white sm:w-auto dark:border-white/10 dark:bg-white/5 dark:text-zinc-200 dark:hover:border-white/25 dark:hover:bg-white/10">
                        See the recruiting toolkit
                    </a>
                </div>

                {{-- Live stats --}}
                <div class="mx-auto mt-12 grid max-w-3xl grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ([
                        ['value' => $engineersCount, 'label' => 'Engineers verified'],
                        ['value' => $evidenceCount, 'label' => 'Evidence analyzed'],
                        ['value' => $openJobsCount, 'label' => 'Open roles'],
                        ['value' => $onlineCount, 'label' => 'Online now'],
                    ] as $stat)
                        <div class="rounded-xl border border-zinc-200 bg-white/60 px-4 py-4 dark:border-white/10 dark:bg-white/[0.03]">
                            <div class="text-2xl font-bold tabular-nums tracking-tight text-zinc-900 dark:text-white">{{ $stat['value'] }}</div>
                            <div class="mt-0.5 text-[11px] font-medium uppercase tracking-wider text-zinc-500">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ===== LIVE EVIDENCE SEARCH PREVIEW (avatar view) ===== --}}
            <div id="preview" class="relative mx-auto mt-14 max-w-5xl" x-data="companySearch()">
                <div class="pointer-events-none absolute -inset-6 -z-10 rounded-3xl bg-[#3750eb]/10 blur-3xl" aria-hidden="true"></div>

                <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white/90 shadow-2xl shadow-zinc-900/10 backdrop-blur-xl dark:border-white/10 dark:bg-zinc-950/80">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-5 py-3.5 dark:border-white/5">
                        <div class="flex items-center gap-2">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-[#3750eb]/10 text-[#3750eb] dark:text-[#8f9dff]">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['magnifying-glass'] }}" clip-rule="evenodd"/></svg>
                            </span>
                            <div>
                                <div class="text-sm font-semibold text-zinc-900 dark:text-white">Evidence search</div>
                                <div class="text-xs text-zinc-500">Live from the network - analyzed work, not resume keywords</div>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/10 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            <span class="relative flex size-1.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-500 opacity-60"></span><span class="relative inline-flex size-1.5 rounded-full bg-emerald-500"></span></span>
                            Live data
                        </span>
                    </div>

                    {{-- Paste a job description / URL to auto-apply skill filters --}}
                    <div class="border-b border-zinc-200 px-5 py-3 dark:border-white/5">
                        <button type="button" @click="showMatch = !showMatch" class="flex w-full items-center justify-between gap-3 text-left">
                            <span class="flex items-center gap-2 text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-3.5 text-[#3750eb] dark:text-[#8f9dff]"><path fill-rule="evenodd" d="{{ $iconPaths['sparkles'] }}" clip-rule="evenodd"/></svg>
                                Paste a job description or URL to auto-apply skills
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 shrink-0 text-zinc-500 transition duration-200" :class="showMatch ? 'rotate-180' : ''"><path fill-rule="evenodd" d="{{ $iconPaths['chevron-down'] }}" clip-rule="evenodd"/></svg>
                        </button>

                        <div x-show="showMatch" x-cloak class="mt-3 grid gap-2">
                            <textarea x-model="matchText" rows="3"
                                placeholder="Paste a full job description here, or a URL like https://company.com/careers/backend-engineer..."
                                class="w-full resize-none rounded-lg border border-zinc-200 bg-white p-3 text-sm text-zinc-900 outline-none transition placeholder:text-zinc-400 focus:border-[#3750eb]/50 focus:ring-2 focus:ring-[#3750eb]/20 dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:border-[#3750eb]/50"></textarea>
                            <div class="flex flex-wrap items-center gap-3">
                                <button type="button" @click="matchSkills()" :disabled="matching"
                                    class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg bg-[#3750eb] px-3.5 text-xs font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="{{ $iconPaths['sparkles'] }}" clip-rule="evenodd"/></svg>
                                    <span x-text="matching ? 'Reading posting...' : 'Match skills'"></span>
                                </button>
                                <span x-show="matchResult" x-cloak
                                    :class="{
                                        'text-emerald-600 dark:text-emerald-400': matchResult && matchResult.type === 'ok',
                                        'text-amber-600 dark:text-amber-400': matchResult && matchResult.type === 'warn',
                                        'text-rose-600 dark:text-rose-400': matchResult && matchResult.type === 'err',
                                    }"
                                    class="text-xs font-medium" x-text="matchResult ? matchResult.text : ''"></span>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-zinc-200 px-5 py-3 dark:border-white/5">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative min-w-0 flex-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400"><path fill-rule="evenodd" d="{{ $iconPaths['magnifying-glass'] }}" clip-rule="evenodd"/></svg>
                                <input x-model="q" type="search" placeholder="Search technologies, skills, names - try 'Laravel' or 'React'..."
                                    class="w-full rounded-lg border border-zinc-200 bg-white py-2 pl-9 pr-3 text-sm text-zinc-900 outline-none transition placeholder:text-zinc-400 focus:border-[#3750eb]/50 focus:ring-2 focus:ring-[#3750eb]/20 dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:border-[#3750eb]/50">
                            </div>
                            <button type="button" @click="verifiedOnly = !verifiedOnly"
                                :class="verifiedOnly ? 'bg-[#3750eb] text-white border-[#3750eb]' : 'bg-white text-zinc-600 border-zinc-200 hover:border-[#3750eb]/40 dark:bg-white/5 dark:text-zinc-300 dark:border-white/10'"
                                class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg border px-3 text-xs font-semibold transition">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="{{ $iconPaths['check-badge'] }}" clip-rule="evenodd"/></svg>
                                Verified only
                            </button>
                            <button type="button" @click="onlineOnly = !onlineOnly"
                                :class="onlineOnly ? 'bg-emerald-500 text-white border-emerald-500' : 'bg-white text-zinc-600 border-zinc-200 hover:border-emerald-400/50 dark:bg-white/5 dark:text-zinc-300 dark:border-white/10'"
                                class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg border px-3 text-xs font-semibold transition">
                                <span class="size-2 rounded-full bg-emerald-500"></span>
                                Online now
                            </button>
                            <span class="hidden shrink-0 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-medium text-zinc-500 sm:inline-flex dark:border-white/10 dark:bg-white/5 dark:text-zinc-400">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="{{ $iconPaths['user-circle'] }}" clip-rule="evenodd"/></svg>
                                Avatar view
                            </span>
                        </div>

                        {{-- Filters: skills, location, reset --}}
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Skills</span>
                            @foreach ($skillFilters as $skill)
                                <button
                                    type="button"
                                    @click="toggleSkill({{ json_encode($skill) }})"
                                    :class="activeSkills.includes({{ json_encode($skill) }}) ? 'bg-[#3750eb] text-white border-[#3750eb]' : 'bg-white text-zinc-600 border-zinc-200 hover:border-[#3750eb]/40 dark:bg-white/5 dark:text-zinc-300 dark:border-white/10'"
                                    class="inline-flex h-8 shrink-0 items-center rounded-full border px-3 text-xs font-medium transition"
                                >
                                    {{ $skill }}
                                </button>
                            @endforeach
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <div class="relative min-w-0 flex-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400"><path fill-rule="evenodd" d="{{ $iconPaths['map-pin'] }}" clip-rule="evenodd"/></svg>
                                <input x-model="loc" type="search" placeholder="Location - e.g. Kampala, Remote, Worldwide..."
                                    class="w-full rounded-lg border border-zinc-200 bg-white py-2 pl-9 pr-3 text-sm text-zinc-900 outline-none transition placeholder:text-zinc-400 focus:border-[#3750eb]/50 focus:ring-2 focus:ring-[#3750eb]/20 dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:border-[#3750eb]/50">
                            </div>
                            <button
                                type="button"
                                @click="resetFilters()"
                                x-show="hasActiveFilters"
                                x-cloak
                                class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-zinc-600 transition hover:border-[#3750eb]/40 hover:text-zinc-900 dark:border-white/10 dark:bg-white/5 dark:text-zinc-300 dark:hover:text-white"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="{{ $iconPaths['x-mark'] }}" clip-rule="evenodd"/></svg>
                                Reset filters
                                <span x-text="filterCount" class="rounded-full bg-zinc-900 px-1.5 py-0.5 text-[10px] font-bold text-white dark:bg-white dark:text-zinc-900"></span>
                            </button>
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="grid grid-cols-4 gap-3 sm:grid-cols-6 lg:grid-cols-8">
                            <template x-for="engineer in matches" :key="engineer.id">
                                <a :href="'/passport/' + engineer.username" class="group flex flex-col items-center gap-1.5 rounded-lg p-2 transition hover:bg-zinc-50 dark:hover:bg-zinc-900"
                                    :title="engineer.name + (engineer.headline ? ' - ' + engineer.headline : '')">
                                    <span class="relative">
                                        <span class="block rounded-full p-[2.5px] transition"
                                            :style="matchPct(engineer) === 100 && activeSkills.length ? 'background: linear-gradient(135deg, #34d399, #14b8a6)' : ''">
                                            <img :src="engineer.avatar" :alt="engineer.name" loading="lazy"
                                                class="size-14 rounded-full object-cover ring-1 ring-zinc-200 transition group-hover:ring-2 group-hover:ring-[#3750eb]/60 dark:ring-white/10" />
                                        </span>
                                        <span x-show="engineer.online" class="absolute bottom-0 right-0 size-3 rounded-full border-2 border-white bg-emerald-500 dark:border-zinc-950"></span>
                                        <span x-show="activeSkills.length" x-cloak
                                            :class="matchPct(engineer) === 100 ? 'text-white' : matchPct(engineer) >= 70 ? 'bg-emerald-500 text-white' : matchPct(engineer) >= 40 ? 'bg-amber-500 text-white' : 'bg-zinc-600 text-white dark:bg-zinc-700'"
                                            :style="matchPct(engineer) === 100 ? 'background: linear-gradient(135deg, #10b981, #14b8a6)' : ''"
                                            :title="matchPct(engineer) === 100 ? 'Perfect match — covers all selected skills' : matchPct(engineer) + '% of the selected skills'"
                                            class="absolute -right-1 -top-1 inline-flex items-center gap-0.5 rounded-full px-1 py-px text-[9px] font-bold leading-tight tabular-nums ring-2 ring-white dark:ring-zinc-950">
                                            <svg x-show="matchPct(engineer) === 100" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-2.5"><path fill-rule="evenodd" d="{{ $iconPaths['check-badge'] }}" clip-rule="evenodd"/></svg>
                                            <span x-text="matchPct(engineer) + '%'"></span>
                                        </span>
                                    </span>
                                    <span class="w-full truncate text-center text-[10px] text-zinc-500" x-text="engineer.name"></span>
                                    <span x-show="engineer.verified" class="inline-flex items-center gap-0.5 rounded-full bg-emerald-400/10 px-1.5 py-0.5 text-[9px] font-semibold text-emerald-600 dark:text-emerald-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-2.5"><path fill-rule="evenodd" d="{{ $iconPaths['check-badge'] }}" clip-rule="evenodd"/></svg>
                                        Verified
                                    </span>
                                </a>
                            </template>
                        </div>

                        <div x-show="matches.length === 0" class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-600">
                            <div class="text-sm font-semibold text-zinc-900 dark:text-white">No engineers match your filters</div>
                            <p class="mt-1 text-sm text-zinc-500">Try a broader term, fewer skills, or reset the filters.</p>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-zinc-200 pt-3 text-xs text-zinc-500 dark:border-white/5">
                            <span>
                                Showing <span class="font-semibold text-zinc-800 dark:text-zinc-200" x-text="matches.length"></span> of {{ count($engineers) }} engineers with analyzed evidence
                            </span>
                            <a href="{{ route('register', ['role' => 'company']) }}" class="inline-flex items-center gap-1 font-semibold text-[#3750eb] transition hover:gap-2 dark:text-[#8f9dff]">
                                Search the full network
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== WHY EVIDENCE BEATS RESUMES ===================== --}}
        <section id="why" class="relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">Why evidence beats resumes</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">ProoDev gives recruiters what self-reported profiles cannot: verifiable proof</h2>
                </div>

                <div class="relative mx-auto mt-12 max-w-4xl overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.03]">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-white/10">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Evidence vs resume-driven sourcing</h3>
                        <p class="text-sm text-zinc-500">Every row is verifiable - claims have to point to real work.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 text-left dark:border-white/10">
                                    <th class="px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400"></th>
                                    <th class="px-4 py-3 text-center font-medium text-zinc-500 dark:text-zinc-400">Resume / LinkedIn</th>
                                    <th class="px-4 py-3 text-center font-semibold text-[#3750eb] dark:text-[#8f9dff]">ProoDev</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                                @foreach ([
                                    ['claim' => 'Skills are self-reported', 'theirs' => false, 'ours' => true],
                                    ['claim' => 'Expertise is linked to real work', 'theirs' => false, 'ours' => true],
                                    ['claim' => 'Claims can be checked against sources', 'theirs' => false, 'ours' => true],
                                    ['claim' => 'Score is explainable, factor by factor', 'theirs' => false, 'ours' => true],
                                    ['claim' => 'Community vouches weighted by reputation', 'theirs' => false, 'ours' => true],
                                    ['claim' => 'Verification backed by an authority', 'theirs' => false, 'ours' => true],
                                    ['claim' => 'Searched by analyzed work, not keywords', 'theirs' => false, 'ours' => true],
                                ] as $row)
                                    <tr>
                                        <td class="px-6 py-3 text-zinc-700 dark:text-zinc-300">{{ $row['claim'] }}</td>
                                        <td class="px-4 py-3 text-center">
                                            @if ($row['theirs'])
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mx-auto size-5 text-emerald-500"><path fill-rule="evenodd" d="{{ $iconPaths['check'] }}" clip-rule="evenodd"/></svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mx-auto size-5 text-red-400"><path fill-rule="evenodd" d="{{ $iconPaths['x'] }}" clip-rule="evenodd"/></svg>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mx-auto size-5 text-emerald-500"><path fill-rule="evenodd" d="{{ $iconPaths['check'] }}" clip-rule="evenodd"/></svg>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== THE RECRUITING TOOLKIT ===================== --}}
        <section id="tools" class="relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">The recruiting toolkit</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Everything a recruiting team needs, in one place</h2>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">From evidence search to interview scheduling - the full Recruiter Intelligence Suite and agency workspace.</p>
                </div>

                <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        ['icon' => 'document-text', 'title' => 'Candidate intelligence reports', 'text' => 'Every candidate gets an evidence-backed report: technologies, engineering areas, strengths, weaknesses, seniority, and an explainable magnitude score.'],
                        ['icon' => 'magnifying-glass', 'title' => 'Evidence-based search', 'text' => 'Search runs against analyzed work - technologies and areas inside real repos and articles - not self-reported resume keywords. Grid, list, detailed, or avatar views.'],
                        ['icon' => 'scale', 'title' => 'Side-by-side comparison', 'text' => 'Stack two or three candidates and compare them across evidence, verification, and community signals - let the evidence pick the winner.'],
                        ['icon' => 'check-badge', 'title' => 'Verified network', 'text' => 'A filterable pool of engineers who passed official verification or earned verified skills. Proven, not claimed.'],
                        ['icon' => 'document-check', 'title' => 'Resume vs evidence validation', 'text' => 'Paste a resume and ProoDev checks its claims against the candidate\'s analyzed evidence. Claims have to point to proof.'],
                        ['icon' => 'shield-exclamation', 'title' => 'Hiring risk assessment', 'text' => 'Evidence gaps, verification gaps, and pipeline red flags surfaced up front, so you move forward with open eyes.'],
                        ['icon' => 'chat-bubble-oval-left-ellipsis', 'title' => 'Evidence-grounded interviews', 'text' => 'Interview questions generated from the candidate\'s actual analyzed work - probe what they really built, not what they claim.'],
                        ['icon' => 'folder', 'title' => 'Talent pools & statuses', 'text' => 'Save candidates into named pools and move them through shortlisted, contacted, interviewing, offered, and placed - with shared workspace access.'],
                        ['icon' => 'calendar-days', 'title' => 'Interview scheduler & calendar', 'text' => 'Schedule interviews with date, time, and mode, view a weekly calendar across all pools, and send candidates calendar invites (.ics).'],
                        ['icon' => 'users', 'title' => 'Agency workspace', 'text' => 'Talent pools, shared notes, member seats, statuses, interviews, and placements - the full agency toolkit for recruiting teams.'],
                        ['icon' => 'envelope', 'title' => 'Exports & sharing', 'text' => 'Export candidate reports and shortlists to PDF or Excel, email them to stakeholders, and send professional invitations from the app.'],
                        ['icon' => 'bell-alert', 'title' => 'Talent discovery alerts', 'text' => 'Paste a job description or URL and get alerts when new proven engineers match - watch the network without manual searching.'],
                        ['icon' => 'briefcase', 'title' => 'Job posts & applicant tracking', 'text' => 'Post unlimited roles with AI-drafted descriptions, receive applications straight to a pipeline, and track every applicant in one board.'],
                        ['icon' => 'arrow-trending-up', 'title' => 'Magnitude rankings', 'text' => 'A professional leaderboard of engineers ranked by explainable magnitude, with candidate details and PDF export on every entry.'],
                        ['icon' => 'sparkles', 'title' => 'Talent alerts on your terms', 'text' => 'Control which notifications reach you - new job offers, chat messages, and evidence activity - through per-user email preferences.'],
                    ] as $tool)
                        <div class="group rounded-2xl border border-zinc-200 bg-white p-5 transition duration-300 hover:-translate-y-1 hover:border-[#3750eb]/40 hover:shadow-xl hover:shadow-[#3750eb]/10 dark:border-white/10 dark:bg-white/[0.03] dark:hover:border-[#3750eb]/30 dark:hover:bg-white/[0.06]">
                            <span class="flex size-10 items-center justify-center rounded-xl bg-[#3750eb]/10 text-[#3750eb] ring-1 ring-zinc-200 transition group-hover:scale-105 dark:text-[#8f9dff] dark:ring-white/10">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="{{ $iconPaths[$tool['icon']] }}" clip-rule="evenodd"/></svg>
                            </span>
                            <h3 class="mt-4 text-base font-semibold text-zinc-900 dark:text-white">{{ $tool['title'] }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $tool['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===================== HOW IT WORKS FOR COMPANIES ===================== --}}
        <section id="how" class="relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">How it works</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">From job description to signed offer, faster</h2>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">Paste the role. ProoDev finds the proof. You make the call.</p>
                </div>

                <div class="mx-auto mt-10 grid max-w-5xl gap-6 md:grid-cols-4">
                    @foreach ([
                        ['icon' => 'briefcase', 'number' => '01', 'title' => 'Post a role or paste a job description', 'description' => 'Publish a job or paste a full job description - AI drafts it, extracts the skills, and matches candidates automatically.'],
                        ['icon' => 'magnifying-glass', 'number' => '02', 'title' => 'AI matches & ranks candidates', 'description' => 'Verified engineers surface first, ranked by evidence relevance - no manual keyword sifting across resumes.'],
                        ['icon' => 'folder', 'number' => '03', 'title' => 'Save, compare & collaborate', 'description' => 'Drop matches into talent pools, set statuses, compare candidates side-by-side, and share the shortlist with your team.'],
                        ['icon' => 'calendar-days', 'number' => '04', 'title' => 'Interview & hire', 'description' => 'Schedule interviews with calendar invites, generate evidence-grounded questions, and move the winner through to offer.'],
                    ] as $step)
                        <div class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-6 transition duration-300 hover:-translate-y-1 hover:border-[#3750eb]/50 hover:shadow-xl hover:shadow-[#3750eb]/10 dark:border-white/10 dark:bg-white/[0.03] dark:hover:border-[#3750eb]/30 dark:hover:bg-white/[0.05]">
                            <div class="flex items-center justify-between">
                                <span class="flex size-10 items-center justify-center rounded-xl bg-[#3750eb]/10 text-[#3750eb] dark:text-[#8f9dff]">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="{{ $iconPaths[$step['icon']] }}" clip-rule="evenodd"/></svg>
                                </span>
                                <span class="text-xs font-semibold tracking-widest text-zinc-400">{{ $step['number'] }}</span>
                            </div>
                            <h3 class="mt-4 text-base font-semibold text-zinc-900 dark:text-white">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $step['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===================== LIVE NETWORK MARQUEE ===================== --}}
        <section class="relative overflow-hidden border-t border-zinc-200 py-14 dark:border-white/5">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">Engineers with proof, all over the world</h2>
                    <p class="mt-3 text-zinc-600 dark:text-zinc-400">Real developers building evidence-backed identities on ProoDev - this is the network you hire from.</p>
                </div>
            </div>
            <div class="relative mt-10 overflow-hidden">
                <div class="flex w-max animate-marquee gap-4 pr-4">
                    @php $marqueeMembers = $engineersMarquee->count() > 0 ? $engineersMarquee->values() : collect([(object) ['name' => 'Alex Morgan', 'location' => 'Berlin'], (object) ['name' => 'Priya Sharma', 'location' => 'Mumbai'], (object) ['name' => 'Kenji Sato', 'location' => 'Tokyo']]); @endphp
                    @foreach ($marqueeMembers->concat($marqueeMembers) as $member)
                        <div class="flex items-center gap-2.5 rounded-full border border-zinc-200 bg-white/60 px-4 py-2 dark:border-white/10 dark:bg-white/[0.03]">
                            <span class="flex size-7 items-center justify-center rounded-full bg-black text-xs font-bold text-white ring-2 ring-zinc-200 dark:bg-white dark:text-black dark:ring-zinc-800">{{ strtoupper(substr($member->name ?? 'A', 0, 1)) }}</span>
                            <span class="whitespace-nowrap text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $member->name ?? 'Engineer' }}</span>
                            <span class="text-xs text-zinc-500">{{ $member->location ?? '' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Live roles --}}
            @if ($openJobs->isNotEmpty())
                <div class="mx-auto mt-12 max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($openJobs as $job)
                            <a href="{{ route('jobs.show', [$job->company, $job]) }}" class="group flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-[#3750eb]/40 dark:border-white/10 dark:bg-white/[0.03]">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-[#3750eb]/10 text-[#3750eb] dark:text-[#8f9dff]">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="{{ $iconPaths['briefcase'] }}" clip-rule="evenodd"/></svg>
                                </span>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-zinc-900 group-hover:text-[#3750eb] dark:text-white">{{ $job->title }}</div>
                                    <div class="truncate text-xs text-zinc-500">{{ $job->company?->name ?? 'Company' }} · {{ $job->location ?? 'Remote' }}</div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="ml-auto size-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-[#3750eb] dark:text-zinc-600"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        {{-- ===================== PRICING ===================== --}}
        <section id="pricing" class="relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">Pricing</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Start free, scale with your team</h2>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">Create a company account free and publish roles. Upgrade when you need the full intelligence suite.</p>
                </div>

                @php
                    $planOnboardHref = function (string $side) {
                        if ($side === 'enterprise') {
                            return 'mailto:'.config('billing.seller.email');
                        }

                        if (auth()->check() && auth()->user()->hasIntelligenceAccess()) {
                            return route('recruiter.index');
                        }

                        if (auth()->check() && auth()->user()->role?->isDeveloper()) {
                            return route('companies.create');
                        }

                        return route('register', ['role' => 'company']);
                    };

                    $pricingTiers = [
                        [
                            'name' => 'Recruiter Intelligence Suite',
                            'price' => '$599',
                            'per' => 'first month · then $199/mo',
                            'highlight' => true,
                            'cta' => 'Get Intelligence Suite',
                            'cta_note' => 'For agencies & scaling teams',
                            'side' => 'recruiter',
                            'features' => config('billing.companies.intelligence.features', []),
                        ],
                        [
                            'name' => 'Recruiter',
                            'price' => '$299',
                            'per' => 'per month',
                            'highlight' => false,
                            'cta' => 'Choose Recruiter',
                            'cta_note' => 'Cancel anytime',
                            'side' => 'recruiter',
                            'features' => config('billing.companies.recruiter.features', []),
                        ],
                        [
                            'name' => 'Enterprise',
                            'price' => 'Custom',
                            'per' => 'tailored to your team',
                            'highlight' => false,
                            'cta' => 'Contact sales',
                            'cta_note' => 'Multi-seat, SSO & dedicated support',
                            'side' => 'enterprise',
                            'features' => [
                                'Everything in Recruiter Intelligence Suite',
                                'Multi-seat agency pricing',
                                'Custom AI / model configuration',
                                'Dedicated onboarding & support',
                                'SSO & advanced security',
                            ],
                        ],
                    ];
                @endphp

                <div class="mx-auto mt-10 grid max-w-6xl items-stretch gap-6 lg:grid-cols-3">
                    @foreach ($pricingTiers as $index => $pricing)
                        <div class="flex flex-col rounded-2xl border p-6 {{ $pricing['highlight'] ? 'border-[#3750eb]/50 bg-white shadow-2xl shadow-[#3750eb]/15 dark:border-[#3750eb]/40 dark:bg-white/[0.06]' : 'border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.03]' }} {{ $pricing['highlight'] ? 'lg:-translate-y-2 lg:scale-[1.03]' : '' }}">
                            <div class="flex items-center justify-between">
                                <div class="text-base font-semibold text-zinc-900 dark:text-white">{{ $pricing['name'] }}</div>
                                @if ($pricing['highlight'])
                                    <span class="rounded-full bg-[#3750eb] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-white">Most powerful</span>
                                @endif
                            </div>

                            <div class="mt-5 flex items-baseline gap-1">
                                <span class="text-4xl font-bold tabular-nums tracking-tight text-zinc-900 dark:text-white">{{ $pricing['price'] }}</span>
                                <span class="text-sm text-zinc-500">{{ $pricing['per'] }}</span>
                            </div>

                            <div class="mt-6 grid gap-2.5">
                                @foreach ($pricing['features'] as $feature)
                                    <div class="flex items-start gap-2.5 text-sm text-zinc-700 dark:text-zinc-300">
                                        <span class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full bg-[#3750eb]/10 text-[#3750eb]">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-2.5"><path fill-rule="evenodd" d="{{ $iconPaths['check'] }}" clip-rule="evenodd"/></svg>
                                        </span>
                                        {{ $feature }}
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-auto pt-7">
                                <a href="{{ $planOnboardHref($pricing['side']) }}"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ $pricing['highlight'] ? 'bg-[#3750eb] text-white shadow-lg shadow-[#3750eb]/25 hover:opacity-90' : 'border border-zinc-200 bg-white/60 text-zinc-700 hover:border-[#3750eb]/40 hover:text-[#3750eb] dark:border-white/10 dark:bg-white/5 dark:text-zinc-200 dark:hover:border-[#3750eb]/40 dark:hover:text-white' }}">
                                    {{ $pricing['cta'] }}
                                </a>
                                <p class="mt-2 text-center text-xs text-zinc-500">{{ $pricing['cta_note'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mx-auto mt-8 flex max-w-4xl items-center justify-center gap-3 rounded-xl border border-[#3750eb]/20 bg-[#3750eb]/5 px-5 py-4 dark:border-[#3750eb]/30 dark:bg-[#3750eb]/10">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-[#3750eb]/10 text-[#3750eb] dark:text-[#8f9dff]">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['check-badge'] }}" clip-rule="evenodd"/></svg>
                    </span>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">
                        <span class="font-semibold text-zinc-900 dark:text-white">Need the whole team onboard?</span>
                        Contact us for custom Enterprise pricing - multi-seat discounts, dedicated onboarding, and priority support.
                    </p>
                </div>
            </div>
        </section>

        {{-- ===================== FAQ ===================== --}}
        <section id="faq" class="relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">FAQ</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Questions from recruiters &amp; hiring teams</h2>
                </div>

                <div class="mt-10 grid gap-3">
                    @foreach ([
                        ['q' => 'How is this different from recruiting on LinkedIn or a job board?', 'a' => 'Job boards are keyword searches over self-reported profiles. ProoDev searches analyzed work - the actual repositories, articles, and projects engineers have published - and ranks candidates by explainable evidence, with verified engineers surfacing first.'],
                        ['q' => 'Can I post a job description instead of searching manually?', 'a' => 'Yes. Paste a full job description or a URL and ProoDev extracts the skills, matches candidates against their analyzed evidence, and ranks them - verified engineers first.'],
                        ['q' => 'Do candidates have to do anything to get scored?', 'a' => 'No. Candidates add evidence (repos, articles, projects) and ProoDev reads and analyzes the work automatically into a DevID with an explainable magnitude score. Recruiters just search the results.'],
                        ['q' => 'Can I save candidates and collaborate with my team?', 'a' => 'Yes. Save candidates into named talent pools, set statuses (shortlisted, interviewing, offered), share pools across a workspace, and compare candidates side-by-side.'],
                        ['q' => 'How do interviews work?', 'a' => 'Schedule interviews with date, time, and mode from the Interview Builder, see them on a weekly calendar, and generate evidence-grounded questions from each candidate\'s actual analyzed work. Invitations include a calendar invite.'],
                        ['q' => 'Is there a free plan for companies?', 'a' => 'Yes. Creating a company account and publishing roles is free. The Recruiter and Recruiter Intelligence Suite plans add reports, comparison, exports, and the full agency toolkit.'],
                        ['q' => 'Is candidate data private?', 'a' => 'DevIDs are public by default so the community can verify work, but candidates control what appears. Candidate contact details are only visible to verified recruiters and hiring companies.'],
                    ] as $faq)
                        <div data-faq class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.03]">
                            <button type="button" data-faq-toggle class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left">
                                <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $faq['q'] }}</span>
                                <svg data-faq-chevron xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 shrink-0 text-zinc-500 transition duration-200">
                                    <path fill-rule="evenodd" d="{{ $iconPaths['chevron-down'] }}" />
                                </svg>
                            </button>
                            <div data-faq-answer class="max-h-0 px-5 transition-all duration-300 ease-in-out">
                                <p class="pb-5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $faq['a'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===================== CTA ===================== --}}
        <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-gradient-to-br from-[#f1f4ff] via-white to-[#eef1ff] px-6 py-16 text-center sm:px-16 dark:border-white/10 dark:from-[#3750eb]/25 dark:via-zinc-900 dark:to-[#3750eb]/10">
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[#3750eb]/5 blur-3xl" aria-hidden="true"></div>
                <div class="relative">
                    <h2 class="mx-auto max-w-2xl text-3xl font-bold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                        Stop collecting resumes. Start hiring proof.
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-zinc-700 dark:text-zinc-300">
                        Create a company account free, publish your first role, and meet engineers whose work speaks for itself.
                    </p>
                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ route('register', ['role' => 'company']) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#3750eb] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#3750eb]/25 transition hover:opacity-90 sm:w-auto">
                            Create your company account
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-zinc-300 bg-white/60 px-6 py-3 text-sm font-semibold text-zinc-700 transition hover:bg-white sm:w-auto dark:border-white/20 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                            I already have an account
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== FOOTER ===================== --}}
        <footer class="relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-4 py-10 sm:flex-row sm:px-6 lg:px-8">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo-black.png') }}" alt="ProoDev" class="h-5 w-auto dark:hidden" />
                    <img src="{{ asset('images/logo-white.png') }}" alt="ProoDev" class="hidden h-5 w-auto dark:block" />
                </div>
                <p class="text-sm text-zinc-500">(c) {{ date('Y') }} {{ config('app.name', 'ProoDev') }}. Proof over claims.</p>
                <div class="flex items-center gap-4 text-sm text-zinc-500">
                    <a href="{{ route('welcome') }}" class="transition hover:text-zinc-900 dark:hover:text-white">For developers</a>
                    <a href="{{ route('news.index') }}" class="transition hover:text-zinc-900 dark:hover:text-white">News</a>
                    <a href="{{ route('privacy') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Privacy</a>
                    <a href="{{ route('terms') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Terms</a>
                    <a href="{{ route('cookies') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Cookies</a>
                </div>
            </div>
        </footer>

        <script>
            (function () {
                'use strict';

                var header = document.querySelector('.site-header');
                if (header) {
                    function onScroll() {
                        header.classList.toggle('is-scrolled', window.scrollY > 10);
                    }
                    onScroll();
                    window.addEventListener('scroll', onScroll, { passive: true });
                }

                var mobileToggle = document.querySelector('[data-mobile-menu-toggle]');
                var mobileMenu = document.querySelector('[data-mobile-menu]');
                if (mobileToggle && mobileMenu) {
                    mobileToggle.addEventListener('click', function () {
                        mobileMenu.classList.toggle('hidden');
                    });
                }

                var items = document.querySelectorAll('[data-faq]');
                items.forEach(function (item) {
                    var button = item.querySelector('[data-faq-toggle]');
                    var answer = item.querySelector('[data-faq-answer]');
                    var chevron = item.querySelector('[data-faq-chevron]');
                    if (!button || !answer) return;
                    button.addEventListener('click', function () {
                        var open = answer.style.maxHeight !== '0px' && answer.style.maxHeight !== '';
                        items.forEach(function (other) {
                            var oa = other.querySelector('[data-faq-answer]');
                            var oc = other.querySelector('[data-faq-chevron]');
                            if (oa) { oa.style.maxHeight = '0px'; }
                            if (oc) { oc.style.transform = 'rotate(0deg)'; }
                        });
                        if (!open) {
                            answer.style.maxHeight = answer.scrollHeight + 'px';
                            if (chevron) { chevron.style.transform = 'rotate(180deg)'; }
                        }
                    });
                });
            })();

            function companySearch() {
                var params = new URLSearchParams(window.location.search);
                return {
                    q: params.get('q') || '',
                    verifiedOnly: params.get('verified') === '1',
                    onlineOnly: params.get('online') === '1',
                    loc: params.get('loc') || '',
                    activeSkills: (params.get('skills') || '').split(',').filter(function (s) { return s.length > 0; }),
                    showMatch: false,
                    matchText: '',
                    matching: false,
                    matchResult: null,
                    engineers: @json($engineers),
                    allSkills: @json($skillFilters),
                    init: function () {
                        var self = this;
                        this.$watch('q', function () { self.syncUrl(); });
                        this.$watch('loc', function () { self.syncUrl(); });
                        this.$watch('verifiedOnly', function () { self.syncUrl(); });
                        this.$watch('onlineOnly', function () { self.syncUrl(); });
                    },
                    syncUrl: function () {
                        var params = new URLSearchParams();
                        if (this.q.trim()) params.set('q', this.q.trim());
                        if (this.loc.trim()) params.set('loc', this.loc.trim());
                        if (this.verifiedOnly) params.set('verified', '1');
                        if (this.onlineOnly) params.set('online', '1');
                        if (this.activeSkills.length) params.set('skills', this.activeSkills.join(','));
                        var qs = params.toString();
                        history.replaceState(null, '', qs ? window.location.pathname + '?' + qs : window.location.pathname);
                    },
                    toggleSkill: function (skill) {
                        var i = this.activeSkills.indexOf(skill);
                        if (i === -1) { this.activeSkills.push(skill); } else { this.activeSkills.splice(i, 1); }
                        this.syncUrl();
                    },
                    matchPct: function (engineer) {
                        if (!this.activeSkills.length) return 0;
                        var es = (engineer.skills || []).map(function (s) { return s.toLowerCase(); });
                        var covered = 0;
                        this.activeSkills.forEach(function (skill) {
                            if (es.indexOf(skill.toLowerCase()) !== -1) covered++;
                        });
                        return Math.round((covered / this.activeSkills.length) * 100);
                    },
                    matchSkills: async function () {
                        var raw = (this.matchText || '').trim();
                        if (!raw) {
                            this.matchResult = { type: 'err', text: 'Paste a job description or a URL first.' };
                            return;
                        }
                        this.matching = true;
                        this.matchResult = null;
                        var self = this;
                        try {
                            var token = document.querySelector('meta[name="csrf-token"]');
                            var resp = await fetch('/for-companies/match-skills', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
                                },
                                body: JSON.stringify({ text: raw })
                            });
                            var data = await resp.json().catch(function () { return {}; });
                            if (!resp.ok) {
                                this.matchResult = { type: 'err', text: data.error || 'Could not read that posting. Try again.' };
                                return;
                            }
                            var available = this.allSkills.map(function (s) { return s.toLowerCase(); });
                            var applied = [];
                            (data.skills || []).forEach(function (skill) {
                                var idx = available.indexOf(String(skill).toLowerCase());
                                if (idx === -1) return;
                                var canonical = self.allSkills[idx];
                                if (applied.indexOf(canonical) === -1) applied.push(canonical);
                            });
                            if (applied.length) {
                                applied.forEach(function (s) {
                                    if (self.activeSkills.indexOf(s) === -1) self.activeSkills.push(s);
                                });
                                var skipped = (data.skills || []).length - applied.length;
                                this.matchResult = {
                                    type: 'ok',
                                    text: applied.join(', ').replace(/\b\w/g, function (c) { return c.toUpperCase(); })
                                        + (skipped > 0 ? ' applied — ' + skipped + ' not in the visible network' : ' applied as filters')
                                };
                            } else if ((data.skills || []).length) {
                                this.matchResult = { type: 'warn', text: 'Extracted ' + data.skills.join(', ') + ' — none match the engineers shown here.' };
                            } else {
                                this.matchResult = { type: 'warn', text: 'No recognizable skills in that posting.' };
                            }
                            this.syncUrl();
                        } catch (e) {
                            this.matchResult = { type: 'err', text: 'Could not reach the matcher. Try again.' };
                        } finally {
                            this.matching = false;
                        }
                    },
                    resetFilters: function () {
                        this.q = '';
                        this.verifiedOnly = false;
                        this.onlineOnly = false;
                        this.loc = '';
                        this.activeSkills = [];
                        this.syncUrl();
                    },
                    get filterCount() {
                        return (this.q.trim() ? 1 : 0)
                            + (this.verifiedOnly ? 1 : 0)
                            + (this.onlineOnly ? 1 : 0)
                            + (this.loc.trim() ? 1 : 0)
                            + this.activeSkills.length;
                    },
                    get hasActiveFilters() {
                        return this.filterCount > 0;
                    },
                    get matches() {
                        var needle = this.q.trim().toLowerCase();
                        var loc = this.loc.trim().toLowerCase();
                        var self = this;
                        return this.engineers.filter(function (engineer) {
                            if (self.verifiedOnly && !engineer.verified) return false;
                            if (self.onlineOnly && !engineer.online) return false;
                            if (loc && !(engineer.location || '').toLowerCase().includes(loc)) return false;
                            if (self.activeSkills.length) {
                                var es = (engineer.skills || []).map(function (s) { return s.toLowerCase(); });
                                if (!self.activeSkills.some(function (s) { return es.indexOf(s.toLowerCase()) !== -1; })) return false;
                            }
                            if (!needle) return true;
                            var hay = [engineer.name, engineer.headline || '', (engineer.skills || []).join(' '), engineer.location || ''].join(' ').toLowerCase();
                            return hay.indexOf(needle) !== -1;
                        });
                    }
                };
            }
        </script>

        @fluxScripts
    </body>
</html>
