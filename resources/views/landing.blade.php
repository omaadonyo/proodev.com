<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'ProoDev') }} - Proof Over Claims: Evidence-Backed Engineering Identity</title>

        <meta name="description" content="ProoDev turns your real work into evidence. Paste a repo or project URL, AI analyzes it into an engineering report and an explainable Engineering Magnitude score, and you get a public passport that can't be faked.">

        <meta name="keywords" content="engineering magnitude, evidence-backed portfolio, developer persona, engineer, ai analysis, engineering report, verified engineer, proof over claims, open source, software engineer">

        <link rel="canonical" href="{{ url()->current() }}">


        <link rel="icon" href="/images/favicon-128.png" sizes="128x128" type="image/png">
        <link rel="icon" href="/images/favicon-64.png" sizes="64x64" type="image/png">

        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @fluxAppearance

        <style>
            #global-canvas { opacity: 0.3; }
            .glow {
                background: radial-gradient(60rem 28rem at 50% -10%, rgb(55 80 235 / 0.1), transparent 60%),
                    radial-gradient(40rem 20rem at 80% 10%, rgb(55 80 235 / 0.04), transparent 55%);
            }
            .text-gradient {
                background: linear-gradient(100deg, #3750eb, #5b6cff 60%, #3750eb);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
            .dark .text-gradient {
                background: linear-gradient(100deg, #6f84ff, #9db8ff 60%, #6f84ff);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
            @keyframes marquee {
                from { transform: translateX(0); }
                to { transform: translateX(-50%); }
            }
            .animate-marquee {
                animation: marquee 32s linear infinite;
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

            /* Sticky pill header */
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
    <body class="page-landing min-h-screen overflow-x-clip bg-white text-zinc-900 antialiased selection:bg-[#3750eb]/30 dark:bg-zinc-950 dark:text-zinc-100">

        {{-- Global canvas animation --}}
        <canvas id="global-canvas" class="pointer-events-none fixed inset-0 -z-10" aria-hidden="true"></canvas>

        {{-- Ambient glow --}}
        <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-[36rem] glow" aria-hidden="true"></div>

        @php
            $iconPaths = [
                'bolt' => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z',
                'folder' => 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z',
                'book-open' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
                'trophy' => 'M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0',
                'shield-check' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
                'users' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
                'check-badge' => 'M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z',
                'sparkles' => 'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z',
                'arrow-right' => 'M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z',
                'arrow-up-right' => 'M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25',
                'chevron-down' => 'M19.5 8.25l-7.5 7.5-7.5-7.5',
                'bars' => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
                'x' => 'M6 18 18 6M6 6l12 12',
                'check' => 'M4.5 12.75l6 6 9-13.5',
                'star' => 'M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z',
                'arrow-trending-up' => 'M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941',
                'map-pin' => 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z',
                'academic-cap' => 'M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5',
                'code-bracket' => 'M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5',
                'rocket-launch' => 'M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z',
                'chart-bar' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
                'document-text' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 12h4.5m-4.5 3h5.25m-9.75-6h.008v.008h-.008v-.008ZM7.5 21h9a3 3 0 0 0 3-3V7.5A4.5 4.5 0 0 0 15 3H7.5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3Z',
                'funnel' => 'M6 4.5h12a1.5 1.5 0 0 1 1.06 2.56L14 12.11v5.14a1.5 1.5 0 0 1-.56 1.18l-2.88 2.16A1.5 1.5 0 0 1 8 19.35v-7.24L4.94 7.06A1.5 1.5 0 0 1 6 4.5Z',
                'magnifying-glass' => 'M21 21l-4.35-4.35M11 17a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z',
                'scale' => 'M12 3v18m0 0H5.25M12 21h6.75M12 6.75 7.5 12m4.5-5.25L16.5 12m-4.5 9v-7.5M9.75 21h4.5',
                'document-check' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75 1.5 1.5 3.75-3.75m-1.5-4.5H8.25M12 15.75h-3.75m9.375-5.25h.008v.008h-.008v-.008Zm0 3.75h.008v.008h-.008v-.008ZM15 3H7.5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h9a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3Z',
                'chat-bubble-oval-left-ellipsis' => 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z',
                'shield-exclamation' => 'M12 9v3.75m0 3.75h.008v.008H12v-.008Zm0-10.214A11.95 11.95 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286Z',
                'bell-alert' => 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5',
                'arrow-down-tray' => 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3',
            ];

            $feedIcons = [
                'project-published' => 'folder',
                'package-released' => 'archive-box',
                'problem-solved' => 'wrench-screwdriver',
                'badge-earned' => 'check-badge',
                'vouch-received' => 'shield-check',
                'article-published' => 'document-text',
                'architecture-showcase' => 'building-library',
                'learning-milestone' => 'academic-cap',
                'achievement-verified' => 'check-badge',
                'project-launch' => 'rocket-launch',
                'open-source-contribution' => 'code-bracket',
                'level-up' => 'arrow-trending-up',
                'skill-verified' => 'shield-check',
                'journal-published' => 'book-open',
                'milestone-reached' => 'arrow-trending-up',
                'evidence-added' => 'document-text',
                'evidence-analyzed' => 'sparkles',
                'verification-approved' => 'shield-check',
                'joined' => 'sparkles',
            ];

            $heroAvatars = $engineers->take(5)->values();

            $badgeSvg = fn ($icon) => $iconPaths[$feedIcons[$icon] ?? 'bolt'] ?? $iconPaths['bolt'];
        @endphp

        {{-- ===================== NAV ===================== --}}
        <header class="site-header fixed inset-x-0 top-0 z-50 border-b border-transparent">
            <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('welcome') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo-black.png') }}" alt="ProoDev" class="h-7 w-auto dark:hidden" />
                    <img src="{{ asset('images/logo-white.png') }}" alt="ProoDev" class="hidden h-7 w-auto dark:block" />
                </a>

                <div class="hidden items-center gap-1 text-sm text-zinc-500 md:flex dark:text-zinc-400">
                    <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                        <button type="button" @click="open = !open" class="flex items-center gap-1 rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">
                            Product
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5 transition-transform" :class="{ 'rotate-180': open }"><path fill-rule="evenodd" d="{{ $iconPaths['chevron-down'] }}" clip-rule="evenodd"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute left-0 top-full z-50 mt-1 w-56 rounded-xl border border-zinc-200 bg-white/95 p-1.5 shadow-xl shadow-zinc-900/10 backdrop-blur-xl dark:border-white/10 dark:bg-zinc-950/95">
                            <a href="#platform" class="block rounded-lg px-3 py-2 transition hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-white/5 dark:hover:text-white">Platform</a>
                            <a href="#jobs" class="block rounded-lg px-3 py-2 transition hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-white/5 dark:hover:text-white">Open Roles</a>
                            <a href="#globe" class="block rounded-lg px-3 py-2 transition hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-white/5 dark:hover:text-white">Global Talent</a>
                        </div>
                    </div>
                    <a href="{{ route('developers') }}" class="rounded-lg px-3 py-2 font-medium text-[#3750eb] transition hover:text-[#3750eb]/80 dark:text-[#8f9dff] dark:hover:text-[#9db8ff]">For developers</a>
                    <a href="{{ route('for-companies') }}" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">For Companies</a>
                    <a href="{{ route('for-companies').'#pricing' }}" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">Pricing</a>
                    <a href="{{ route('news.index') }}" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">News</a>
                </div>

                <div class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('home') }}" class="inline-flex items-center rounded-full bg-zinc-900 px-4 py-2 text-sm font-semibold text-white! transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900! dark:hover:bg-zinc-200">
                            Open dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 transition hover:text-zinc-900 sm:inline-block dark:text-zinc-300 dark:hover:text-white">Sign in</a>
                        <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-[#3750eb] px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-[#3750eb]/25 transition hover:opacity-90">
                            Get started
                        </a>
                    @endauth
                    <x-theme-toggle />
                    <button type="button" data-mobile-menu-toggle class="inline-flex size-9 items-center justify-center rounded-lg border border-zinc-200 text-zinc-600 md:hidden dark:border-white/10 dark:text-zinc-300" aria-label="Toggle menu">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="{{ $iconPaths['bars'] }}" clip-rule="evenodd"/></svg>
                    </button>
                </div>
            </nav>

            <div data-mobile-menu class="hidden border-t border-zinc-200 bg-white/95 px-4 py-4 md:hidden dark:border-white/5 dark:bg-zinc-950/95">
                <div class="grid gap-1 text-sm">
                    <div class="mt-1 px-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Product</div>
                    <a href="#platform" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">Platform</a>
                    <a href="#jobs" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">Open Roles</a>
                    <a href="#globe" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">Global Talent</a>
                    <div class="mt-1 px-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Get started</div>
                    <a href="{{ route('developers') }}" class="rounded-lg px-3 py-2 font-medium text-[#3750eb] transition dark:text-[#8f9dff]">For developers</a>
                    <a href="{{ route('for-companies') }}" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">For Companies</a>
                    <a href="{{ route('for-companies').'#pricing' }}" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">Pricing</a>
                    <a href="{{ route('news.index') }}" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">News</a>
                    @guest
                        <a href="{{ route('login') }}" class="mt-2 rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">Sign in</a>
                    @endguest
                </div>
            </div>
        </header>

        {{-- ===================== HERO ===================== --}}
        <section id="feed" class="relative mx-auto max-w-7xl overflow-hidden px-4 pb-16 pt-16 text-center sm:px-6 sm:pt-24 lg:px-8">
            <div class="relative mx-auto max-w-3xl animate-fade-up">
                <a href="#platform" class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white/60 px-4 py-1.5 text-xs font-medium text-zinc-600 transition hover:border-[#3750eb]/40 hover:text-zinc-900 dark:border-white/10 dark:bg-white/5 dark:text-zinc-300 dark:hover:text-white">
                    <span class="relative flex size-2">
                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-[#3750eb] opacity-60"></span>
                        <span class="relative inline-flex size-2 rounded-full bg-[#3750eb]"></span>
                    </span>
                    Proof over claims - an evidence-backed engineering identity
                </a>

                <h1 class="mt-8 text-4xl font-bold tracking-tight text-zinc-900 sm:text-6xl lg:text-7xl dark:text-white">
                    Your work. <span class="text-gradient">Proven.</span> Not claimed.
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-zinc-600 sm:text-xl dark:text-zinc-400">
                    Paste any repo, article, or project URL. AI reads the real work, drafts an engineering report,
                    and computes an explainable Engineering Magnitude score - a passport built on evidence, not noise.
                </p>

                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    @auth
                        <a href="{{ route('home') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#3750eb] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#3750eb]/25 transition hover:opacity-90 sm:w-auto">
                            <x-app-logo-icon class="size-4 fill-current" />
                            Go to your feed
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#3750eb] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#3750eb]/25 transition hover:opacity-90 sm:w-auto">
                            Start proving - it's free
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                        </a>
                        <a href="#how" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-white/60 px-6 py-3 text-sm font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-white sm:w-auto dark:border-white/10 dark:bg-white/5 dark:text-zinc-200 dark:hover:border-white/25 dark:hover:bg-white/10">
                            See how the evidence works
                        </a>
                    @endauth
                </div>

                {{-- Trust line --}}
                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
                    <div class="flex -space-x-2.5">
                        @forelse ($heroAvatars as $i => $engineer)
                            <flux:avatar :src="$engineer->avatarUrl()" :alt="$engineer->name" circle class="size-9 ring-2 ring-white dark:ring-zinc-950" />
                        @empty
                            <span class="flex size-9 items-center justify-center rounded-full bg-black text-xs font-bold text-white ring-2 ring-white dark:bg-white dark:text-black dark:ring-zinc-950">A</span>
                            <span class="flex size-9 items-center justify-center rounded-full bg-black text-xs font-bold text-white ring-2 ring-white dark:bg-white dark:text-black dark:ring-zinc-950">E</span>
                            <span class="flex size-9 items-center justify-center rounded-full bg-black text-xs font-bold text-white ring-2 ring-white dark:bg-white dark:text-black dark:ring-zinc-950">O</span>
                        @endforelse
                    </div>
                    <p class="text-sm text-zinc-500">
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $stats[0]['value'] }}</span> engineers with evidence-backed identities
                    </p>
                </div>
            </div>

            {{-- Hero product window --}}
            <div class="relative mx-auto mt-16 max-w-5xl animate-fade-up delay-200">
                <div class="pointer-events-none absolute -inset-x-8 -top-10 bottom-0 -z-10 rounded-xl bg-[#3750eb]/10 blur-3xl" aria-hidden="true"></div>

                <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white/70 shadow-2xl shadow-zinc-900/10 backdrop-blur-xl dark:border-white/10 dark:bg-zinc-950/70 dark:shadow-[#3750eb]/20">
                    {{-- Window chrome --}}
                    <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-5 py-3 dark:border-white/5">
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-full bg-[#3750eb]/60"></span>
                            <span class="size-2.5 rounded-full bg-[#5b6cff]/60"></span>
                            <span class="size-2.5 rounded-full bg-[#8f9dff]/60"></span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-zinc-500">
                            <span class="relative flex size-1.5">
                                <span class="absolute inline-flex size-full animate-ping rounded-full bg-[#3750eb] opacity-75"></span>
                                <span class="relative inline-flex size-1.5 rounded-full bg-[#3750eb]"></span>
                            </span>
                            Try it now
                        </div>
                        <div class="flex items-center gap-1 text-zinc-400 dark:text-zinc-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-up-right'] }}" clip-rule="evenodd"/></svg>
                        </div>
                    </div>

                    <div class="p-4 sm:p-6">
                        <div class="mx-auto max-w-2xl">
                            <div class="text-left">
                                <div class="text-sm font-semibold text-zinc-900 dark:text-white">Paste evidence. Get proof.</div>
                                <div class="mt-1 text-xs leading-relaxed text-zinc-500">
                                    Drop a GitHub repository or any project URL. AI fetches the source, drafts the engineering report, computes your Engineering Magnitude, and assembles the evidence - instantly.
                                </div>
                            </div>

                            <div class="mt-5 text-left">
                                <livewire:landing-scout wire:key="landing-scout" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== GLOBAL TALENT GLOBE ===================== --}}
        <section id="globe" class="relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">Global talent</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Engineers with proof, all over the world</h2>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">Live from the passport database - real developers with evidence-backed engineering identities. Drag to spin the globe, click any profile to open a passport.</p>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['label' => 'Public passports', 'value' => number_format(count($globeDevelopers))],
                        ['label' => 'Evidence-backed scores', 'value' => 'Magnitude 0-1000'],
                        ['label' => 'Verified work', 'value' => 'Repos, projects, vouches'],
                        ['label' => 'One click to recruit', 'value' => 'Passport -> apply'],
                    ] as $globeStat)
                        <div class="rounded-xl border border-zinc-200 bg-white p-4 text-center dark:border-white/10 dark:bg-white/[0.03]">
                            <div class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $globeStat['value'] }}</div>
                            <div class="mt-0.5 text-xs font-medium uppercase tracking-wider text-zinc-500">{{ $globeStat['label'] }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="relative mt-10">
                    <div class="pointer-events-none absolute inset-0 -z-10 rounded-full bg-[#3750eb]/10 blur-3xl" aria-hidden="true"></div>
                    <div class="relative w-full overflow-hidden rounded-xl">
                        <canvas id="talent-globe" class="block size-full aspect-[3/2] cursor-grab active:cursor-grabbing sm:aspect-[16/9]" aria-label="3D globe of developers"></canvas>

                        <div id="globe-tooltip" class="absolute z-20 hidden w-72 -translate-x-1/2 rounded-lg border border-zinc-200 bg-white/95 p-4 shadow-2xl shadow-zinc-900/20 backdrop-blur-xl dark:border-white/10 dark:bg-zinc-900/95 dark:shadow-black/40" data-tooltip-interactive>
                            <div class="flex items-center gap-3">
                                <img id="globe-tip-avatar" src="" alt="" class="size-10 shrink-0 rounded-full ring-2 ring-[#3750eb]/40" />
                                <div class="min-w-0">
                                    <div id="globe-tip-name" class="truncate text-sm font-semibold text-zinc-900 dark:text-white"></div>
                                    <div id="globe-tip-location" class="truncate text-xs text-zinc-500"></div>
                                </div>
                            </div>
                            <p id="globe-tip-headline" class="mt-2 line-clamp-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400"></p>
                            <div class="mt-3 flex items-center justify-between">
                                <span id="globe-tip-score" class="inline-flex items-center gap-1 rounded-full bg-[#3750eb]/10 px-2 py-0.5 text-xs font-semibold text-[#3750eb] dark:text-[#8f9dff]"></span>
                                <a id="globe-tip-link" href="#" class="inline-flex items-center gap-1 text-xs font-semibold text-[#3750eb] transition hover:gap-2 dark:text-[#8f9dff]">
                                    View passport
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-up-right'] }}" clip-rule="evenodd"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 text-center text-xs text-zinc-500">Drag to rotate - Scroll or pinch to zoom - Click a profile for a passport summary</p>
                </div>

                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('jobs.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#3750eb] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#3750eb]/25 transition hover:opacity-90">
                        Browse open roles
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                    </a>
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl border border-zinc-200 bg-white/60 px-5 py-2.5 text-sm font-semibold text-zinc-700 transition hover:bg-white dark:border-white/10 dark:bg-white/5 dark:text-zinc-200 dark:hover:bg-white/10">
                        Get on the map
                    </a>
                </div>
            </div>
        </section>

        {{-- ===================== OPEN ROLES ===================== --}}
        <section id="jobs" class="relative overflow-hidden border-t border-zinc-200 bg-zinc-50 dark:border-white/5 dark:bg-white/[0.02]">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-end">
                    <div class="max-w-2xl">
                        <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">Open roles</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Hire engineers who prove their work</h2>
                        <p class="mt-4 text-zinc-600 dark:text-zinc-400">Every role on ProoDev is posted by a company, and every candidate carries an evidence-backed passport. No blind resumes.</p>
                    </div>
                    <a href="{{ route('jobs.index') }}" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-[#3750eb] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#3750eb]/25 transition hover:opacity-90">
                        Browse all roles
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                    </a>
                </div>

                <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @forelse ($openJobs as $job)
                        @php
                            $jobLocation = trim(($job->location ?? '') . ($job->is_remote ? ' - Remote' : ''));
                            $jobSalary = $job->salaryRange();
                            $jobType = $job->employment_type;
                            $jobLogo = $job->company?->logoUrl();
                            $jobCompany = $job->company?->name;
                        @endphp
                        <a href="{{ route('jobs.show', ['company' => $job->company, 'job' => $job]) }}" class="group relative flex flex-col rounded-lg border border-zinc-200 bg-white p-4 transition duration-300 hover:-translate-y-1 hover:border-[#3750eb]/50 hover:shadow-xl hover:shadow-[#3750eb]/10 dark:border-white/10 dark:bg-zinc-950/60 dark:hover:border-[#3750eb]/30 dark:hover:bg-white/[0.05]">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-11 items-center justify-center overflow-hidden rounded-xl bg-[#3750eb]/10 ring-1 ring-zinc-200 dark:ring-white/10">
                                        @if ($jobLogo)
                                            <img src="{{ $jobLogo }}" alt="" class="size-full object-cover" />
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 text-[#3750eb]"><path fill-rule="evenodd" d="{{ $iconPaths['folder'] }}" clip-rule="evenodd"/></svg>
                                        @endif
                                    </span>
                                    <div>
                                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $job->title }}</div>
                                        <div class="text-xs text-zinc-500">{{ $jobCompany }}</div>
                                    </div>
                                </div>
                                @if ($job->is_remote)
                                    <span class="shrink-0 rounded-full bg-[#3750eb]/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-[#3750eb] dark:text-[#8f9dff]">Remote</span>
                                @endif
                            </div>

                            <p class="mt-4 line-clamp-2 flex-1 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ Str::limit(strip_tags($job->description), 140) }}</p>

                            <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-zinc-500">
                                @if ($jobLocation)
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-3.5 text-[#3750eb]/70"><path fill-rule="evenodd" d="{{ $iconPaths['map-pin'] }}" clip-rule="evenodd"/></svg>
                                        {{ $jobLocation }}
                                    </span>
                                @endif
                                @if ($jobSalary)
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $jobSalary }}</span>
                                @endif
                                @if ($jobType)
                                    <span class="rounded-md border border-zinc-200 px-2 py-0.5 dark:border-white/10">{{ $jobType }}</span>
                                @endif
                                <span class="ml-auto inline-flex items-center gap-1 font-medium text-[#3750eb] transition group-hover:gap-2 dark:text-[#8f9dff]">
                                    View role
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                                </span>
                            </div>
                        </a>
                    @empty
                        @foreach ([
                            ['Senior Backend Engineer', 'Stripe', '$140k - $180k', 'Remote'],
                            ['Staff Frontend Engineer', 'Vercel', '$150k - $200k', 'San Francisco, CA'],
                            ['Platform Engineer', 'Railway', '$130k - $170k', 'Remote'],
                        ] as $placeholder)
                            <div class="flex flex-col rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-950/60">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-11 items-center justify-center rounded-xl bg-[#3750eb]/10 ring-1 ring-zinc-200 dark:ring-white/10">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 text-[#3750eb]"><path fill-rule="evenodd" d="{{ $iconPaths['folder'] }}" clip-rule="evenodd"/></svg>
                                    </span>
                                    <div>
                                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $placeholder[0] }}</div>
                                        <div class="text-xs text-zinc-500">{{ $placeholder[1] }}</div>
                                    </div>
                                </div>
                                <div class="mt-4 flex-1 text-sm text-zinc-500">Be the first company to post an engineering role - matched to engineers who can prove it.</div>
                                <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-zinc-500">
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $placeholder[2] }}</span>
                                    <span>{{ $placeholder[3] }}</span>
                                </div>
                            </div>
                        @endforeach
                    @endforelse
                </div>

                <div class="mt-10 flex flex-col items-center justify-center gap-3 text-center sm:flex-row">
                    <p class="text-sm text-zinc-500">Have a role to fill?</p>
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#3750eb] transition hover:gap-2.5 dark:text-[#8f9dff]">
                        Post a job free - no subscription required
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                    </a>
                </div>
            </div>
        </section>

        {{-- ===================== ALL-IN-ONE / INTERACTIVE CARDS ===================== --}}
        <section id="platform" class="relative overflow-hidden border-t border-zinc-200 bg-zinc-50 dark:border-white/5 dark:bg-white/[0.02]">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">All-in-one</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">One platform for your whole engineering identity</h2>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">Everything below is live from ProoDev - real evidence analyzed by AI, real projects, real magnitude, real reputation.</p>
                </div>

                <div class="mt-14 grid gap-5 lg:grid-cols-3">
                    @foreach ($features as $feature)
                        <div class="group relative flex flex-col overflow-hidden rounded-lg border border-zinc-200 bg-white transition duration-300 hover:-translate-y-1 hover:border-[#3750eb]/40 hover:shadow-2xl hover:shadow-[#3750eb]/10 dark:border-white/10 dark:bg-white/[0.03] dark:hover:bg-white/[0.06]">
                            <div class="border-b border-zinc-200 p-4 dark:border-white/5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <span class="flex size-11 items-center justify-center rounded-xl bg-[#3750eb]/10 text-[#3750eb] ring-1 ring-zinc-200 transition group-hover:text-[#2f45c7] dark:text-[#8f9dff] dark:ring-white/10 dark:group-hover:text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6"><path fill-rule="evenodd" clip-rule="evenodd" d="{{ $iconPaths[$feature['icon']] }}" /></svg>
                                        </span>
                                        <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">{{ $feature['title'] }}</h3>
                                        <p class="mt-1.5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $feature['description'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="min-h-44 flex-1 p-5">
                                @if ($feature['key'] === 'feed')
                                    <div class="grid gap-2.5">
                                        @forelse ($feed->take(4) as $event)
                                            <div class="flex items-center gap-2.5 rounded-lg border border-zinc-100 bg-zinc-50 p-2.5 dark:border-white/5 dark:bg-zinc-950/40">
                                                <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-[#3750eb]/10 text-[#3750eb] dark:text-[#8f9dff]">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" clip-rule="evenodd" d="{{ $badgeSvg($event->type->value) }}" /></svg>
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <div class="truncate text-xs font-medium text-zinc-800 dark:text-zinc-100">{{ $event->title }}</div>
                                                    <div class="flex items-center gap-1 truncate text-[11px] text-zinc-500">{{ $event->user?->name }} <x-verified-badge :user="$event->user" compact /> - {{ $event->type->label() }}</div>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-sm text-zinc-500">No activity yet.</p>
                                        @endforelse
                                    </div>

                                @elseif ($feature['key'] === 'evidence')
                                    <div class="grid gap-2.5">
                                        @forelse ($evidence as $item)
                                            <div class="rounded-lg border border-zinc-100 bg-zinc-50 p-3 dark:border-white/5 dark:bg-zinc-950/40">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="min-w-0 truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $item->title }}</div>
                                                    <span class="shrink-0 rounded-full bg-[#3750eb]/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-[#3750eb] dark:text-[#8f9dff]">{{ $item->type->label() }}</span>
                                                </div>
                                                @if ($item->source)
                                                    <div class="mt-0.5 line-clamp-1 text-xs text-zinc-500">{{ $item->source }}</div>
                                                @endif
                                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                                    @if ($item->ai_score !== null)
                                                        <span class="rounded-full bg-[#3750eb]/10 px-2 py-0.5 text-[10px] font-semibold text-[#3750eb] dark:text-[#8f9dff]">Magnitude {{ number_format($item->ai_score) }}</span>
                                                    @endif
                                                    <span class="ml-auto text-[11px] text-zinc-500">{{ $item->created_at?->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-sm text-zinc-500">No evidence analyzed yet. Paste your first URL.</p>
                                        @endforelse
                                    </div>

                                @elseif ($feature['key'] === 'projects')
                                    <div class="grid gap-2.5">
                                        @forelse ($projects as $project)
                                            <div class="rounded-lg border border-zinc-100 bg-zinc-50 p-3 dark:border-white/5 dark:bg-zinc-950/40">
                                                <div class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $project->title }}</div>
                                                @if ($project->tagline)
                                                    <div class="mt-0.5 line-clamp-1 text-xs text-zinc-500">{{ \App\Support\Markdown::plain($project->tagline) }}</div>
                                                @endif
                                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                                    @foreach (collect($project->tech_stack ?? [])->take(3) as $tech)
                                                        <span class="rounded-full bg-[#3750eb]/10 px-2 py-0.5 text-[10px] font-medium text-[#3750eb] dark:text-[#8f9dff]">{{ $tech }}</span>
                                                    @endforeach
                                                    <span class="ml-auto inline-flex items-center gap-1 text-[11px] text-zinc-500">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-3"><path fill-rule="evenodd" d="{{ $iconPaths['users'] }}" /></svg>
                                                        {{ $project->recognition_count }}
                                                    </span>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-sm text-zinc-500">No projects shipped yet.</p>
                                        @endforelse
                                    </div>

                                @elseif ($feature['key'] === 'journal')
                                    <div class="grid gap-2.5">
                                        @forelse ($journal as $entry)
                                            <div class="rounded-lg border border-zinc-100 bg-zinc-50 p-3 dark:border-white/5 dark:bg-zinc-950/40">
                                                <div class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $entry->title ?: 'Untitled entry' }}</div>
                                                <div class="mt-0.5 line-clamp-2 text-xs text-zinc-500">{{ Str::limit(strip_tags($entry->content), 120) }}</div>
                                                <div class="mt-1.5 flex items-center gap-1.5 text-[11px] text-zinc-500">
                                                    <flux:avatar :src="$entry->user?->avatarUrl() ?? ''" circle class="size-4" />
                                                    {{ $entry->user?->name }}
                                                    <x-verified-badge :user="$entry->user" compact />
                                                    <span class="ml-auto">{{ $entry->published_at?->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-sm text-zinc-500">No journal entries yet.</p>
                                        @endforelse
                                    </div>

                                @elseif ($feature['key'] === 'reputation')
                                    <div class="grid gap-2.5">
                                        @forelse ($engineers->take(5) as $index => $engineer)
                                            <div class="flex items-center gap-2.5 rounded-lg border border-zinc-100 bg-zinc-50 p-2.5 dark:border-white/5 dark:bg-zinc-950/40">
                                                <flux:avatar :src="$engineer->avatarUrl()" :alt="$engineer->name" circle class="size-7 shrink-0" />
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-1.5">
                                                        <div class="truncate text-xs font-medium text-zinc-800 dark:text-zinc-100">{{ $engineer->name }}</div>
                                                        <x-verified-badge :user="$engineer" compact />
                                                    </div>
                                                    <div class="truncate text-[11px] text-zinc-500">{{ $engineer->levelTitle() }} - {{ $engineer->location ?: 'Building in public' }}</div>
                                                </div>
                                                <span class="inline-flex items-center gap-1 text-xs font-bold text-[#3750eb] dark:text-[#8f9dff]">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-3"><path fill-rule="evenodd" d="{{ $iconPaths['shield-check'] }}" /></svg>
                                                    {{ number_format($engineer->reputation_score) }}
                                                </span>
                                            </div>
                                        @empty
                                            <p class="text-sm text-zinc-500">No reputations yet.</p>
                                        @endforelse
                                    </div>
                                @endif
                            </div>

                            <div class="border-t border-zinc-200 p-4 dark:border-white/5">
                                <a href="{{ $feature['href'] }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-[#3750eb] transition group-hover:gap-2.5 dark:text-[#8f9dff]">
                                    Explore {{ strtolower($feature['title']) }}
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===================== SOLUTIONS MARQUEE ===================== --}}
        <section class="relative overflow-hidden border-t border-zinc-200 py-16 dark:border-white/5">
            @php
                $marqueeItems = ['Evidence Library', 'AI Engineering Reports', 'Engineering Magnitude', 'Projects', 'Engineering Journal', 'Passport', 'Reputation', 'Discovery', 'Vouches', 'Verifications', 'Skills Mapping', 'Open Source'];
            @endphp
            <div class="relative overflow-hidden">
                <div class="pointer-events-none absolute inset-y-0 left-0 z-10 w-24 bg-gradient-to-r from-white to-transparent dark:from-zinc-950" aria-hidden="true"></div>
                <div class="pointer-events-none absolute inset-y-0 right-0 z-10 w-24 bg-gradient-to-l from-white to-transparent dark:from-zinc-950" aria-hidden="true"></div>
                <div class="flex w-max animate-marquee items-center gap-12">
                    @for ($i = 0; $i < 2; $i++)
                        @foreach ($marqueeItems as $item)
                            <div class="flex items-center gap-3 text-sm font-semibold uppercase tracking-widest text-zinc-500 dark:text-zinc-600">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-3 text-[#3750eb]/70"><path fill-rule="evenodd" d="{{ $iconPaths['sparkles'] }}" /></svg>
                                {{ $item }}
                            </div>
                        @endforeach
                    @endfor
                </div>
            </div>
        </section>

        {{-- ===================== QUALITY & VETTING ===================== --}}
        <section class="relative overflow-hidden border-t border-zinc-200 bg-zinc-50 dark:border-white/5 dark:bg-white/[0.02]">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="grid items-start gap-12 lg:grid-cols-2">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">Trust & quality</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">An identity that can't be faked</h2>
                        <p class="mt-4 text-zinc-600 dark:text-zinc-400">Reputation isn't self-reported. Every point on your passport is anchored to evidence - real code AI has read, projects you've shipped, and vouches from engineers who actually know your work.</p>

                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            @foreach ([
                                ['icon' => 'document-text', 'title' => 'Evidence-first', 'text' => 'Every claim points to a repo, article, or project AI has actually read.'],
                                ['icon' => 'sparkles', 'title' => 'AI-analyzed', 'text' => 'Engineering reports are drafted by AI from the source material itself.'],
                                ['icon' => 'shield-check', 'title' => 'Vouch-weighted', 'text' => 'Endorsements are weighted by the giver\'s own proven track record.'],
                                ['icon' => 'check-badge', 'title' => 'Explainable magnitude', 'text' => 'A 0-1000 score broken down factor by factor, tied to real evidence.'],
                            ] as $quality)
                                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-white/[0.03]">
                                    <span class="flex size-9 items-center justify-center rounded-lg bg-[#3750eb]/10 text-[#3750eb] ring-1 ring-zinc-200 dark:text-[#8f9dff] dark:ring-white/10">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="{{ $iconPaths[$quality['icon']] }}" /></svg>
                                    </span>
                                    <h3 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-white">{{ $quality['title'] }}</h3>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $quality['text'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-zinc-950/60">
                        <div class="grid grid-cols-2 gap-px overflow-hidden rounded-xl bg-zinc-200 dark:bg-white/10">
                            @foreach ($stats as $stat)
                                <div class="bg-white p-4 text-center dark:bg-zinc-950/90">
                                    <div class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $stat['value'] }}</div>
                                    <div class="mt-1 text-xs font-medium uppercase tracking-wider text-zinc-500">{{ $stat['label'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-8 grid gap-4">
                            @foreach ([
                                'Every piece of evidence is analyzed by AI from the actual source',
                                'Engineering Magnitude is explainable, factor by factor, from 0-1000',
                                'Vouches, verifications, and public work are all anchored to evidence',
                            ] as $bullet)
                                <div class="flex items-start gap-3 text-sm text-zinc-700 dark:text-zinc-300">
                                    <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-[#3750eb]/10 text-[#3750eb]">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3"><path fill-rule="evenodd" d="{{ $iconPaths['check'] }}" clip-rule="evenodd"/></svg>
                                    </span>
                                    {{ $bullet }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== HOW IT WORKS ===================== --}}
        <section id="how" class="relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">How it works</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">From first piece of evidence to public proof</h2>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">Four steps. No noise. Your work does the talking.</p>
                </div>

                <div class="mt-14 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                    @foreach ($steps as $step)
                        <div class="group relative rounded-lg border border-zinc-200 bg-white p-4 transition duration-300 hover:-translate-y-1 hover:border-[#3750eb]/50 hover:shadow-xl hover:shadow-[#3750eb]/10 dark:border-white/10 dark:bg-zinc-950/60 dark:hover:border-[#3750eb]/30 dark:hover:bg-white/[0.04]">
                            <span class="bg-[#3750eb]/15 text-[#3750eb] dark:text-[#8f9dff] inline-flex items-center rounded-full px-3 py-1 text-xs font-bold tracking-widest ring-1 ring-zinc-200 dark:ring-white/10">{{ $step['number'] }}</span>
                            <h3 class="mt-5 text-lg font-semibold text-zinc-900 dark:text-white">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $step['description'] }}</p>
                            <a href="{{ $step['href'] }}" class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-[#3750eb] transition group-hover:gap-2 dark:text-[#8f9dff]">
                                Start here
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===================== TESTIMONIALS ===================== --}}
        <section class="relative overflow-hidden border-t border-zinc-200 bg-zinc-50 dark:border-white/5 dark:bg-white/[0.02]">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">Vouches</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Endorsements from engineers who know</h2>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">Real vouches from the community - weighted by each giver's proven track record and anchored to evidence.</p>
                </div>

                <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($vouches as $vouch)
                        <figure class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-950/60">
                            <div class="flex gap-1 text-[#3750eb]">
                                @for ($i = 0; $i < 5; $i++)
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['star'] }}" /></svg>
                                @endfor
                            </div>
                            <blockquote class="mt-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">"{{ $vouch->message ?: 'Vouched for their engineering.' }}"</blockquote>
                            <figcaption class="mt-5 flex items-center gap-3">
                                                <flux:avatar :src="$vouch->voucher?->avatarUrl() ?? ''" circle class="size-9" />
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $vouch->voucher?->name }}</div>
                                        <x-verified-badge :user="$vouch->voucher" compact />
                                    </div>
                                    <div class="truncate text-xs text-zinc-500">vouched for {{ $vouch->vouchee?->name }} - {{ $vouch->type->label() }}@if ($vouch->skill) - {{ $vouch->skill->name }}@endif</div>
                                </div>
                            </figcaption>
                        </figure>
                    @empty
                        @foreach ([[], [], []] as $i => $skeleton)
                            <figure class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-950/60">
                                <div class="flex gap-1 text-[#3750eb]/60">
                                    @for ($j = 0; $j < 5; $j++)
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['star'] }}" /></svg>
                                    @endfor
                                </div>
                                <blockquote class="mt-4 text-sm italic leading-relaxed text-zinc-500">Be the first to vouch for an engineer you trust.</blockquote>
                                <figcaption class="mt-5 flex items-center gap-3">
                                    <span class="flex size-9 items-center justify-center rounded-full bg-gradient-to-br from-zinc-700 to-zinc-600 text-xs font-bold text-white">?</span>
                                    <div class="text-sm text-zinc-500">Your name here</div>
                                </figcaption>
                            </figure>
                        @endforeach
                    @endforelse
                </div>
            </div>
        </section>

        {{-- ===================== FAQ ===================== --}}
        <section class="relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">FAQ</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Everything you need to know</h2>
                </div>

                <div class="mt-12 grid gap-3">
                    @foreach ($faqs as $index => $faq)
                        <div data-faq class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/[0.03]">
                            <button type="button" data-faq-toggle class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left">
                                <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $faq['question'] }}</span>
                                <svg data-faq-chevron xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 shrink-0 text-zinc-500 transition duration-200">
                                    <path fill-rule="evenodd" d="{{ $iconPaths['chevron-down'] }}" />
                                </svg>
                            </button>
                            <div data-faq-answer class="max-h-0 px-5 transition-all duration-300 ease-in-out">
                                <p class="pb-5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $faq['answer'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===================== CTA ===================== --}}
        <section class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-gradient-to-br from-[#f1f4ff] via-white to-[#eef1ff] px-6 py-16 text-center sm:px-16 dark:border-white/10 dark:from-[#3750eb]/25 dark:via-zinc-900 dark:to-[#3750eb]/10">
                <div class="relative">
                    <h2 class="mx-auto max-w-2xl text-3xl font-bold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                        Ready to prove your work?
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-zinc-700 dark:text-zinc-300">
                        Add your first piece of evidence and let AI build the report that shows what you're actually capable of.
                    </p>
                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        @auth
                            <a href="{{ route('home') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-zinc-900 px-6 py-3 text-sm font-semibold text-white! transition hover:bg-zinc-700 sm:w-auto dark:bg-white dark:text-zinc-900! dark:hover:bg-zinc-200">
                                Go to your feed
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-zinc-900 px-6 py-3 text-sm font-semibold text-white! transition hover:bg-zinc-700 sm:w-auto dark:bg-white dark:text-zinc-900! dark:hover:bg-zinc-200">
                                Create your free account
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-zinc-300 bg-white/60 px-6 py-3 text-sm font-semibold text-zinc-700 transition hover:bg-white sm:w-auto dark:border-white/20 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                                I already have an account
                            </a>
                        @endauth
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
                    <a href="{{ route('news.index') }}" class="transition hover:text-zinc-900 dark:hover:text-white">News</a>
                    @auth
                        <a href="{{ route('home') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Dashboard</a>
                        <a href="{{ route('jobs.index') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Open Roles</a>
                    @else
                        <a href="{{ route('login') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Sign in</a>
                        <a href="{{ route('register') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Register</a>
                    @endauth
                </div>
            </div>
            <div class="relative mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 border-t border-zinc-200 px-4 py-5 sm:flex-row sm:px-6 lg:px-8 dark:border-white/5">
                <p class="text-xs text-zinc-400">Built for engineers who back their claims with evidence.</p>
                <div class="flex items-center gap-5 text-xs text-zinc-500">
                    <a href="{{ route('privacy') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Privacy Policy</a>
                    <a href="{{ route('terms') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Terms &amp; Conditions</a>
                    <a href="{{ route('cookies') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Cookie Policy</a>
                </div>
            </div>
        </footer>

        <script>
            (function () {
                'use strict';

                var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                var canvas = document.getElementById('global-canvas');
                if (!canvas) return;

                var ctx = canvas.getContext('2d');
                var dpr = Math.min(window.devicePixelRatio || 1, 1.5);
                var width, height;
                var particles = [];
                var mouse = { x: -9999, y: -9999 };
                var COLORS = ['55,80,235', '91,108,255', '143,157,255'];

                function resize() {
                    width = window.innerWidth;
                    height = window.innerHeight;
                    canvas.width = width * dpr;
                    canvas.height = height * dpr;
                    canvas.style.width = width + 'px';
                    canvas.style.height = height + 'px';
                    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                    spawn();
                }

                function spawn() {
                    var count = Math.min(110, Math.floor((width * height) / 16000));
                    particles = [];
                    for (var i = 0; i < count; i++) {
                        particles.push(makeParticle(true));
                    }
                }

                function makeParticle(randomize) {
                    return {
                        x: randomize ? Math.random() * width : width * 0.5,
                        y: randomize ? Math.random() * height : height * 0.5,
                        vx: (Math.random() - 0.5) * 0.45,
                        vy: (Math.random() - 0.5) * 0.45,
                        r: Math.random() * 1.8 + 0.6,
                        c: COLORS[Math.floor(Math.random() * COLORS.length)]
                    };
                }

                function draw() {
                    ctx.clearRect(0, 0, width, height);
                    var linkDist = 130;

                    for (var i = 0; i < particles.length; i++) {
                        var p = particles[i];

                        p.x += p.vx;
                        p.y += p.vy;

                        if (p.x < -20) p.x = width + 20;
                        if (p.x > width + 20) p.x = -20;
                        if (p.y < -20) p.y = height + 20;
                        if (p.y > height + 20) p.y = -20;

                        var dx = p.x - mouse.x;
                        var dy = p.y - mouse.y;
                        var distToMouse = Math.sqrt(dx * dx + dy * dy);
                        if (distToMouse < 140) {
                            p.x += (dx / distToMouse) * 0.6;
                            p.y += (dy / distToMouse) * 0.6;
                        }

                        ctx.beginPath();
                        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                        ctx.fillStyle = 'rgba(' + p.c + ',0.7)';
                        ctx.fill();

                        for (var j = i + 1; j < particles.length; j++) {
                            var q = particles[j];
                            var ddx = p.x - q.x;
                            var ddy = p.y - q.y;
                            var d = ddx * ddx + ddy * ddy;
                            if (d < linkDist * linkDist) {
                                var alpha = (1 - Math.sqrt(d) / linkDist) * 0.16;
                                ctx.beginPath();
                                ctx.moveTo(p.x, p.y);
                                ctx.lineTo(q.x, q.y);
                                ctx.strokeStyle = 'rgba(55,80,235,' + alpha + ')';
                                ctx.lineWidth = 1;
                                ctx.stroke();
                            }
                        }
                    }

                    if (!prefersReduced) {
                        window.requestAnimationFrame(draw);
                    }
                }

                window.addEventListener('resize', resize);

                window.addEventListener('pointermove', function (e) {
                    mouse.x = e.clientX;
                    mouse.y = e.clientY;
                });

                window.addEventListener('pointerleave', function () {
                    mouse.x = -9999;
                    mouse.y = -9999;
                });

                resize();
                draw();
            })();

            (function () {
                var header = document.querySelector('.site-header');
                if (!header) return;
                function onScroll() {
                    if (window.scrollY > 8) {
                        header.classList.add('is-scrolled');
                    } else {
                        header.classList.remove('is-scrolled');
                    }
                }
                window.addEventListener('scroll', onScroll, { passive: true });
                onScroll();
            })();

            (function () {
                var toggle = document.querySelector('[data-mobile-menu-toggle]');
                var menu = document.querySelector('[data-mobile-menu]');
                if (!toggle || !menu) return;
                toggle.addEventListener('click', function () {
                    menu.classList.toggle('hidden');
                });
            })();

                        (function () {
                'use strict';

                var canvas = document.getElementById('talent-globe');
                var tooltip = document.getElementById('globe-tooltip');
                if (!canvas || !tooltip) return;

                var developers = @json($globeDevelopers);
                if (!developers.length) return;

                var ctx = canvas.getContext('2d');
                var dpr = Math.min(window.devicePixelRatio || 1, 2);
                var width = 0, height = 0;
                var cx = 0, cy = 0, R = 0;
                var yaw = -0.6, pitch = 0.42;
                var zoom = 1.25;
                var dragging = false;
                var moved = false;
                var lastX = 0, lastY = 0;
                var hoverId = -1;
                var pinnedIndex = -1;
                var autoRotate = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                var points = [];
                var pointers = new Map();
                var pinchDist = 0;
                var cosY = Math.cos(yaw), sinY = Math.sin(yaw);
                var cosP = Math.cos(pitch), sinP = Math.sin(pitch);

                // Dot-matrix land data (lat/lng in tenths of a degree, '|'-separated)
                var LAND_DOTS = '885,1785|855,1785|825,-345|825,1785|795,-945|795,-915|795,-855|795,-825|795,-795|795,-765|795,-735|795,-705|795,-645|795,-615|795,-585|795,-555|795,-525|795,-495|795,-465|795,-435|795,-405|795,-375|795,-345|795,-315|795,-285|795,-255|795,-225|795,-195|795,495|795,945|795,1785|765,-1185|765,-1155|765,-855|765,-825|765,-795|765,-675|765,-645|765,-615|765,-585|765,-555|765,-525|765,-495|765,-465|765,-435|765,-405|765,-375|765,-345|765,-315|765,-285|765,-255|765,-225|765,-195|765,-105|765,165|765,225|765,885|765,1005|765,1035|765,1785|735,-1785|735,-1215|735,-1125|735,-555|735,-525|735,-495|735,-465|735,-435|735,-405|735,-375|735,-345|735,-315|735,-285|735,-255|735,-225|735,-195|735,555|735,585|735,615|735,645|735,705|735,735|735,765|735,795|735,825|735,855|735,885|735,915|735,945|735,975|735,1005|735,1035|735,1065|735,1095|735,1125|735,1245|735,1275|735,1305|735,1335|735,1365|735,1395|735,1455|735,1485|735,1515|735,1545|735,1785|705,-1785|705,-1545|705,-1515|705,-1215|705,-1155|705,-1125|705,-1095|705,-1065|705,-945|705,-885|705,-855|705,-825|705,-795|705,-765|705,-735|705,-525|705,-495|705,-465|705,-435|705,-405|705,-375|705,-345|705,-315|705,-285|705,-255|705,-225|705,525|705,555|705,675|705,705|705,735|705,765|705,795|705,825|705,855|705,885|705,915|705,945|705,975|705,1005|705,1035|705,1065|705,1095|705,1125|705,1155|705,1185|705,1215|705,1245|705,1275|705,1305|705,1335|705,1365|705,1395|705,1425|705,1455|705,1485|705,1515|705,1545|705,1575|705,1605|705,1785|675,-1785|675,-1635|675,-1605|675,-1575|675,-1545|675,-1515|675,-1485|675,-1455|675,-1425|675,-1395|675,-1365|675,-1335|675,-1305|675,-1275|675,-1245|675,-1215|675,-1185|675,-1155|675,-1125|675,-1065|675,-975|675,-945|675,-915|675,-885|675,-855|675,-825|675,-735|675,-705|675,-675|675,-525|675,-495|675,-465|675,-435|675,-405|675,-375|675,-345|675,-315|675,-285|675,165|675,195|675,225|675,255|675,285|675,315|675,345|675,375|675,435|675,465|675,495|675,525|675,555|675,585|675,615|675,645|675,675|675,705|675,735|675,765|675,795|675,825|675,855|675,885|675,915|675,945|675,975|675,1005|675,1035|675,1065|675,1095|675,1125|675,1155|675,1185|675,1215|675,1245|675,1275|675,1305|675,1335|675,1365|675,1395|675,1425|675,1455|675,1485|675,1515|675,1545|675,1575|675,1605|675,1635|675,1665|675,1695|675,1725|675,1755|675,1785|645,-1785|645,-1725|645,-1665|645,-1635|645,-1605|645,-1575|645,-1545|645,-1515|645,-1485|645,-1455|645,-1425|645,-1395|645,-1365|645,-1335|645,-1305|645,-1275|645,-1245|645,-1185|645,-1155|645,-1125|645,-1095|645,-1065|645,-1035|645,-1005|645,-975|645,-945|645,-915|645,-885|645,-855|645,-765|645,-735|645,-705|645,-675|645,-645|645,-525|645,-495|645,-465|645,-435|645,-405|645,-225|645,-195|645,-165|645,135|645,165|645,195|645,255|645,285|645,315|645,345|645,405|645,435|645,465|645,495|645,525|645,555|645,585|645,615|645,645|645,675|645,705|645,735|645,765|645,795|645,825|645,855|645,885|645,915|645,945|645,975|645,1005|645,1035|645,1065|645,1095|645,1125|645,1155|645,1185|645,1215|645,1245|645,1275|645,1305|645,1335|645,1365|645,1395|645,1425|645,1455|645,1485|645,1515|645,1545|645,1575|645,1605|645,1635|645,1665|645,1695|645,1725|645,1755|645,1785|615,-1635|615,-1605|615,-1575|615,-1545|615,-1515|615,-1485|615,-1455|615,-1425|615,-1395|615,-1365|615,-1335|615,-1305|615,-1275|615,-1245|615,-1215|615,-1185|615,-1155|615,-1125|615,-1095|615,-1065|615,-1035|615,-1005|615,-975|615,-945|615,-795|615,-765|615,-735|615,-675|615,-495|615,-465|615,-435|615,-75|615,75|615,105|615,135|615,165|615,225|615,255|615,285|615,315|615,345|615,375|615,405|615,435|615,465|615,495|615,525|615,555|615,585|615,615|615,645|615,675|615,705|615,735|615,765|615,795|615,825|615,855|615,885|615,915|615,945|615,975|615,1005|615,1035|615,1065|615,1095|615,1125|615,1155|615,1185|615,1215|615,1245|615,1275|615,1305|615,1335|615,1365|615,1395|615,1425|615,1455|615,1485|615,1515|615,1545|615,1575|615,1605|615,1635|615,1665|615,1695|615,1725|615,1785|585,-1605|585,-1575|585,-1545|585,-1365|585,-1335|585,-1305|585,-1275|585,-1245|585,-1215|585,-1185|585,-1155|585,-1125|585,-1095|585,-1065|585,-1035|585,-1005|585,-975|585,-945|585,-765|585,-735|585,-705|585,-645|585,75|585,105|585,135|585,165|585,195|585,225|585,255|585,285|585,315|585,345|585,375|585,405|585,435|585,465|585,495|585,525|585,555|585,585|585,615|585,645|585,675|585,705|585,735|585,765|585,795|585,825|585,855|585,885|585,915|585,945|585,975|585,1005|585,1035|585,1065|585,1095|585,1125|585,1155|585,1185|585,1215|585,1245|585,1275|585,1305|585,1335|585,1365|585,1395|585,1425|585,1515|585,1605|585,1635|585,1785|555,-1605|555,-1335|555,-1305|555,-1275|555,-1245|555,-1215|555,-1185|555,-1155|555,-1125|555,-1095|555,-1065|555,-1035|555,-1005|555,-975|555,-945|555,-915|555,-885|555,-855|555,-795|555,-765|555,-735|555,-705|555,-675|555,-645|555,-615|555,-45|555,105|555,135|555,225|555,255|555,285|555,315|555,345|555,375|555,405|555,435|555,465|555,495|555,525|555,555|555,585|555,615|555,645|555,675|555,705|555,735|555,765|555,795|555,825|555,855|555,885|555,915|555,945|555,975|555,1005|555,1035|555,1065|555,1095|555,1125|555,1155|555,1185|555,1215|555,1245|555,1275|555,1305|555,1335|555,1365|555,1545|555,1575|555,1605|555,1785|525,-1275|525,-1245|525,-1215|525,-1185|525,-1155|525,-1125|525,-1095|525,-1065|525,-1035|525,-1005|525,-975|525,-945|525,-915|525,-885|525,-855|525,-825|525,-795|525,-765|525,-735|525,-705|525,-675|525,-645|525,-615|525,-585|525,-555|525,-75|525,-45|525,-15|525,15|525,45|525,75|525,105|525,135|525,165|525,195|525,225|525,255|525,285|525,315|525,345|525,375|525,405|525,435|525,465|525,495|525,525|525,555|525,585|525,615|525,645|525,675|525,705|525,735|525,765|525,795|525,825|525,855|525,885|525,915|525,945|525,975|525,1005|525,1035|525,1065|525,1095|525,1125|525,1155|525,1185|525,1215|525,1245|525,1275|525,1305|525,1335|525,1365|525,1395|525,1425|525,1575|525,1785|495,-1245|495,-1215|495,-1185|495,-1155|495,-1125|495,-1095|495,-1065|495,-1035|495,-1005|495,-975|495,-945|495,-915|495,-885|495,-855|495,-825|495,-795|495,-765|495,-735|495,-705|495,-675|495,-555|495,15|495,45|495,75|495,105|495,135|495,165|495,195|495,225|495,255|495,285|495,315|495,345|495,375|495,405|495,435|495,465|495,495|495,525|495,555|495,585|495,615|495,645|495,675|495,705|495,735|495,765|495,795|495,825|495,855|495,885|495,915|495,945|495,975|495,1005|495,1035|495,1065|495,1095|495,1125|495,1155|495,1185|495,1215|495,1245|495,1275|495,1305|495,1335|495,1365|495,1395|495,1425|495,1545|495,1785|465,-1215|465,-1185|465,-1155|465,-1125|465,-1095|465,-1065|465,-1035|465,-1005|465,-975|465,-945|465,-915|465,-885|465,-825|465,-795|465,-765|465,-735|465,-705|465,-675|465,-555|465,-15|465,15|465,45|465,75|465,105|465,135|465,165|465,195|465,225|465,255|465,285|465,315|465,345|465,375|465,405|465,435|465,465|465,495|465,525|465,555|465,585|465,615|465,645|465,675|465,705|465,735|465,765|465,795|465,825|465,855|465,885|465,915|465,945|465,975|465,1005|465,1035|465,1065|465,1095|465,1125|465,1155|465,1185|465,1215|465,1245|465,1275|465,1305|465,1335|465,1365|465,1785|435,-1215|435,-1185|435,-1155|435,-1125|435,-1095|435,-1065|435,-1035|435,-1005|435,-975|435,-945|435,-915|435,-885|435,-855|435,-825|435,-795|435,-765|435,-735|435,-705|435,-645|435,-15|435,15|435,45|435,75|435,105|435,165|435,195|435,225|435,255|435,285|435,405|435,435|435,465|435,495|435,525|435,555|435,585|435,615|435,645|435,675|435,705|435,735|435,765|435,795|435,825|435,855|435,885|435,915|435,945|435,975|435,1005|435,1035|435,1065|435,1095|435,1125|435,1155|435,1185|435,1215|435,1245|435,1275|435,1305|435,1335|435,1425|435,1785|405,-1215|405,-1185|405,-1155|405,-1125|405,-1095|405,-1065|405,-1035|405,-1005|405,-975|405,-945|405,-915|405,-885|405,-855|405,-825|405,-795|405,-765|405,-735|405,-75|405,-45|405,-15|405,165|405,195|405,225|405,255|405,285|405,315|405,345|405,375|405,405|405,435|405,465|405,495|405,525|405,555|405,585|405,615|405,645|405,675|405,705|405,735|405,765|405,795|405,825|405,855|405,885|405,915|405,945|405,975|405,1005|405,1035|405,1065|405,1095|405,1125|405,1155|405,1185|405,1215|405,1245|405,1275|405,1395|405,1785|375,-1215|375,-1185|375,-1155|375,-1125|375,-1095|375,-1065|375,-1035|375,-1005|375,-975|375,-945|375,-915|375,-885|375,-855|375,-825|375,-795|375,-765|375,-255|375,-75|375,-45|375,-15|375,135|375,225|375,285|375,315|375,345|375,375|375,405|375,435|375,465|375,555|375,585|375,615|375,645|375,675|375,705|375,735|375,765|375,795|375,825|375,855|375,885|375,915|375,945|375,975|375,1005|375,1035|375,1065|375,1095|375,1125|375,1155|375,1185|375,1245|375,1275|375,1395|375,1785|345,-1185|345,-1155|345,-1125|345,-1095|345,-1065|345,-1035|345,-1005|345,-975|345,-945|345,-915|345,-885|345,-855|345,-825|345,-795|345,-765|345,-45|345,-15|345,15|345,45|345,75|345,105|345,375|345,405|345,435|345,465|345,495|345,525|345,555|345,585|345,615|345,645|345,675|345,705|345,735|345,765|345,795|345,825|345,855|345,885|345,915|345,945|345,975|345,1005|345,1035|345,1065|345,1095|345,1125|345,1155|345,1185|345,1275|345,1335|345,1365|345,1785|315,-1155|315,-1125|315,-1095|315,-1065|315,-1035|315,-1005|315,-975|315,-945|315,-915|315,-885|315,-855|315,-825|315,-75|315,-45|315,-15|315,15|315,45|315,75|315,105|315,135|315,225|315,345|315,375|315,405|315,435|315,465|315,495|315,525|315,555|315,585|315,615|315,645|315,675|315,705|315,735|315,765|315,795|315,825|315,855|315,885|315,915|315,945|315,975|315,1005|315,1035|315,1065|315,1095|315,1125|315,1155|315,1185|315,1215|315,1305|315,1785|285,-1095|285,-1065|285,-1035|285,-1005|285,-975|285,-825|285,-105|285,-75|285,-45|285,-15|285,15|285,45|285,75|285,105|285,135|285,165|285,195|285,225|285,255|285,285|285,315|285,345|285,375|285,405|285,435|285,465|285,525|285,555|285,585|285,615|285,645|285,675|285,705|285,735|285,765|285,795|285,825|285,855|285,885|285,915|285,945|285,975|285,1005|285,1035|285,1065|285,1095|285,1125|285,1155|285,1185|285,1785|255,-1065|255,-1035|255,-1005|255,-975|255,-135|255,-105|255,-75|255,-45|255,-15|255,15|255,45|255,75|255,105|255,135|255,165|255,195|255,225|255,255|255,285|255,315|255,375|255,405|255,435|255,465|255,495|255,555|255,585|255,615|255,645|255,675|255,705|255,735|255,765|255,795|255,825|255,855|255,885|255,915|255,945|255,975|255,1005|255,1035|255,1065|255,1095|255,1125|255,1155|255,1185|255,1785|225,-1035|225,-1005|225,-975|225,-825|225,-795|225,-165|225,-135|225,-105|225,-75|225,-45|225,-15|225,15|225,45|225,75|225,105|225,135|225,165|225,195|225,225|225,255|225,285|225,315|225,345|225,405|225,435|225,465|225,495|225,525|225,555|225,585|225,705|225,735|225,765|225,795|225,825|225,855|225,885|225,915|225,945|225,975|225,1005|225,1035|225,1065|225,1095|225,1125|225,1785|195,-1545|195,-1035|195,-1005|195,-975|195,-885|195,-705|195,-165|195,-135|195,-105|195,-75|195,-45|195,-15|195,15|195,45|195,75|195,105|195,135|195,165|195,195|195,225|195,255|195,285|195,315|195,345|195,405|195,435|195,465|195,495|195,525|195,555|195,735|195,765|195,795|195,825|195,945|195,975|195,1005|195,1035|195,1095|195,1785|165,-975|165,-945|165,-915|165,-885|165,-165|165,-135|165,-105|165,-75|165,-45|165,-15|165,15|165,45|165,75|165,105|165,135|165,165|165,195|165,225|165,255|165,285|165,315|165,345|165,375|165,435|165,465|165,495|165,525|165,735|165,765|165,795|165,945|165,975|165,1005|165,1035|165,1065|165,1785|135,-885|135,-855|135,-165|135,-135|135,-105|135,-75|135,-45|135,-15|135,15|135,45|135,75|135,105|135,135|135,165|135,195|135,225|135,255|135,285|135,315|135,345|135,375|135,405|135,435|135,465|135,765|135,795|135,975|135,1005|135,1035|135,1065|135,1215|135,1785|105,-855|105,-735|105,-705|105,-675|105,-615|105,-135|105,-105|105,-75|105,-45|105,-15|105,15|105,45|105,75|105,105|105,135|105,165|105,195|105,225|105,255|105,285|105,315|105,345|105,375|105,405|105,435|105,465|105,495|105,765|105,1035|105,1065|105,1185|105,1215|105,1785|75,-765|75,-735|75,-705|75,-675|75,-645|75,-615|75,-585|75,-105|75,-75|75,-45|75,-15|75,15|75,45|75,75|75,105|75,135|75,165|75,195|75,225|75,255|75,285|75,315|75,345|75,375|75,405|75,435|75,465|75,795|75,1245|75,1335|75,1785|45,-765|45,-735|45,-705|45,-675|45,-645|45,-615|45,-585|45,-555|45,-525|45,-75|45,75|45,105|45,135|45,165|45,195|45,225|45,255|45,285|45,315|45,345|45,375|45,405|45,435|45,465|45,975|45,1005|45,1155|45,1785|15,-765|15,-735|15,-705|15,-675|15,-645|15,-615|15,-585|15,-555|15,-525|15,-495|15,105|15,135|15,165|15,195|15,225|15,255|15,285|15,315|15,345|15,375|15,405|15,435|15,1005|15,1035|15,1095|15,1125|15,1155|15,1275|15,1785|-15,-795|-15,-765|-15,-735|-15,-705|-15,-675|-15,-645|-15,-615|-15,-585|-15,-555|-15,-525|-15,-495|-15,-465|-15,105|-15,135|-15,165|-15,195|-15,225|-15,255|-15,285|-15,345|-15,375|-15,405|-15,1005|-15,1035|-15,1095|-15,1125|-15,1155|-15,1185|-15,1305|-15,1365|-15,1785|-45,-795|-45,-765|-45,-735|-45,-705|-45,-675|-45,-645|-45,-615|-45,-585|-45,-555|-45,-525|-45,-495|-45,-465|-45,-435|-45,-405|-45,-375|-45,135|-45,165|-45,195|-45,225|-45,255|-45,285|-45,315|-45,345|-45,375|-45,1035|-45,1215|-45,1365|-45,1395|-45,1425|-45,1515|-45,1785|-75,-765|-75,-735|-75,-705|-75,-675|-75,-645|-75,-615|-75,-585|-75,-555|-75,-525|-75,-495|-75,-465|-75,-435|-75,-405|-75,-375|-75,135|-75,165|-75,195|-75,225|-75,255|-75,285|-75,315|-75,345|-75,375|-75,1095|-75,1125|-75,1305|-75,1365|-75,1395|-75,1425|-75,1455|-75,1575|-75,1785|-105,-765|-105,-735|-105,-705|-105,-675|-105,-645|-105,-615|-105,-585|-105,-555|-105,-525|-105,-495|-105,-465|-105,-435|-105,-405|-105,-375|-105,165|-105,195|-105,225|-105,255|-105,285|-105,315|-105,345|-105,375|-105,1605|-105,1785|-135,-735|-135,-705|-135,-675|-135,-645|-135,-615|-135,-585|-135,-555|-135,-525|-135,-495|-135,-465|-135,-435|-135,-405|-135,135|-135,165|-135,195|-135,225|-135,255|-135,285|-135,315|-135,375|-135,495|-135,1305|-135,1335|-135,1425|-135,1785|-165,-705|-165,-675|-165,-645|-165,-615|-165,-585|-165,-555|-165,-525|-165,-495|-165,-465|-165,-435|-165,-405|-165,135|-165,165|-165,195|-165,225|-165,255|-165,285|-165,315|-165,345|-165,375|-165,465|-165,1215|-165,1245|-165,1275|-165,1305|-165,1335|-165,1365|-165,1425|-165,1785|-195,-675|-195,-645|-195,-615|-195,-585|-195,-555|-195,-525|-195,-495|-195,-465|-195,-435|-195,-405|-195,135|-195,165|-195,195|-195,225|-195,255|-195,285|-195,315|-195,345|-195,465|-195,1185|-195,1215|-195,1245|-195,1275|-195,1305|-195,1335|-195,1365|-195,1395|-195,1425|-195,1455|-195,1785|-225,-675|-225,-645|-225,-615|-225,-585|-225,-555|-225,-525|-225,-495|-225,-465|-225,-435|-225,165|-225,195|-225,225|-225,255|-225,285|-225,315|-225,345|-225,435|-225,465|-225,1155|-225,1185|-225,1215|-225,1245|-225,1275|-225,1305|-225,1335|-225,1365|-225,1395|-225,1425|-225,1455|-225,1485|-225,1785|-255,-675|-255,-645|-255,-615|-255,-585|-255,-555|-255,-525|-255,-495|-255,165|-255,195|-255,225|-255,255|-255,285|-255,315|-255,1125|-255,1155|-255,1185|-255,1215|-255,1245|-255,1275|-255,1305|-255,1335|-255,1365|-255,1395|-255,1425|-255,1455|-255,1485|-255,1515|-255,1785|-285,-705|-285,-675|-285,-645|-285,-615|-285,-585|-285,-555|-285,-525|-285,-495|-285,165|-285,195|-285,225|-285,255|-285,285|-285,1155|-285,1185|-285,1215|-285,1245|-285,1275|-285,1305|-285,1335|-285,1365|-285,1395|-285,1425|-285,1455|-285,1485|-285,1515|-285,1785|-315,-705|-315,-675|-315,-645|-315,-615|-315,-585|-315,-555|-315,-525|-315,195|-315,225|-315,255|-315,285|-315,1155|-315,1185|-315,1215|-315,1245|-315,1335|-315,1365|-315,1395|-315,1425|-315,1455|-315,1485|-315,1515|-315,1785|-345,-705|-345,-675|-345,-645|-345,-615|-345,-585|-345,-555|-345,1155|-345,1365|-345,1395|-345,1425|-345,1455|-345,1485|-345,1725|-345,1785|-375,-705|-375,-675|-375,-645|-375,-615|-375,-585|-375,1425|-375,1455|-375,1755|-375,1785|-405,-735|-405,-705|-405,-675|-405,1455|-405,1725|-405,1785|-435,-735|-435,-705|-435,-675|-435,1695|-435,1785|-465,-735|-465,-705|-465,-675|-465,1785|-495,-735|-495,-705|-495,1785|-525,-735|-525,-705|-525,-675|-525,1785|-555,1785|-585,1785|-615,1785|-645,-615|-645,975|-645,1005|-645,1035|-645,1245|-645,1365|-645,1785|-675,-645|-675,-615|-675,405|-675,435|-675,465|-675,495|-675,525|-675,555|-675,585|-675,615|-675,645|-675,675|-675,705|-675,795|-675,825|-675,855|-675,885|-675,915|-675,945|-675,975|-675,1005|-675,1035|-675,1065|-675,1095|-675,1125|-675,1155|-675,1185|-675,1215|-675,1245|-675,1275|-675,1305|-675,1335|-675,1365|-675,1395|-675,1425|-675,1455|-675,1485|-675,1515|-675,1545|-675,1785|-705,-735|-705,-705|-705,-675|-705,-645|-705,-615|-705,-135|-705,-105|-705,-75|-705,-45|-705,-15|-705,15|-705,45|-705,75|-705,105|-705,135|-705,165|-705,195|-705,225|-705,255|-705,285|-705,315|-705,345|-705,375|-705,405|-705,435|-705,465|-705,495|-705,525|-705,555|-705,585|-705,615|-705,645|-705,675|-705,705|-705,735|-705,765|-705,795|-705,825|-705,855|-705,885|-705,915|-705,945|-705,975|-705,1005|-705,1035|-705,1065|-705,1095|-705,1125|-705,1155|-705,1185|-705,1215|-705,1245|-705,1275|-705,1305|-705,1335|-705,1365|-705,1395|-705,1425|-705,1455|-705,1485|-705,1515|-705,1545|-705,1575|-705,1605|-705,1635|-705,1665|-705,1695|-705,1785|-735,-1425|-735,-1395|-735,-1365|-735,-1335|-735,-1305|-735,-1275|-735,-1245|-735,-1215|-735,-1185|-735,-1155|-735,-1125|-735,-1095|-735,-1065|-735,-1035|-735,-1005|-735,-975|-735,-945|-735,-915|-735,-885|-735,-855|-735,-825|-735,-795|-735,-765|-735,-735|-735,-705|-735,-675|-735,-645|-735,-615|-735,-225|-735,-195|-735,-165|-735,-135|-735,-105|-735,-75|-735,-45|-735,-15|-735,15|-735,45|-735,75|-735,105|-735,135|-735,165|-735,195|-735,225|-735,255|-735,285|-735,315|-735,345|-735,375|-735,405|-735,435|-735,465|-735,495|-735,525|-735,555|-735,585|-735,615|-735,645|-735,675|-735,705|-735,735|-735,765|-735,795|-735,825|-735,855|-735,885|-735,915|-735,945|-735,975|-735,1005|-735,1035|-735,1065|-735,1095|-735,1125|-735,1155|-735,1185|-735,1215|-735,1245|-735,1275|-735,1305|-735,1335|-735,1365|-735,1395|-735,1425|-735,1455|-735,1485|-735,1515|-735,1545|-735,1575|-735,1605|-735,1785|-765,-1575|-765,-1545|-765,-1515|-765,-1485|-765,-1455|-765,-1425|-765,-1395|-765,-1365|-765,-1335|-765,-1305|-765,-1275|-765,-1245|-765,-1215|-765,-1185|-765,-1155|-765,-1125|-765,-1095|-765,-1065|-765,-1035|-765,-1005|-765,-975|-765,-945|-765,-915|-765,-885|-765,-855|-765,-825|-765,-795|-765,-765|-765,-735|-765,-705|-765,-675|-765,-645|-765,-615|-765,-585|-765,-555|-765,-525|-765,-495|-765,-345|-765,-315|-765,-285|-765,-255|-765,-225|-765,-195|-765,-165|-765,-135|-765,-105|-765,-75|-765,-45|-765,-15|-765,15|-765,45|-765,75|-765,105|-765,135|-765,165|-765,195|-765,225|-765,255|-765,285|-765,315|-765,345|-765,375|-765,405|-765,435|-765,465|-765,495|-765,525|-765,555|-765,585|-765,615|-765,645|-765,675|-765,705|-765,735|-765,765|-765,795|-765,825|-765,855|-765,885|-765,915|-765,945|-765,975|-765,1005|-765,1035|-765,1065|-765,1095|-765,1125|-765,1155|-765,1185|-765,1215|-765,1245|-765,1275|-765,1305|-765,1335|-765,1365|-765,1395|-765,1425|-765,1455|-765,1485|-765,1515|-765,1545|-765,1575|-765,1605|-765,1635|-765,1665|-765,1695|-765,1725|-765,1785|-795,-1785|-795,-1755|-795,-1725|-795,-1695|-795,-1665|-795,-1635|-795,-1605|-795,-1575|-795,-1545|-795,-1515|-795,-1485|-795,-1455|-795,-1425|-795,-1395|-795,-1365|-795,-1335|-795,-1305|-795,-1275|-795,-1245|-795,-1215|-795,-1185|-795,-1155|-795,-1125|-795,-1095|-795,-1065|-795,-1035|-795,-1005|-795,-975|-795,-945|-795,-915|-795,-885|-795,-855|-795,-825|-795,-795|-795,-765|-795,-735|-795,-705|-795,-675|-795,-645|-795,-615|-795,-585|-795,-555|-795,-525|-795,-495|-795,-465|-795,-435|-795,-405|-795,-375|-795,-345|-795,-315|-795,-285|-795,-255|-795,-225|-795,-195|-795,-165|-795,-135|-795,-105|-795,-75|-795,-45|-795,-15|-795,15|-795,45|-795,75|-795,105|-795,135|-795,165|-795,195|-795,225|-795,255|-795,285|-795,315|-795,345|-795,375|-795,405|-795,435|-795,465|-795,495|-795,525|-795,555|-795,585|-795,615|-795,645|-795,675|-795,705|-795,735|-795,765|-795,795|-795,825|-795,855|-795,885|-795,915|-795,945|-795,975|-795,1005|-795,1035|-795,1065|-795,1095|-795,1125|-795,1155|-795,1185|-795,1215|-795,1245|-795,1275|-795,1305|-795,1335|-795,1365|-795,1395|-795,1425|-795,1455|-795,1485|-795,1515|-795,1545|-795,1575|-795,1605|-795,1635|-795,1665|-795,1695|-795,1725|-795,1755|-795,1785|-825,-1785|-825,-1755|-825,-1725|-825,-1695|-825,-1665|-825,-1635|-825,-1605|-825,-1575|-825,-1545|-825,-1515|-825,-1485|-825,-1455|-825,-1425|-825,-1395|-825,-1365|-825,-1335|-825,-1305|-825,-1275|-825,-1245|-825,-1215|-825,-1185|-825,-1155|-825,-1125|-825,-1095|-825,-1065|-825,-1035|-825,-1005|-825,-975|-825,-945|-825,-915|-825,-885|-825,-855|-825,-825|-825,-795|-825,-765|-825,-735|-825,-705|-825,-675|-825,-645|-825,-615|-825,-585|-825,-555|-825,-525|-825,-495|-825,-465|-825,-435|-825,-405|-825,-375|-825,-345|-825,-315|-825,-285|-825,-255|-825,-225|-825,-195|-825,-165|-825,-135|-825,-105|-825,-75|-825,-45|-825,-15|-825,15|-825,45|-825,75|-825,105|-825,135|-825,165|-825,195|-825,225|-825,255|-825,285|-825,315|-825,345|-825,375|-825,405|-825,435|-825,465|-825,495|-825,525|-825,555|-825,585|-825,615|-825,645|-825,675|-825,705|-825,735|-825,765|-825,795|-825,825|-825,855|-825,885|-825,915|-825,945|-825,975|-825,1005|-825,1035|-825,1065|-825,1095|-825,1125|-825,1155|-825,1185|-825,1215|-825,1245|-825,1275|-825,1305|-825,1335|-825,1365|-825,1395|-825,1425|-825,1455|-825,1485|-825,1515|-825,1545|-825,1575|-825,1605|-825,1635|-825,1665|-825,1695|-825,1725|-825,1755|-825,1785|-855,-1785|-855,-1755|-855,-1725|-855,-1695|-855,-1665|-855,-1635|-855,-1605|-855,-1575|-855,-1545|-855,-1515|-855,-1485|-855,-1455|-855,-1425|-855,-1395|-855,-1365|-855,-1335|-855,-1305|-855,-1275|-855,-1245|-855,-1215|-855,-1185|-855,-1155|-855,-1125|-855,-1095|-855,-1065|-855,-1035|-855,-1005|-855,-975|-855,-945|-855,-915|-855,-885|-855,-855|-855,-825|-855,-795|-855,-765|-855,-735|-855,-705|-855,-675|-855,-645|-855,-615|-855,-585|-855,-555|-855,-525|-855,-495|-855,-465|-855,-435|-855,-405|-855,-375|-855,-345|-855,-315|-855,-285|-855,-255|-855,-225|-855,-195|-855,-165|-855,-135|-855,-105|-855,-75|-855,-45|-855,-15|-855,15|-855,45|-855,75|-855,105|-855,135|-855,165|-855,195|-855,225|-855,255|-855,285|-855,315|-855,345|-855,375|-855,405|-855,435|-855,465|-855,495|-855,525|-855,555|-855,585|-855,615|-855,645|-855,675|-855,705|-855,735|-855,765|-855,795|-855,825|-855,855|-855,885|-855,915|-855,945|-855,975|-855,1005|-855,1035|-855,1065|-855,1095|-855,1125|-855,1155|-855,1185|-855,1215|-855,1245|-855,1275|-855,1305|-855,1335|-855,1365|-855,1395|-855,1425|-855,1455|-855,1485|-855,1515|-855,1545|-855,1575|-855,1605|-855,1635|-855,1665|-855,1695|-855,1725|-855,1755|-855,1785|-885,-1785|-885,-1755|-885,-1725|-885,-1695|-885,-1665|-885,-1635|-885,-1605|-885,-1575|-885,-1545|-885,-1515|-885,-1485|-885,-1455|-885,-1425|-885,-1395|-885,-1365|-885,-1335|-885,-1305|-885,-1275|-885,-1245|-885,-1215|-885,-1185|-885,-1155|-885,-1125|-885,-1095|-885,-1065|-885,-1035|-885,-1005|-885,-975|-885,-945|-885,-915|-885,-885|-885,-855|-885,-825|-885,-795|-885,-765|-885,-735|-885,-705|-885,-675|-885,-645|-885,-615|-885,-585|-885,-555|-885,-525|-885,-495|-885,-465|-885,-435|-885,-405|-885,-375|-885,-345|-885,-315|-885,-285|-885,-255|-885,-225|-885,-195|-885,-165|-885,-135|-885,-105|-885,-75|-885,-45|-885,-15|-885,15|-885,45|-885,75|-885,105|-885,135|-885,165|-885,195|-885,225|-885,255|-885,285|-885,315|-885,345|-885,375|-885,405|-885,435|-885,465|-885,495|-885,525|-885,555|-885,585|-885,615|-885,645|-885,675|-885,705|-885,735|-885,765|-885,795|-885,825|-885,855|-885,885|-885,915|-885,945|-885,975|-885,1005|-885,1035|-885,1065|-885,1095|-885,1125|-885,1155|-885,1185|-885,1215|-885,1245|-885,1275|-885,1305|-885,1335|-885,1365|-885,1395|-885,1425|-885,1455|-885,1485|-885,1515|-885,1545|-885,1575|-885,1605|-885,1635|-885,1665|-885,1695|-885,1725|-885,1755|-885,1785';

                function toVec(lat, lng) {
                    var phi = (90 - lat) * Math.PI / 180;
                    var theta = lng * Math.PI / 180;
                    return [
                        Math.sin(phi) * Math.cos(theta),
                        Math.cos(phi),
                        Math.sin(phi) * Math.sin(theta)
                    ];
                }

                // Precompute unit vectors for every land dot
                var landDots = (function () {
                    var out = [];
                    var parts = LAND_DOTS.split('|');
                    for (var i = 0; i < parts.length; i++) {
                        var c = parts[i].split(',');
                        if (c.length !== 2) continue;
                        out.push(toVec(parseInt(c[0], 10) / 10, parseInt(c[1], 10) / 10));
                    }
                    return out;
                })();

                // Preload avatars
                var avatars = [];
                for (var a = 0; a < developers.length; a++) {
                    (function (idx) {
                        var im = new Image();
                        im.onload = function () { avatars[idx] = { img: im, ok: true }; };
                        im.onerror = function () { avatars[idx] = { img: null, ok: false }; };
                        avatars[idx] = { img: null, ok: false };
                        if (developers[idx].avatar) { im.src = developers[idx].avatar; }
                    })(a);
                }

                function rotate(v, yw, pt) {
                    var cY = Math.cos(yw), sY = Math.sin(yw);
                    var x = v[0] * cY + v[2] * sY;
                    var z = -v[0] * sY + v[2] * cY;
                    var y = v[1];
                    var cP = Math.cos(pt), sP = Math.sin(pt);
                    var y2 = y * cP - z * sP;
                    var z2 = y * sP + z * cP;
                    return [x, y2, z2];
                }

                function resize() {
                    var rect = canvas.parentElement.getBoundingClientRect();
                    width = rect.width;
                    height = rect.height;
                    canvas.width = width * dpr;
                    canvas.height = height * dpr;
                    canvas.style.width = width + 'px';
                    canvas.style.height = height + 'px';
                    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                    cx = width / 2;
                    cy = height / 2;
                    R = Math.min(width, height) * 0.5 * zoom;
                }

                var resizeObserver = new ResizeObserver(function () { resize(); });
                if (canvas.parentElement) resizeObserver.observe(canvas.parentElement);

                function projectAll() {
                    points = [];
                    for (var i = 0; i < developers.length; i++) {
                        var dev = developers[i];
                        var v = rotate(toVec(dev.lat, dev.lng), yaw, pitch);
                        var z = v[2];
                        var sx = cx + R * v[0];
                        var sy = cy - R * v[1];
                        var r = Math.max(9, Math.min(26, 12 + (dev.reputation || 0) / 60)) * zoom;
                        points.push({ dev: dev, sx: sx, sy: sy, z: z, r: r });
                    }
                }

                function draw() {
                    ctx.clearRect(0, 0, width, height);

                    cosY = Math.cos(yaw); sinY = Math.sin(yaw);
                    cosP = Math.cos(pitch); sinP = Math.sin(pitch);

                    // Dot-matrix continents (front hemisphere only)
                    var dotR = Math.max(1.5, Math.min(4, R * 0.012));
                    var cY = cosY, sY = sinY, cP = cosP, sP = sinP;
                    for (var i = 0; i < landDots.length; i++) {
                        var v = landDots[i];
                        var x = v[0] * cY + v[2] * sY;
                        var z = -v[0] * sY + v[2] * cY;
                        var y = v[1];
                        var y2 = y * cP - z * sP;
                        var z2 = y * sP + z * cP;
                        if (z2 <= 0) continue;
                        var sx = cx + R * x;
                        var sy = cy - R * y2;
                        var depth = z2 * 0.6 + 0.4;
                        var dr = dotR * (0.6 + 0.4 * depth);
                        ctx.beginPath();
                        ctx.arc(sx, sy, dr, 0, Math.PI * 2);
                        ctx.fillStyle = 'rgba(59,91,219,' + (0.16 + 0.3 * depth).toFixed(3) + ')';
                        ctx.fill();
                    }

                    // Sphere rim + sheen
                    ctx.beginPath();
                    ctx.arc(cx, cy, R, 0, Math.PI * 2);
                    ctx.strokeStyle = 'rgba(55,80,235,0.22)';
                    ctx.lineWidth = 1.5;
                    ctx.stroke();
                    var sheen = ctx.createRadialGradient(cx - R * 0.4, cy - R * 0.45, R * 0.05, cx - R * 0.4, cy - R * 0.45, R * 0.9);
                    sheen.addColorStop(0, 'rgba(255,255,255,0.12)');
                    sheen.addColorStop(1, 'rgba(255,255,255,0)');
                    ctx.beginPath();
                    ctx.arc(cx, cy, R, 0, Math.PI * 2);
                    ctx.fillStyle = sheen;
                    ctx.fill();

                    projectAll();

                    // Sort front-to-back
                    var front = points.filter(function (p) { return p.z > 0; })
                        .sort(function (a, b) { return b.z - a.z; });

                    for (var j = 0; j < front.length; j++) {
                        var p = front[j];
                        var isActive = (p.dev.id === hoverId) || (p.dev.id === pinnedIndex);
                        var av = avatars[developers.indexOf(p.dev)];

                        if (av && av.ok && av.img) {
                            ctx.save();
                            ctx.beginPath();
                            ctx.arc(p.sx, p.sy, p.r, 0, Math.PI * 2);
                            ctx.clip();
                            ctx.drawImage(av.img, p.sx - p.r, p.sy - p.r, p.r * 2, p.r * 2);
                            ctx.restore();
                        } else {
                            ctx.beginPath();
                            ctx.arc(p.sx, p.sy, p.r, 0, Math.PI * 2);
                            ctx.fillStyle = isActive ? 'rgba(91,108,255,0.95)' : 'rgba(55,80,235,0.85)';
                            ctx.fill();
                        }

                        ctx.beginPath();
                        ctx.arc(p.sx, p.sy, p.r, 0, Math.PI * 2);
                        ctx.strokeStyle = isActive ? 'rgba(91,108,255,1)' : 'rgba(255,255,255,0.9)';
                        ctx.lineWidth = isActive ? 3 : 2;
                        ctx.stroke();

                        if (isActive) {
                            ctx.beginPath();
                            ctx.arc(p.sx, p.sy, p.r + 7, 0, Math.PI * 2);
                            ctx.strokeStyle = 'rgba(55,80,235,0.35)';
                            ctx.lineWidth = 1.5;
                            ctx.stroke();
                        }
                    }
                }

                function tick() {
                    if (!dragging && autoRotate) {
                        yaw += 0.0016;
                    }
                    draw();
                    window.requestAnimationFrame(tick);
                }

                function hitTest(x, y) {
                    var best = -1;
                    var bestDist = 26;
                    for (var i = 0; i < points.length; i++) {
                        var p = points[i];
                        if (p.z <= 0) continue;
                        var dx = p.sx - x;
                        var dy = p.sy - y;
                        var d = Math.sqrt(dx * dx + dy * dy);
                        if (d < bestDist) { bestDist = d; best = i; }
                    }
                    return best;
                }

                function canvasPos(e) {
                    var rect = canvas.getBoundingClientRect();
                    return { x: e.clientX - rect.left, y: e.clientY - rect.top };
                }

                function showTooltip(p, x, y, interactive) {
                    tooltip.style.left = x + 'px';
                    tooltip.style.top = y + 'px';
                    tooltip.classList.remove('hidden');
                    tooltip.classList.toggle('pointer-events-none', !interactive);
                    document.getElementById('globe-tip-avatar').src = p.dev.avatar || '';
                    document.getElementById('globe-tip-name').textContent = p.dev.name || 'Anonymous';
                    document.getElementById('globe-tip-location').textContent = p.dev.location || 'Location unknown';
                    var headline = document.getElementById('globe-tip-headline');
                    headline.textContent = p.dev.headline || 'Proven engineer on ProoDev';
                    document.getElementById('globe-tip-score').textContent = 'Magnitude ' + (p.dev.reputation || 0).toLocaleString();
                    document.getElementById('globe-tip-link').href = '{{ url('/passport') }}/' + encodeURIComponent(p.dev.handle);
                }

                function hideTooltip() {
                    tooltip.classList.add('hidden');
                    tooltip.classList.remove('pointer-events-none');
                }

                function clampTooltip(x, y) {
                    var w = tooltip.offsetWidth;
                    var h = tooltip.offsetHeight;
                    var left = Math.max(w / 2 + 8, Math.min(x, width - w / 2 - 8));
                    var top = Math.max(h + 8, Math.min(y, height - 8));
                    return { x: left, y: top };
                }

                canvas.addEventListener('pointerdown', function (e) {
                    canvas.setPointerCapture(e.pointerId);
                    pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
                    if (pointers.size === 2) {
                        var pts = Array.from(pointers.values());
                        pinchDist = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
                    }
                    dragging = true;
                    moved = false;
                    lastX = e.clientX;
                    lastY = e.clientY;
                });

                canvas.addEventListener('pointermove', function (e) {
                    var pos = canvasPos(e);
                    if (dragging && pointers.has(e.pointerId)) {
                        pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
                        if (pointers.size === 2) {
                            var pts = Array.from(pointers.values());
                            var dist = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
                            if (pinchDist > 0) {
                                var prev = zoom;
                                zoom = Math.max(0.7, Math.min(1.8, zoom * (dist / pinchDist)));
                                if (zoom !== prev) { R = Math.min(width, height) * 0.5 * zoom; }
                            }
                            pinchDist = dist;
                        } else {
                            var dx = e.clientX - lastX;
                            var dy = e.clientY - lastY;
                            if (Math.abs(dx) + Math.abs(dy) > 2) moved = true;
                            yaw += dx * 0.005;
                            pitch = Math.max(-1.4, Math.min(1.4, pitch - dy * 0.005));
                            lastX = e.clientX;
                            lastY = e.clientY;
                        }
                    } else {
                        var idx = hitTest(pos.x, pos.y);
                        if (idx !== -1) {
                            var p = points[idx];
                            hoverId = p.dev.id;
                            var t = clampTooltip(p.sx, p.sy - p.r - 16);
                            showTooltip(p, t.x, t.y, false);
                        } else {
                            hoverId = -1;
                            if (pinnedIndex === -1) {
                                hideTooltip();
                            }
                        }
                    }
                });

                function endPointer(e) {
                    pointers.delete(e.pointerId);
                    if (pointers.size < 2) pinchDist = 0;
                    if (pointers.size === 0) {
                        dragging = false;
                        if (!moved) {
                            var pos = canvasPos(e);
                            var idx = hitTest(pos.x, pos.y);
                            if (idx !== -1) {
                                pinnedIndex = points[idx].dev.id;
                                var tp = clampTooltip(points[idx].sx, points[idx].sy - points[idx].r - 16);
                                showTooltip(points[idx], tp.x, tp.y, true);
                            } else {
                                pinnedIndex = -1;
                                hideTooltip();
                            }
                        } else if (pinnedIndex === -1) {
                            hideTooltip();
                        }
                    }
                }

                canvas.addEventListener('pointerup', endPointer);
                canvas.addEventListener('pointercancel', endPointer);

                canvas.addEventListener('wheel', function (e) {
                    e.preventDefault();
                    zoom = Math.max(0.7, Math.min(1.8, zoom * (e.deltaY < 0 ? 1.1 : 0.9)));
                    R = Math.min(width, height) * 0.5 * zoom;
                }, { passive: false });

                canvas.addEventListener('pointerleave', function () {
                    hoverId = -1;
                    if (pinnedIndex === -1) hideTooltip();
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') { pinnedIndex = -1; hideTooltip(); }
                });

                resize();
                tick();
            })();



            (function () {
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
        </script>

        {{-- ===================== LIVE ACTIVITY POPUP (bottom-left) ===================== --}}
        @php
            $activityItems = [];

            foreach ($feed->take(6) as $event) {
                if (! $event->user) {
                    continue;
                }

                $activityItems[] = [
                    'kind' => 'activity',
                    'name' => $event->user->name,
                    'text' => $event->title,
                    'label' => $event->type->label(),
                    'avatar' => $event->user->avatarUrl(),
                    'url' => route('passport', $event->user->handle()),
                ];
            }

            foreach ($liveUsers as $liveUser) {
                $activityItems[] = [
                    'kind' => 'online',
                    'name' => $liveUser->name,
                    'text' => $liveUser->location ? 'Active from '.$liveUser->location : 'Active on ProoDev right now',
                    'label' => 'Live',
                    'avatar' => $liveUser->avatarUrl(),
                    'url' => route('passport', $liveUser->handle()),
                ];
            }

            $activityItems[] = [
                'kind' => 'chat',
                'name' => 'Chat with the community',
                'text' => 'Verified engineers discuss builds, tools and trades in real time.',
                'label' => 'Messages',
                'avatar' => null,
                'url' => auth()->check() ? route('wirechat.chats.chats') : route('register'),
            ];

            $activityItems = array_values(array_slice($activityItems, 0, 10));
        @endphp

        <div id="live-activity" class="fixed bottom-4 left-4 z-40 flex flex-col items-start gap-2 sm:bottom-6 sm:left-6" data-items="{{ json_encode($activityItems) }}">
            <div id="live-activity-card" class="pointer-events-none w-80 max-w-[calc(100vw-2rem)] translate-y-3 rounded-xl border border-zinc-200/80 bg-white/95 p-3.5 opacity-0 shadow-2xl shadow-zinc-900/10 backdrop-blur-xl transition-all duration-500 dark:border-white/10 dark:bg-zinc-900/95">
                <div class="flex items-center gap-3">
                    <span id="live-activity-avatar" class="relative flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#3750eb]/10 ring-1 ring-zinc-200 dark:ring-white/10">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-[#3750eb] dark:text-[#8f9dff]"><path fill-rule="evenodd" d="{{ $iconPaths['sparkles'] }}" /></svg>
                        <span class="absolute -bottom-0.5 -right-0.5 size-2.5 rounded-full border-2 border-white bg-emerald-500 dark:border-zinc-900"></span>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div id="live-activity-name" class="truncate text-sm font-semibold text-zinc-900 dark:text-white"></div>
                        <div id="live-activity-text" class="truncate text-xs text-zinc-500 dark:text-zinc-400"></div>
                    </div>
                    <span id="live-activity-label" class="shrink-0 rounded-full bg-[#3750eb]/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-[#3750eb] dark:text-[#8f9dff]"></span>
                </div>
            </div>

            <div class="flex items-center gap-2 rounded-full border border-zinc-200/80 bg-white/90 py-1.5 pl-3 pr-1.5 text-xs font-medium text-zinc-600 shadow-lg shadow-zinc-900/5 backdrop-blur-xl dark:border-white/10 dark:bg-zinc-900/90 dark:text-zinc-300">
                <span class="relative flex size-2">
                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-500 opacity-60"></span>
                    <span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
                </span>
                <span><span class="font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($onlineCount) }}</span> engineers online</span>
                <a href="{{ auth()->check() ? route('wirechat.chats.chats') : route('register') }}" class="inline-flex items-center gap-1 rounded-full bg-[#3750eb] px-3 py-1 font-semibold text-white transition hover:opacity-90">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-3"><path fill-rule="evenodd" d="{{ $iconPaths['chat-bubble-oval-left-ellipsis'] }}" clip-rule="evenodd"/></svg>
                    Chat
                </a>
            </div>
        </div>

        <script>
            (function () {
                'use strict';

                var host = document.getElementById('live-activity');
                var card = document.getElementById('live-activity-card');
                var nameEl = document.getElementById('live-activity-name');
                var textEl = document.getElementById('live-activity-text');
                var labelEl = document.getElementById('live-activity-label');
                var avatarEl = document.getElementById('live-activity-avatar');

                if (!host || !card || !nameEl || !textEl || !labelEl) return;

                var items = [];

                try {
                    items = JSON.parse(host.getAttribute('data-items') || '[]');
                } catch (e) {
                    items = [];
                }

                if (!items.length) return;

                var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                var paused = false;
                var index = 0;
                var timer = null;
                var chatSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-[#3750eb] dark:text-[#8f9dff]"><path fill-rule="evenodd" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>';
                var dotSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-emerald-500"><path fill-rule="evenodd" d="M12 9v3.75m0 3.75h.008v.008H12v-.008Zm0-10.214A11.95 11.95 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286Z"/></svg>';

                function render(item) {
                    nameEl.textContent = item.name || '';
                    textEl.textContent = item.text || '';
                    labelEl.textContent = item.label || '';

                    if (item.avatar) {
                        avatarEl.innerHTML = '<img src="' + item.avatar + '" alt="" class="size-full object-cover" />';
                    } else if (item.kind === 'chat') {
                        avatarEl.innerHTML = chatSvg;
                    } else {
                        avatarEl.innerHTML = dotSvg;
                    }

                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                    card.style.pointerEvents = 'auto';
                }

                function dismiss() {
                    card.style.opacity = '0.35';
                    card.style.transform = 'translateY(3px)';
                    card.style.pointerEvents = 'none';
                }

                function hide() {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(12px)';
                    card.style.pointerEvents = 'none';
                }

                function cycle() {
                    if (paused) {
                        timer = window.setTimeout(cycle, 2000);
                        return;
                    }

                    var item = items[index];
                    index = (index + 1) % items.length;

                    render(item);

                    if (reduced) {
                        timer = window.setTimeout(hide, 3000);
                        timer = window.setTimeout(cycle, 5600);
                        return;
                    }

                    timer = window.setTimeout(dismiss, 4500);
                    timer = window.setTimeout(hide, 5800);
                    timer = window.setTimeout(cycle, 7600);
                }

                card.addEventListener('click', function () {
                    var item = items[(index + items.length - 1) % items.length];
                    if (item && item.url) {
                        window.location.href = item.url;
                    }
                });

                card.addEventListener('mouseenter', function () {
                    paused = true;
                    window.clearTimeout(timer);
                    card.style.opacity = '1';
                });

                card.addEventListener('mouseleave', function () {
                    paused = false;
                    dismiss();
                    window.clearTimeout(timer);
                    timer = window.setTimeout(hide, 1200);
                    timer = window.setTimeout(cycle, 3000);
                });

                timer = window.setTimeout(cycle, 4000);
            })();
        </script>

        @fluxScripts
    </body>
</html>
