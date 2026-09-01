<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>ProoDev · Show What You've Built. Get Noticed.</title>

        <meta name="description" content="ProoDev helps developers turn repositories, projects, pull requests, open-source contributions and technical work into evidence-backed engineering achievements, helping them get noticed by recruiters and companies.">

        <meta name="keywords" content="{{ ($metaKeywords ?? null) ?: app(\App\Services\SiteSettings::class)->metaKeywords() }}">

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
            /* Vertical section borders — contained layout */
            .section-contained { position: relative; }
            .section-contained::before,
            .section-contained::after {
                content: '';
                position: absolute;
                top: 0;
                bottom: 0;
                width: 1px;
                background: rgb(228 228 231 / 0.6);
                z-index: 1;
            }
            .dark .section-contained::before { background: rgb(255 255 255 / 0.06); }
            .dark .section-contained::after  { background: rgb(255 255 255 / 0.06); }
            .section-contained::before { left: calc(50% - 40rem); }
            .section-contained::after  { right: calc(50% - 40rem); }
            @media (max-width: 1280px) {
                .section-contained::before { left: 1rem; }
                .section-contained::after  { right: 1rem; }
            }
            @media (min-width: 640px) and (max-width: 1280px) {
                .section-contained::before { left: 1.5rem; }
                .section-contained::after  { right: 1.5rem; }
            }
            @media (min-width: 1024px) and (max-width: 1280px) {
                .section-contained::before { left: 2rem; }
                .section-contained::after  { right: 2rem; }
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

            /* Sticky header with backdrop blur */
            .site-header {
                background-color: rgb(255 255 255 / 0.65);
                backdrop-filter: blur(16px) saturate(180%);
                -webkit-backdrop-filter: blur(16px) saturate(180%);
                border-color: rgb(228 228 231 / 0.5);
                transition: background-color .3s ease, border-color .3s ease, box-shadow .3s ease, backdrop-filter .3s ease;
            }
            .dark .site-header {
                background-color: rgb(9 9 11 / 0.6);
                border-color: rgb(255 255 255 / 0.06);
            }
            .site-header.is-scrolled {
                background-color: rgb(255 255 255 / 0.82);
                backdrop-filter: blur(16px) saturate(180%);
                -webkit-backdrop-filter: blur(16px) saturate(180%);
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
    <body class="page-landing min-h-screen overflow-x-clip bg-white text-zinc-900 antialiased selection:bg-zinc-900/20 dark:bg-zinc-950 dark:text-zinc-100">

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
        <header class="site-header fixed inset-x-0 top-0 z-50 border-b backdrop-blur-xl supports-[backdrop-filter]:bg-white/60 supports-[backdrop-filter]:dark:bg-zinc-950/50">
            <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('welcome') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo-black.png') }}" alt="ProoDev" class="h-7 w-auto dark:hidden" />
                    <img src="{{ asset('images/logo-white.png') }}" alt="ProoDev" class="hidden h-7 w-auto dark:block" />
                </a>

                <div class="hidden items-center gap-1 text-sm text-zinc-500 md:flex dark:text-zinc-400">
                    <a href="{{ route('developers') }}" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">Developers</a>
                    <a href="#recruiters" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">Recruiters</a>
                    <a href="{{ route('for-companies') }}" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">Companies</a>
                    <a href="#jobs" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">Opportunities</a>
                    <a href="#how-it-works" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">How It Works</a>
                </div>

                <div class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('home') }}" class="inline-flex items-center rounded-full bg-[#3750eb] px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-[#3750eb]/25 transition hover:opacity-90">
                            Open dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 transition hover:text-zinc-900 sm:inline-block dark:text-zinc-300 dark:hover:text-white">Sign In</a>
                        <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-[#3750eb] px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-[#3750eb]/25 transition hover:opacity-90">
                            Create Your DevID
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
                    <a href="{{ route('developers') }}" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">Developers</a>
                    <a href="#recruiters" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">Recruiters</a>
                    <a href="{{ route('for-companies') }}" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">Companies</a>
                    <a href="#jobs" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">Opportunities</a>
                    <a href="#how-it-works" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">How It Works</a>
                    <a href="{{ route('news.index') }}" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">News</a>
                    @guest
                        <a href="{{ route('login') }}" class="mt-2 rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">Sign in</a>
                    @endguest
                </div>
            </div>
        </header>

        {{-- ===================== HERO ===================== --}}
        <section id="feed" class="section-contained relative mx-auto max-w-7xl overflow-hidden px-4 pb-16 pt-16 text-center sm:px-6 sm:pt-24 lg:px-8">
            <div class="relative mx-auto max-w-3xl animate-fade-up">
                <a href="#how-it-works" class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white/60 px-4 py-1.5 text-xs font-medium text-zinc-600 transition hover:border-zinc-900 hover:text-zinc-900 dark:border-white/10 dark:bg-white/5 dark:text-zinc-300 dark:hover:text-white">
                    <span class="relative flex size-2">
                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-[#3750eb] opacity-60"></span>
                        <span class="relative inline-flex size-2 rounded-full bg-[#3750eb]"></span>
                    </span>
                    Your work. Your impact. Your next opportunity.
                </a>

                <h1 class="mt-8 text-4xl font-bold tracking-tight text-zinc-900 sm:text-6xl lg:text-7xl dark:text-white">
                    What You've Built <span class="text-gradient">Should Open Doors.</span>
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-zinc-600 sm:text-xl dark:text-zinc-400">
                    ProoDev uncovers the achievements and impact behind your code, projects, and contributions -
                    helping you showcase what you can do and connect with companies looking for engineers like you.
                </p>

                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    @auth
                        <a href="{{ route('home') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#3750eb] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#3750eb]/25 transition hover:opacity-90 sm:w-auto">
                            <img src="{{ asset('images/logo-white.png') }}" alt="ProoDev" class="h-4 w-auto shrink-0" />
                            Create Your DevID
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#3750eb] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#3750eb]/25 transition hover:opacity-90 sm:w-auto">
                            Create Your DevID
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                        </a>
                    @endauth
                    <a href="#jobs" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-white/60 px-6 py-3 text-sm font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-white sm:w-auto dark:border-white/10 dark:bg-white/5 dark:text-zinc-200 dark:hover:border-white/25 dark:hover:bg-white/10">
                        Find Opportunities
                    </a>
                </div>

                <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">Free for developers. No long résumé forms.</p>

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
                <div class="pointer-events-none absolute -inset-x-8 -top-10 bottom-0 -z-10 rounded-xl bg-[#3750eb]/10 blur-3xl dark:bg-[#3750eb]/15" aria-hidden="true"></div>

                <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white/70 shadow-2xl shadow-zinc-900/10 backdrop-blur-xl dark:border-white/10 dark:bg-zinc-900/80 dark:shadow-zinc-900/20">
                    {{-- Window chrome --}}
                    <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-5 py-3 dark:border-white/5">
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-full bg-zinc-900/60"></span>
                            <span class="size-2.5 rounded-full bg-zinc-400/70"></span>
                            <span class="size-2.5 rounded-full bg-zinc-300 dark:bg-zinc-600"></span>
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
                        <div class="mx-auto grid max-w-5xl gap-5 text-left lg:grid-cols-[minmax(0,1fr)_300px]">
                            <div>
                                <div class="text-sm font-semibold text-zinc-900 dark:text-white">Paste evidence. Get proof.</div>
                                <div class="mt-1 text-xs leading-relaxed text-zinc-500">
                                    Drop a GitHub repository or any project URL. ProoDev reads the real work, surfaces the
                                    engineering achievements behind it, and assembles the evidence instantly.
                                </div>

                                <div class="mt-5">
                                    <livewire:landing-scout wire:key="landing-scout" />
                                </div>
                            </div>

                            {{-- Live achievement card --}}
                            <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-zinc-950/80">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-10 items-center justify-center rounded-full bg-gradient-to-br from-[#3750eb] to-[#5b6cff] text-sm font-bold text-white">J</span>
                                    <div>
                                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">James Mwangi</div>
                                        <div class="text-xs text-zinc-500">Backend Engineer</div>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-zinc-100 bg-zinc-50 p-3 dark:border-white/5 dark:bg-white/[0.03]">
                                    <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Engineering Magnitude</div>
                                    <div class="mt-0.5 flex items-baseline gap-2">
                                        <span class="text-3xl font-bold tabular-nums text-zinc-900 dark:text-white">87</span>
                                        <span class="text-xs text-zinc-400">/ 100</span>
                                    </div>
                                </div>

                                <div>
                                    <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Recent engineering achievement</div>
                                    <div class="mt-2 rounded-lg border border-zinc-300 bg-zinc-100/70 dark:border-white/10 dark:bg-white/5 p-3">
                                        <div class="flex items-start gap-2 text-sm font-medium text-zinc-900 dark:text-white">
                                            <span class="mt-1 size-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                                            Solved a high-complexity concurrency problem
                                        </div>
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            <span class="rounded-full bg-zinc-900/5 px-2 py-0.5 text-[10px] font-medium text-zinc-600 dark:bg-white/10 dark:text-zinc-300">Open Source Contribution</span>
                                            <span class="rounded-full bg-zinc-900/5 px-2 py-0.5 text-[10px] font-medium text-zinc-600 dark:bg-white/10 dark:text-zinc-300">Laravel Ecosystem</span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Demonstrated expertise</div>
                                    <ul class="mt-1.5 space-y-1 text-xs text-zinc-600 dark:text-zinc-300">
                                        @foreach (['PHP', 'Backend Architecture', 'Concurrency', 'Testing'] as $skill)
                                            <li class="flex items-center gap-2">
                                                <span class="size-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                                                {{ $skill }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div>
                                    <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Evidence</div>
                                    <div class="mt-1.5 flex items-center gap-1 text-[11px] font-medium text-zinc-500 dark:text-zinc-400">
                                        Pull Request <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3 text-zinc-300 dark:text-zinc-600"><path d="{{ $iconPaths['arrow-right'] }}"/></svg>
                                        Issue <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3 text-zinc-300 dark:text-zinc-600"><path d="{{ $iconPaths['arrow-right'] }}"/></svg>
                                        Commit <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3 text-zinc-300 dark:text-zinc-600"><path d="{{ $iconPaths['arrow-right'] }}"/></svg>
                                        Tests
                                    </div>
                                </div>

                                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:border-zinc-900 hover:text-zinc-900 dark:text-white dark:border-white/10 dark:text-zinc-200">
                                    View Achievement
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-up-right'] }}" clip-rule="evenodd"/></svg>
                                </a>

                                <div class="rounded-lg border border-emerald-300/40 bg-emerald-50 p-3 dark:border-emerald-400/20 dark:bg-emerald-400/5">
                                    <div class="text-[10px] font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Matched opportunity</div>
                                    <div class="mt-1 flex items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">Senior Backend Engineer</div>
                                            <div class="text-[11px] font-medium tabular-nums text-emerald-600 dark:text-emerald-400">94% Work Match</div>
                                        </div>
                                        <a href="#jobs" class="shrink-0 rounded-md bg-white px-2 py-1 text-[11px] font-semibold text-zinc-700 ring-1 ring-zinc-200 transition hover:text-zinc-900 dark:text-white dark:bg-zinc-950/60 dark:text-zinc-200 dark:ring-white/10">View Opportunity</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== YOU BUILD. WE HELP YOU GET NOTICED. ===================== --}}
        <section id="how-it-works" class="section-contained relative overflow-hidden border-t border-zinc-200 dark:border-white/5 dark:bg-white/[0.01]">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <x-marketing.section-heading
                    eyebrow="How it works"
                    title="You Build. We Help You Get Noticed."
                    sub="You already have years of technical work behind you. ProoDev helps turn that work into a clear engineering story that developers, recruiters and companies can understand."
                />

                <div class="mt-14 grid gap-5 lg:grid-cols-3">
                    {{-- Step 1 --}}
                    <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-6 transition duration-300 hover:-translate-y-1 hover:border-zinc-900 hover:shadow-xl hover:shadow-zinc-900/10 dark:border-white/10 dark:bg-zinc-900/50 dark:hover:border-white/25 dark:hover:bg-white/[0.06]">
                        <span class="inline-flex w-fit items-center rounded-full bg-zinc-950 px-3 py-1 text-xs font-bold tracking-widest text-white ring-1 ring-zinc-200 dark:bg-white dark:text-zinc-950 dark:ring-white/10">01: ADD YOUR WORK</span>
                        <div class="mt-5 flex flex-wrap gap-1.5">
                            @foreach (['GitHub repositories', 'Pull requests', 'Projects', 'Packages', 'Articles', 'Demos', 'Open-source contributions'] as $source)
                                <span class="rounded-md bg-zinc-100 px-2 py-1 text-[11px] text-zinc-700 ring-1 ring-zinc-200 dark:bg-white/5 dark:text-zinc-200 dark:ring-white/10">{{ $source }}</span>
                            @endforeach
                        </div>
                        <p class="mt-5 text-base font-semibold text-zinc-900 dark:text-white">No long résumé forms.</p>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">You start with work you've already done.</p>
                    </div>

                    {{-- Step 2 --}}
                    <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-6 transition duration-300 hover:-translate-y-1 hover:border-zinc-900 hover:shadow-xl hover:shadow-zinc-900/10 dark:border-white/10 dark:bg-zinc-900/50 dark:hover:border-white/25 dark:hover:bg-white/[0.06]">
                        <span class="inline-flex w-fit items-center rounded-full bg-zinc-950 px-3 py-1 text-xs font-bold tracking-widest text-white ring-1 ring-zinc-200 dark:bg-white dark:text-zinc-950 dark:ring-white/10">02: PROODEV FINDS THE STORY</span>
                        <ul class="mt-5 space-y-1.5 text-sm text-zinc-600 dark:text-zinc-300">
                            @foreach (['Engineering achievements', 'Problems solved', 'Technical expertise', 'Project complexity', 'Open-source contributions', 'Impact and reach', 'Evidence'] as $found)
                                <li class="flex items-center gap-2">
                                    <span class="size-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                                    {{ $found }}
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-5 text-base font-semibold text-zinc-900 dark:text-white">We don't just collect activity. We look for meaning.</p>
                    </div>

                    {{-- Step 3 --}}
                    <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-6 transition duration-300 hover:-translate-y-1 hover:border-zinc-900 hover:shadow-xl hover:shadow-zinc-900/10 dark:border-white/10 dark:bg-zinc-900/50 dark:hover:border-white/25 dark:hover:bg-white/[0.06]">
                        <span class="inline-flex w-fit items-center rounded-full bg-zinc-950 px-3 py-1 text-xs font-bold tracking-widest text-white ring-1 ring-zinc-200 dark:bg-white dark:text-zinc-950 dark:ring-white/10">03: GET DISCOVERED</span>
                        <div class="mt-5 flex flex-wrap gap-1.5">
                            @foreach (['Recruiters', 'Engineering managers', 'Startups', 'Companies', 'Hiring teams'] as $audience)
                                <span class="rounded-md bg-zinc-100 px-2 py-1 text-[11px] text-zinc-700 ring-1 ring-zinc-200 dark:bg-white/5 dark:text-zinc-200 dark:ring-white/10">{{ $audience }}</span>
                            @endforeach
                        </div>
                        <p class="mt-5 text-base font-semibold text-zinc-900 dark:text-white">You're not just another applicant.</p>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Employers can see evidence behind your capabilities.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== THE TRANSFORMATION ===================== --}}
        <section id="transformation" class="section-contained relative overflow-hidden border-t border-zinc-200 bg-zinc-50 dark:border-white/5 dark:bg-white/[0.02]">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">The transformation</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Your Code Has a Story. We Help Tell It.</h2>
                </div>

                <div class="mt-14 grid items-stretch gap-5 lg:grid-cols-2">
                    {{-- Before --}}
                    <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-zinc-950/60">
                        <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"/></svg>
                            GitHub
                        </div>
                        <div class="mt-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 font-mono text-sm dark:border-white/10 dark:bg-zinc-900/70">
                            <div class="flex items-center gap-2">
                                <span class="rounded-full bg-emerald-400/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">Merged ✓</span>
                                <span class="text-zinc-500">PR #4821</span>
                            </div>
                            <p class="mt-3 text-zinc-700 dark:text-zinc-300">"Fix race condition in connection pool"</p>
                        </div>
                        <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">A single line in a changelog. Easy to miss. Easy to forget.</p>
                    </div>

                    {{-- After --}}
                    <div class="relative flex flex-col rounded-xl border border-zinc-300 dark:border-white/15 bg-gradient-to-br from-zinc-100 to-white p-5 shadow-lg shadow-zinc-900/10 dark:border-zinc-300 dark:border-white/15 dark:from-white/10 dark:to-zinc-950/60">
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">ProoDev</div>
                        <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-4 dark:bg-zinc-950/60">
                            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">Engineering achievement</div>
                            <p class="mt-2 text-base font-semibold text-zinc-900 dark:text-white">Solved a high-complexity concurrency problem.</p>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Demonstrated</div>
                                    <ul class="mt-1.5 space-y-1 text-xs text-zinc-600 dark:text-zinc-300">
                                        @foreach (['Concurrency', 'Debugging', 'Backend Architecture', 'Testing'] as $skill)
                                            <li class="flex items-center gap-2">
                                                <span class="size-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                                                {{ $skill }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div>
                                    <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Engineering significance</div>
                                    <span class="mt-1.5 inline-flex rounded-full bg-amber-400/10 px-2.5 py-1 text-xs font-bold text-amber-600 dark:text-amber-400">HIGH</span>
                                </div>
                            </div>
                            <div class="mt-4 border-t border-zinc-200 pt-3 dark:border-white/10">
                                <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Evidence</div>
                                <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                    @foreach (['Issue', 'Pull Request', 'Code Changes', 'Tests', 'Review'] as $i => $step)
                                        @if ($i > 0)
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3 rotate-90 text-zinc-300 dark:text-zinc-600"><path fill-rule="evenodd" d="{{ $iconPaths['chevron-down'] }}" clip-rule="evenodd"/></svg>
                                        @endif
                                        <span>{{ $step }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-300">The same work, finally understood.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== OPEN SOURCE ===================== --}}
        <section class="section-contained relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="grid items-center gap-10 lg:grid-cols-2">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">Open source</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Your Contribution Deserves More Than a "Merged" Badge.</h2>
                        <p class="mt-4 text-zinc-600 dark:text-zinc-400">You might see a pull request. ProoDev helps reveal the engineering contribution behind it.</p>
                        <p class="mt-6 text-lg font-medium text-zinc-700 dark:text-zinc-300">
                            The value isn't simply that you contributed. It's what the contribution demonstrates about you as an engineer.
                        </p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xl shadow-zinc-900/5 dark:border-white/10 dark:bg-zinc-900/50">
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Contributed to</div>
                        <div class="mt-1 flex items-center gap-2">
                            <img src="{{ asset('images/logo-black.png') }}" alt="ProoDev" class="h-6 w-auto dark:hidden" />
                            <img src="{{ asset('images/logo-white.png') }}" alt="ProoDev" class="hidden h-6 w-auto dark:block" />
                            <span class="text-xl font-bold text-zinc-900 dark:text-white">Laravel</span>
                            <span class="rounded-full bg-zinc-100 dark:bg-white/10 px-2.5 py-0.5 text-[10px] font-semibold text-zinc-900 dark:text-white">Major ecosystem project</span>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Contribution</div>
                                <p class="mt-1.5 text-sm text-zinc-700 dark:text-zinc-300">Improved authentication behavior.</p>
                            </div>
                            <div>
                                <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Demonstrates</div>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach (['Security', 'PHP', 'Framework Architecture', 'Testing'] as $skill)
                                        <span class="rounded-md bg-zinc-100 px-2 py-1 text-[11px] text-zinc-700 ring-1 ring-zinc-200 dark:bg-white/5 dark:text-zinc-200 dark:ring-white/10">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== WHAT PROODEV DISCOVERS ===================== --}}
        <section id="discovers" class="section-contained relative overflow-hidden border-t border-zinc-200 bg-zinc-50 dark:border-white/5 dark:bg-white/[0.02]">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">What ProoDev discovers</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">We Look for the Engineering Behind the Work.</h2>
                </div>

                <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        ['title' => 'Engineering Achievements', 'copy' => 'Discover meaningful accomplishments hidden inside your technical work.'],
                        ['title' => 'Problem Solving', 'copy' => 'Surface difficult problems you\'ve solved.'],
                        ['title' => 'Expertise', 'copy' => 'Identify capabilities demonstrated through real work.'],
                        ['title' => 'Open Source Contributions', 'copy' => 'Give meaningful context to your contributions.'],
                        ['title' => 'Impact', 'copy' => 'Help people understand the significance and reach of your work.'],
                        ['title' => 'Evidence', 'copy' => 'Keep every important conclusion connected to its source.'],
                    ] as $card)
                        <div class="group relative rounded-lg border border-zinc-200 bg-white p-5 transition duration-300 hover:-translate-y-1 hover:border-zinc-900 hover:shadow-xl hover:shadow-zinc-900/10 dark:border-white/10 dark:bg-zinc-950/60 dark:hover:border-white/25 dark:hover:bg-white/[0.04]">
                            <span class="inline-flex size-11 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-500/10" aria-hidden="true">
                                <span class="size-2.5 rounded-full bg-emerald-500"></span>
                            </span>
                            <h3 class="mt-4 text-base font-semibold text-zinc-900 dark:text-white">{{ $card['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $card['copy'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===================== BUILD YOUR DEVID ===================== --}}
        <section id="devid" class="section-contained relative overflow-hidden border-t border-zinc-200 dark:border-white/5 dark:bg-white/[0.01]">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="grid items-start gap-10 lg:grid-cols-2">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">Your DevID</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Build Your DevID From Work You've Already Done.</h2>
                        <p class="mt-4 text-zinc-600 dark:text-zinc-400">No long forms. No starting from a blank résumé. Add the work you've already created and let ProoDev help turn it into a professional engineering identity.</p>

                        <div class="mt-8 flex flex-wrap items-center gap-2.5">
                            @foreach (['GitHub', 'Projects', 'Pull Requests', 'Packages', 'Articles', 'Demos', 'Documentation', 'Open Source'] as $source)
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-zinc-900 dark:border-white/10 dark:bg-white/5 dark:text-zinc-200">
                                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                    {{ $source }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-col gap-4">
                        @foreach ([
                            ['step' => '01', 'title' => 'Build', 'copy' => 'Add the work you\'ve already done — GitHub repos, PRs, projects, articles.'],
                            ['step' => '02', 'title' => 'Prove', 'copy' => 'Evidence-backed achievements emerge from your real technical work.'],
                            ['step' => '03', 'title' => 'Get Noticed', 'copy' => 'The right people — recruiters, engineering managers, companies — see your work.'],
                        ] as $i => $s)
                            <div class="flex items-start gap-4 rounded-xl border border-zinc-200 bg-white p-5 transition duration-300 hover:border-zinc-900 hover:shadow-lg dark:border-white/10 dark:bg-zinc-900/50 dark:hover:border-white/25">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#3750eb] text-sm font-bold text-white">{{ $s['step'] }}</span>
                                <div>
                                    <div class="text-base font-semibold text-zinc-900 dark:text-white">{{ $s['title'] }}</div>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $s['copy'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== GLOBAL TALENT GLOBE ===================== --}}
        <section id="globe" class="section-contained relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">Global talent</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Engineers with proof, all over the world</h2>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">Live from the DevID database - real developers with evidence-backed engineering identities. Drag to spin the globe, click any profile to open a DevID.</p>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['label' => 'Public DevIDs', 'value' => number_format(count($globeDevelopers))],
                        ['label' => 'Evidence-backed scores', 'value' => 'Magnitude 0-1000'],
                        ['label' => 'Verified work', 'value' => 'Repos, projects, vouches'],
                        ['label' => 'One click to recruit', 'value' => 'DevID -> apply'],
                    ] as $globeStat)
                        <div class="rounded-xl border border-zinc-200 bg-white p-4 text-center dark:border-white/10 dark:bg-white/[0.03]">
                            <div class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $globeStat['value'] }}</div>
                            <div class="mt-0.5 text-xs font-medium uppercase tracking-wider text-zinc-500">{{ $globeStat['label'] }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="relative mt-10">
                    <div class="pointer-events-none absolute inset-0 -z-10 rounded-full bg-zinc-900/10 blur-3xl" aria-hidden="true"></div>
                    <div class="relative w-full h-[400px] overflow-hidden rounded-xl sm:h-[500px]">
                        <canvas id="talent-globe" class="block size-full cursor-grab active:cursor-grabbing" aria-label="3D globe of developers"></canvas>

                        <div id="globe-tooltip" class="absolute z-20 hidden w-72 -translate-x-1/2 rounded-lg border border-zinc-200 bg-white/95 p-4 shadow-2xl shadow-zinc-900/20 backdrop-blur-xl dark:border-white/10 dark:bg-zinc-900/95 dark:shadow-black/40" data-tooltip-interactive>
                            <div class="flex items-center gap-3">
                                <img id="globe-tip-avatar" src="" alt="" class="size-10 shrink-0 rounded-full ring-2 ring-zinc-900/30" />
                                <div class="min-w-0">
                                    <div id="globe-tip-name" class="truncate text-sm font-semibold text-zinc-900 dark:text-white"></div>
                                    <div id="globe-tip-location" class="truncate text-xs text-zinc-500"></div>
                                </div>
                            </div>
                            <p id="globe-tip-headline" class="mt-2 line-clamp-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400"></p>
                            <div class="mt-3 flex items-center justify-between">
                                <span id="globe-tip-score" class="inline-flex items-center gap-1 rounded-full bg-zinc-100 dark:bg-white/10 px-2 py-0.5 text-xs font-semibold text-zinc-900 dark:text-white"></span>
                                <a id="globe-tip-link" href="#" class="inline-flex items-center gap-1 text-xs font-semibold text-zinc-900 transition dark:text-white hover:gap-2">
                                    View DevID
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-up-right'] }}" clip-rule="evenodd"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 text-center text-xs text-zinc-500">Drag to rotate - Scroll or pinch to zoom - Click a profile for a DevID summary</p>
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

        {{-- ===================== OPPORTUNITIES ===================== --}}
        <section id="jobs" class="section-contained relative overflow-hidden border-t border-zinc-200 bg-zinc-50 dark:border-white/5 dark:bg-white/[0.02]">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="grid items-start gap-10 lg:grid-cols-2">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">Opportunities</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Let Your Work Lead You to the Right Opportunity.</h2>
                        <p class="mt-4 text-zinc-600 dark:text-zinc-400">When companies can understand what you've actually built, you're more than another application in a stack of résumés.</p>
                        <p class="mt-2 text-zinc-600 dark:text-zinc-400">ProoDev helps companies discover developers through demonstrated engineering work and helps developers find opportunities that match what they can actually do.</p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('jobs.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#3750eb] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#3750eb]/25 transition hover:opacity-90">
                                Find Opportunities
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                            </a>
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl border border-zinc-200 bg-white/60 px-5 py-2.5 text-sm font-semibold text-zinc-700 transition hover:bg-white dark:border-white/10 dark:bg-white/5 dark:text-zinc-200 dark:hover:bg-white/10">
                                Post a Job Free
                            </a>
                        </div>
                    </div>

                    {{-- Work-match example --}}
                    <div class="rounded-xl border border-zinc-300 dark:border-white/15 bg-gradient-to-br from-zinc-100 to-white p-5 shadow-lg shadow-zinc-900/10 dark:border-zinc-300 dark:border-white/15 dark:from-white/10 dark:to-zinc-950/60">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-white">Senior Backend Engineer</div>
                                <div class="text-xs text-zinc-500">Matched from your demonstrated work</div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/10 px-3 py-1 text-sm font-bold tabular-nums text-emerald-600 dark:text-emerald-400">
                                92% Work Match
                            </span>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-1.5">
                            @foreach (['Laravel', 'PostgreSQL', 'Redis', 'API Architecture', 'Open Source'] as $matchSkill)
                                <span class="inline-flex items-center gap-1 rounded-md bg-white/80 px-2 py-1 text-[11px] font-medium text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-950/60 dark:text-zinc-200 dark:ring-white/10">
                                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                    {{ $matchSkill }}
                                </span>
                            @endforeach
                        </div>

                        <div class="mt-4 rounded-lg border border-zinc-200 bg-white/70 p-3 dark:border-white/10 dark:bg-zinc-950/60">
                            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Why you match</div>
                            <p class="mt-1 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">Your projects and open-source contributions demonstrate the backend architecture experience this role requires.</p>
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-3">
                            <div class="rounded-lg border border-zinc-200 bg-white/70 p-3 text-center dark:border-white/10 dark:bg-zinc-950/60">
                                <div class="text-lg font-bold text-zinc-900 dark:text-white">{{ number_format($stats[0]['value']) }}</div>
                                <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500">Developers</div>
                            </div>
                            <div class="rounded-lg border border-zinc-200 bg-white/70 p-3 text-center dark:border-white/10 dark:bg-zinc-950/60">
                                <div class="text-lg font-bold text-zinc-900 dark:text-white">{{ count($openJobs) }}</div>
                                <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500">Open roles</div>
                            </div>
                            <div class="rounded-lg border border-zinc-200 bg-white/70 p-3 text-center dark:border-white/10 dark:bg-zinc-950/60">
                                <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">Free</div>
                                <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500">For devs</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== FOR RECRUITERS ===================== --}}
        <section id="recruiters" class="section-contained relative overflow-hidden border-t border-zinc-200 dark:border-white/5 dark:bg-white/[0.01]">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="grid items-center gap-10 lg:grid-cols-2">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">For recruiters &amp; companies</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Don't Just Read What a Developer Says. See What They've Built.</h2>
                        <p class="mt-4 text-lg leading-relaxed text-zinc-600 dark:text-zinc-400">CVs tell you what candidates claim. ProoDev helps you understand the engineering work behind those claims.</p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('developers') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#3750eb] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#3750eb]/25 transition hover:opacity-90">
                                Discover Developers
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                            </a>
                            <a href="{{ route('for-companies') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-white/60 px-6 py-3 text-sm font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-white dark:border-white/10 dark:bg-white/5 dark:text-zinc-200 dark:hover:border-white/25 dark:hover:bg-white/10">
                                For Recruiters
                            </a>
                        </div>
                    </div>

                    {{-- Real developer profile card --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xl shadow-zinc-900/5 dark:border-white/10 dark:bg-zinc-900/50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="relative overflow-hidden size-12 rounded-full bg-gradient-to-br from-[#3750eb] to-[#5b6cff]">
                                    <img src="https://api.dicebear.com/7.x/initials/svg?seed=TN&backgroundColor=3750eb" alt="Thabo Nkosi" class="size-full object-cover" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">Thabo Nkosi</div>
                                        <x-verified-badge :user="\App\Models\User::where('username','thabo-nkosi')->first()" compact />
                                    </div>
                                    <div class="text-xs text-zinc-500">Backend Engineer</div>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Magnitude</div>
                                <div class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-white">87</div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Notable achievements</div>
                            <ul class="mt-2 space-y-1.5 text-sm text-zinc-700 dark:text-zinc-300">
                                <li class="flex items-center gap-2"><span class="size-1.5 shrink-0 rounded-full bg-emerald-500"></span> Solved high-complexity concurrency problem</li>
                                <li class="flex items-center gap-2"><span class="size-1.5 shrink-0 rounded-full bg-emerald-500"></span> Open-source contributor to Laravel</li>
                                <li class="flex items-center gap-2"><span class="size-1.5 shrink-0 rounded-full bg-emerald-500"></span> Built multi-tenant SaaS platform</li>
                            </ul>
                        </div>

                        <div class="mt-5">
                            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Verified expertise</div>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach (['PHP', 'Laravel', 'PostgreSQL', 'Backend Architecture'] as $verifiedSkill)
                                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-400/10 px-2 py-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400">
                                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                        {{ $verifiedSkill }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <a href="{{ url('/devid/thabo-nkosi') }}" class="mt-5 inline-flex items-center gap-1.5 text-sm font-medium text-zinc-900 transition hover:gap-2.5 dark:text-white">
                            View full DevID
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-up-right'] }}" clip-rule="evenodd"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== VOUCHES ===================== --}}
        <section class="section-contained relative overflow-hidden border-t border-zinc-200 bg-zinc-50 dark:border-white/5 dark:bg-white/[0.02]">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="grid items-start gap-10 lg:grid-cols-2">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">Vouches</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Endorsements from engineers who know</h2>
                        <p class="mt-4 text-zinc-600 dark:text-zinc-400">Real vouches from the community — weighted by each giver's proven track record and anchored to evidence.</p>

                        <div class="mt-8">
                            @forelse ($vouches->take(2) as $vouch)
                                <figure class="mb-5 rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900/50">
                                    <div class="flex gap-1 text-zinc-900 dark:text-white">
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
                                            <div class="truncate text-xs text-zinc-500">vouched for {{ $vouch->vouchee?->name }} — {{ $vouch->type->label() }}@if ($vouch->skill) · {{ $vouch->skill->name }}@endif</div>
                                        </div>
                                    </figcaption>
                                </figure>
                            @empty
                                @foreach ([[], []] as $i => $skeleton)
                                    <figure class="mb-5 rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900/50">
                                        <div class="flex gap-1 text-zinc-500">
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

                    <div>
                        @forelse ($vouches->slice(2) as $vouch)
                            <figure class="mb-5 rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900/50">
                                <div class="flex gap-1 text-zinc-900 dark:text-white">
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
                                        <div class="truncate text-xs text-zinc-500">vouched for {{ $vouch->vouchee?->name }} — {{ $vouch->type->label() }}@if ($vouch->skill) · {{ $vouch->skill->name }}@endif</div>
                                    </div>
                                </figcaption>
                            </figure>
                        @empty
                            <figure class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900/50">
                                <div class="flex gap-1 text-zinc-500">
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
                        @endforelse

                        <a href="{{ route('developers') }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-zinc-900 transition hover:gap-2.5 dark:text-white">
                            Browse verified engineers
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== FAQ ===================== --}}
        <section class="section-contained relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">FAQ</p>
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
        <section class="section-contained mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-gradient-to-br from-zinc-100 via-white to-zinc-50 px-6 py-16 text-center sm:px-16 dark:border-white/10 dark:from-white/10 dark:via-zinc-900 dark:to-white/5">
                <div class="relative">
                    <h2 class="mx-auto max-w-2xl text-3xl font-bold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                        Your Work Is Already Telling Your Story.
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-lg text-zinc-700 dark:text-zinc-300">
                        Let ProoDev help the right people see it.
                    </p>
                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        @auth
                            <a href="{{ route('home') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-zinc-900 px-6 py-3 text-sm font-semibold text-white! transition hover:bg-zinc-700 sm:w-auto dark:bg-white dark:text-zinc-900! dark:hover:bg-zinc-200">
                                Build My DevID
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-zinc-900 px-6 py-3 text-sm font-semibold text-white! transition hover:bg-zinc-700 sm:w-auto dark:bg-white dark:text-zinc-900! dark:hover:bg-zinc-200">
                                Build My DevID
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                            </a>
                            <a href="{{ route('developers') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-zinc-300 bg-white/60 px-6 py-3 text-sm font-semibold text-zinc-700 transition hover:bg-white sm:w-auto dark:border-white/20 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                                Explore Developers
                            </a>
                        @endauth
                    </div>
                    <p class="mt-8 text-sm font-medium text-zinc-500 dark:text-zinc-400">Build something meaningful. Prove what you can do. Get noticed.</p>
                </div>
            </div>
        </section>

        <x-landing-sponsors-ads />

        {{-- ===================== FOOTER ===================== --}}
        <footer class="relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="mx-auto max-w-7xl px-4 pt-16 pb-8 sm:px-6 lg:px-8">
                <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Brand --}}
                    <div class="lg:col-span-1">
                        <div class="flex items-center gap-2.5">
                            <img src="{{ asset('images/logo-black.png') }}" alt="ProoDev" class="h-6 w-auto dark:hidden" />
                            <img src="{{ asset('images/logo-white.png') }}" alt="ProoDev" class="hidden h-6 w-auto dark:block" />
                        </div>
                        <p class="mt-4 max-w-xs text-sm leading-relaxed text-zinc-500">Show what you've built. Get noticed by the right people. Evidence-backed engineering identities for developers.</p>
                        <div class="mt-5 flex items-center gap-3">
                            <a href="https://github.com/omaadonyo/proodev.com" class="inline-flex size-9 items-center justify-center rounded-lg border border-zinc-200 text-zinc-500 transition hover:border-zinc-900 hover:text-zinc-900 dark:border-white/10 dark:text-zinc-400 dark:hover:border-white dark:hover:text-white" aria-label="GitHub">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"/></svg>
                            </a>
                        </div>
                    </div>

                    {{-- Product --}}
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Product</h3>
                        <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                            <li><a href="{{ route('home') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Dashboard</a></li>
                            <li><a href="{{ route('developers') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Verified Directory</a></li>
                            <li><a href="{{ route('jobs.index') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Open Roles</a></li>
                            <li><a href="{{ route('for-companies') }}" class="transition hover:text-zinc-900 dark:hover:text-white">For Companies</a></li>
                            <li><a href="{{ url('/devid') }}" class="transition hover:text-zinc-900 dark:hover:text-white">DevID</a></li>
                        </ul>
                    </div>

                    {{-- Company --}}
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Company</h3>
                        <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                            <li><a href="{{ route('for-companies') }}" class="transition hover:text-zinc-900 dark:hover:text-white">About</a></li>
                            <li><a href="{{ route('news.index') }}" class="transition hover:text-zinc-900 dark:hover:text-white">News</a></li>
                            <li><a href="{{ route('developers') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Verified Developers</a></li>
                            @auth
                                <li><a href="{{ route('home') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Dashboard</a></li>
                            @else
                                <li><a href="{{ route('login') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Sign in</a></li>
                                <li><a href="{{ route('register') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Register</a></li>
                            @endauth
                        </ul>
                    </div>

                    {{-- Legal --}}
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Legal</h3>
                        <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                            <li><a href="{{ route('privacy') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Privacy Policy</a></li>
                            <li><a href="{{ route('terms') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Terms &amp; Conditions</a></li>
                            <li><a href="{{ route('cookies') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Cookie Policy</a></li>
                        </ul>
                    </div>
                </div>

                {{-- Bottom bar --}}
                <div class="mt-12 flex flex-col items-center justify-between gap-3 border-t border-zinc-200 pt-6 sm:flex-row dark:border-white/5">
                    <p class="text-xs text-zinc-400">&copy; {{ date('Y') }} {{ config('app.name', 'ProoDev') }}. Proof over claims.</p>
                    <p class="text-xs text-zinc-400">Built for engineers who back their claims with evidence.</p>
                </div>
            </div>
        </footer>

        <script>
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
                var yaw = 0.2, pitch = 0.42;
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
                var LAND_DOTS = 'Full: -560,-740|-550,-710|-560,-670|-550,-640|-560,-600|-550,-570|-560,-580|-550,-500|-560,-510|-530,-760|-550,-730|-530,-740|-540,-700|-550,-660|-530,-670|-540,-630|-550,-640|-530,-600|-540,-560|-550,-570|-530,-530|-540,-540|-550,-500|-530,-760|-510,-720|-520,-730|-530,-690|-510,-700|-520,-660|-530,-620|-510,-630|-520,-590|-530,-600|-510,-560|-520,-520|-530,-530|-520,-500|-490,-750|-500,-760|-510,-720|-490,-680|-500,-690|-510,-650|-490,-660|-500,-620|-510,-580|-490,-590|-500,-550|-510,-560|-490,-520|-470,1690|-470,1760|-480,-740|-490,-750|-470,-710|-480,-720|-490,-680|-470,-640|-480,-650|-490,-610|-470,-620|-480,-580|-490,-540|-470,-550|-480,-510|-490,-520|-470,1670|-450,1660|-460,1700|-470,1740|-450,1730|-460,1770|-470,1760|-450,-740|-460,-700|-470,-710|-450,-670|-460,-680|-470,-640|-450,-600|-460,-610|-470,-570|-450,-580|-460,-540|-470,-500|-450,-510|-430,1680|-440,1670|-450,1710|-430,1700|-440,1740|-450,1780|-430,1770|-450,-760|-440,-730|-450,-740|-430,-700|-440,-660|-450,-670|-430,-630|-440,-640|-450,-600|-430,-560|-440,-570|-450,-530|-430,-540|-440,-500|-430,1680|-410,1720|-420,1710|-430,1750|-410,1740|-420,1780|-420,-760|-430,-720|-410,-730|-420,-690|-430,-700|-410,-660|-420,-620|-430,-630|-410,-590|-420,-600|-430,-560|-410,-520|-420,-530|-410,-500|-390,1180|-400,1220|-390,1250|-400,1290|-390,1320|-400,1360|-390,1390|-400,1380|-390,1460|-400,1450|-390,1480|-400,1520|-390,1550|-400,1660|-390,1690|-400,1680|-410,1720|-390,1760|-400,1750|-410,1790|-410,-750|-390,-760|-400,-720|-410,-680|-390,-690|-400,-650|-410,-660|-390,-620|-400,-580|-410,-590|-390,-550|-400,-560|-410,-520|-380,1120|-390,1160|-370,1200|-380,1190|-390,1230|-370,1220|-380,1260|-390,1300|-370,1290|-380,1330|-390,1320|-370,1360|-380,1400|-390,1390|-370,1430|-380,1420|-390,1460|-370,1500|-380,1490|-390,1530|-370,1520|-370,1660|-380,1700|-390,1690|-370,1730|-380,1720|-390,1760|-370,-740|-380,-750|-390,-710|-370,-720|-380,-680|-390,-640|-370,-650|-380,-610|-390,-620|-370,-580|-380,-540|-390,-550|-370,-510|-380,-520|-350,150|-350,220|-350,290|-350,360|-350,380|-360,1140|-370,1130|-350,1170|-360,1160|-370,1200|-350,1240|-360,1230|-370,1270|-350,1260|-360,1300|-370,1340|-350,1330|-360,1370|-370,1360|-350,1400|-360,1440|-370,1430|-350,1470|-360,1460|-370,1500|-350,1540|-360,1530|-360,1670|-370,1660|-350,1700|-360,1740|-370,1730|-350,1770|-360,1760|-370,-740|-350,-700|-360,-710|-370,-670|-350,-680|-360,-640|-370,-600|-350,-610|-360,-570|-370,-580|-350,-540|-360,-500|-370,-510|-350,130|-330,120|-340,160|-350,200|-330,190|-340,230|-350,220|-330,260|-340,300|-350,290|-330,330|-340,320|-350,360|-330,400|-340,390|-330,420|-330,1140|-340,1180|-350,1170|-330,1210|-340,1200|-350,1240|-330,1280|-340,1270|-350,1310|-330,1300|-340,1340|-350,1380|-330,1370|-340,1410|-350,1400|-330,1440|-340,1480|-350,1470|-330,1510|-340,1500|-350,1540|-350,1680|-340,1710|-350,1700|-340,1780|-350,1770|-340,-760|-330,-730|-340,-740|-350,-700|-330,-660|-340,-670|-350,-630|-330,-640|-340,-600|-350,-560|-330,-570|-340,-530|-350,-540|-330,-500|-310,140|-320,130|-330,170|-310,160|-320,200|-330,240|-310,230|-320,270|-330,260|-310,300|-320,340|-330,330|-310,370|-320,360|-330,400|-330,1120|-320,1150|-330,1140|-310,1180|-320,1220|-330,1210|-310,1250|-320,1240|-330,1280|-310,1320|-320,1310|-330,1350|-310,1340|-320,1380|-330,1420|-310,1410|-320,1450|-330,1440|-310,1480|-320,1520|-330,1510|-310,1550|-320,1540|-310,-760|-320,-720|-330,-730|-310,-690|-320,-700|-330,-660|-310,-620|-320,-630|-330,-590|-310,-600|-320,-560|-330,-520|-310,-530|-330,-500|-310,140|-290,180|-300,170|-310,210|-290,200|-300,240|-310,280|-290,270|-300,310|-310,300|-290,340|-300,380|-310,370|-290,410|-300,400|-300,1120|-310,1160|-290,1150|-300,1190|-310,1180|-290,1220|-300,1260|-310,1250|-290,1290|-300,1280|-310,1320|-290,1360|-300,1350|-310,1390|-290,1380|-300,1420|-310,1460|-290,1450|-300,1490|-310,1480|-290,1520|-310,1550|-300,-750|-310,-760|-290,-720|-300,-680|-310,-690|-290,-650|-300,-660|-310,-620|-290,-580|-300,-590|-310,-550|-290,-560|-300,-520|-280,120|-270,150|-280,140|-290,180|-270,220|-280,210|-290,250|-270,240|-280,280|-290,320|-270,310|-280,350|-290,340|-270,380|-280,420|-290,410|-290,1130|-270,1120|-280,1160|-290,1200|-270,1190|-280,1230|-290,1220|-270,1260|-280,1300|-290,1290|-270,1330|-280,1320|-290,1360|-270,1400|-280,1390|-290,1430|-270,1420|-280,1460|-290,1500|-270,1490|-280,1530|-290,1520|-290,-740|-270,-750|-280,-710|-290,-720|-270,-680|-280,-640|-290,-650|-270,-610|-280,-620|-290,-580|-270,-540|-280,-550|-290,-510|-270,-520|-250,120|-260,160|-270,150|-250,190|-260,180|-270,220|-250,260|-260,250|-270,290|-250,280|-260,320|-270,360|-250,350|-260,390|-270,380|-250,420|-260,460|-250,490|-260,480|-250,1140|-260,1130|-270,1170|-250,1160|-260,1200|-270,1240|-250,1230|-260,1270|-270,1260|-250,1300|-260,1340|-270,1330|-250,1370|-260,1360|-270,1400|-250,1440|-260,1430|-270,1470|-250,1460|-260,1500|-270,1540|-250,1530|-260,-740|-270,-700|-250,-710|-260,-670|-270,-680|-250,-640|-260,-600|-270,-610|-250,-570|-260,-580|-270,-540|-250,-500|-260,-510|-240,130|-250,120|-230,160|-240,200|-250,190|-230,230|-240,220|-250,260|-230,300|-240,290|-250,330|-230,320|-240,360|-250,400|-230,390|-240,430|-250,420|-230,460|-240,500|-250,490|-250,1140|-230,1180|-240,1170|-250,1210|-230,1200|-240,1240|-250,1280|-230,1270|-240,1310|-250,1300|-230,1340|-240,1380|-250,1370|-230,1410|-240,1400|-250,1440|-230,1480|-240,1470|-250,1510|-230,1500|-240,1540|-230,-760|-250,-730|-230,-740|-240,-700|-250,-660|-230,-670|-240,-630|-250,-640|-230,-600|-240,-560|-250,-570|-230,-530|-240,-540|-250,-500|-230,140|-210,130|-220,170|-230,160|-210,200|-220,240|-230,230|-210,270|-220,260|-230,300|-210,340|-220,330|-230,370|-210,360|-220,400|-230,440|-210,430|-220,470|-230,460|-210,500|-220,1120|-210,1150|-220,1140|-230,1180|-210,1220|-220,1210|-230,1250|-210,1240|-220,1280|-230,1320|-210,1310|-220,1350|-230,1340|-210,1380|-220,1420|-230,1410|-210,1450|-220,1440|-230,1480|-210,1520|-220,1510|-230,1550|-210,1540|-230,-760|-210,-720|-220,-730|-230,-690|-210,-700|-220,-660|-230,-620|-210,-630|-220,-590|-230,-600|-210,-560|-220,-520|-230,-530|-220,-500|-200,140|-210,180|-190,170|-200,210|-210,200|-190,240|-200,280|-210,270|-190,310|-200,300|-210,340|-190,380|-200,370|-210,410|-190,400|-200,440|-210,480|-190,470|-210,500|-190,1120|-200,1160|-210,1150|-190,1190|-200,1180|-210,1220|-190,1260|-200,1250|-210,1290|-190,1280|-200,1320|-210,1360|-190,1350|-200,1390|-210,1380|-190,1420|-200,1460|-210,1450|-190,1490|-200,1480|-210,1520|-200,1550|-190,-820|-200,-780|-190,-750|-200,-760|-210,-720|-190,-680|-200,-690|-210,-650|-190,-660|-200,-620|-210,-580|-190,-590|-200,-550|-210,-560|-190,-520|-200,-480|-190,-450|-200,-460|-190,-380|-200,-390|-190,-360|-170,120|-190,150|-170,140|-180,180|-190,220|-170,210|-180,250|-190,240|-170,280|-180,320|-190,310|-170,350|-180,340|-190,380|-170,420|-180,410|-190,450|-170,440|-180,480|-180,1130|-190,1120|-170,1160|-180,1200|-190,1190|-170,1230|-180,1220|-190,1260|-170,1300|-180,1290|-190,1330|-170,1320|-180,1360|-190,1400|-170,1390|-180,1430|-190,1420|-170,1460|-180,1500|-190,1490|-170,1530|-180,1520|-180,-810|-190,-820|-170,-780|-180,-740|-190,-750|-170,-710|-180,-720|-190,-680|-170,-640|-180,-650|-190,-610|-170,-620|-180,-580|-190,-540|-170,-550|-180,-510|-190,-520|-170,-480|-180,-440|-190,-450|-170,-410|-180,-420|-190,-380|-180,-350|-170,120|-150,160|-160,150|-170,190|-150,180|-160,220|-170,260|-150,250|-160,290|-170,280|-150,320|-160,360|-170,350|-150,390|-160,380|-170,420|-150,460|-160,500|-170,1140|-150,1130|-160,1170|-170,1160|-150,1200|-160,1240|-170,1230|-150,1270|-160,1260|-170,1300|-150,1340|-160,1330|-170,1370|-150,1360|-160,1400|-170,1440|-150,1430|-160,1470|-170,1460|-150,1500|-160,1540|-170,1530|-170,-800|-150,-810|-160,-770|-170,-780|-150,-740|-160,-700|-170,-710|-150,-670|-160,-680|-170,-640|-150,-600|-160,-610|-170,-570|-150,-580|-160,-540|-170,-500|-150,-510|-160,-470|-170,-480|-150,-440|-160,-400|-170,-410|-150,-370|-160,-380|-130,130|-140,120|-150,160|-130,200|-140,190|-150,230|-130,220|-140,260|-150,300|-130,290|-140,330|-150,320|-130,360|-140,400|-150,440|-130,480|-140,470|-130,500|-140,1140|-150,1180|-130,1170|-140,1210|-150,1200|-130,1240|-140,1280|-150,1270|-130,1310|-140,1300|-150,1340|-130,1380|-140,1370|-150,1410|-130,1400|-140,1440|-150,1480|-130,1470|-140,1510|-150,1500|-130,1540|-140,-800|-150,-760|-130,-770|-140,-730|-150,-740|-130,-700|-140,-660|-150,-670|-130,-630|-140,-640|-150,-600|-130,-560|-140,-570|-150,-530|-130,-540|-140,-500|-150,-460|-130,-470|-140,-430|-150,-440|-130,-400|-140,-360|-150,-370|-120,140|-130,130|-110,170|-120,160|-130,200|-110,240|-120,230|-130,270|-110,260|-120,300|-130,340|-110,380|-120,420|-130,410|-110,450|-120,440|-130,480|-110,520|-120,510|-110,1120|-130,1150|-110,1140|-120,1180|-130,1220|-110,1210|-120,1250|-130,1240|-110,1280|-120,1320|-130,1310|-110,1350|-120,1340|-130,1380|-110,1420|-120,1410|-130,1450|-110,1440|-120,1480|-130,1520|-110,1510|-120,1550|-130,1540|-110,-820|-130,-790|-110,-800|-120,-760|-130,-720|-110,-730|-120,-690|-130,-700|-110,-660|-120,-620|-130,-630|-110,-590|-120,-600|-130,-560|-110,-520|-120,-530|-130,-490|-110,-500|-120,-460|-130,-420|-110,-430|-120,-390|-130,-400|-110,-360|-100,180|-110,170|-90,210|-100,200|-110,240|-90,280|-100,320|-110,360|-90,350|-100,390|-110,380|-90,420|-100,460|-110,450|-90,490|-100,480|-110,520|-110,1120|-100,1150|-110,1190|-100,1220|-110,1260|-100,1290|-110,1280|-100,1360|-110,1350|-100,1380|-110,1420|-90,1460|-100,1450|-110,1490|-90,1480|-100,1520|-90,1550|-110,-820|-90,-780|-100,-790|-110,-750|-90,-760|-100,-720|-110,-680|-90,-690|-100,-650|-110,-660|-90,-620|-100,-580|-110,-590|-90,-550|-100,-560|-110,-520|-90,-480|-100,-490|-110,-450|-90,-460|-100,-420|-110,-380|-90,-390|-100,-350|-110,-360|-70,180|-80,220|-90,260|-70,300|-80,290|-90,330|-70,320|-80,360|-90,400|-70,390|-80,430|-90,420|-70,460|-80,500|-90,490|-80,520|-80,960|-70,990|-80,1030|-70,1060|-80,1100|-70,1130|-80,1120|-70,1200|-80,1190|-70,1220|-80,1260|-70,1290|-80,1330|-70,1360|-80,1400|-70,1430|-80,1420|-90,1460|-70,1500|-80,1490|-90,1530|-70,1520|-70,-810|-80,-820|-90,-780|-70,-740|-80,-750|-90,-710|-70,-720|-80,-680|-90,-640|-70,-650|-80,-610|-90,-620|-70,-580|-80,-540|-90,-550|-70,-510|-80,-520|-90,-480|-70,-440|-80,-450|-90,-410|-70,-420|-80,-380|-70,-350|-70,160|-50,200|-60,240|-70,230|-50,270|-60,260|-70,300|-50,340|-60,330|-70,370|-50,360|-60,400|-70,440|-50,430|-60,470|-70,460|-50,500|-70,970|-50,960|-60,1000|-70,1040|-50,1030|-60,1070|-70,1060|-50,1100|-60,1140|-70,1130|-50,1170|-60,1160|-70,1200|-50,1240|-60,1230|-70,1270|-50,1260|-60,1300|-70,1340|-50,1330|-60,1370|-70,1360|-50,1400|-60,1440|-70,1430|-50,1470|-60,1460|-70,1500|-50,1540|-60,1530|-60,-800|-70,-810|-50,-770|-60,-780|-70,-740|-50,-700|-60,-710|-70,-670|-50,-680|-60,-640|-70,-600|-50,-610|-60,-570|-70,-580|-50,-540|-60,-500|-70,-510|-50,-470|-60,-480|-70,-440|-50,-400|-60,-410|-70,-370|-50,-380|-50,180|-30,170|-40,210|-50,200|-30,240|-40,280|-50,270|-30,310|-40,300|-50,340|-30,380|-40,370|-50,410|-30,400|-40,440|-50,480|-30,470|-40,510|-50,500|-30,980|-40,970|-50,1010|-30,1000|-40,1040|-50,1080|-30,1070|-40,1110|-50,1100|-30,1140|-40,1180|-50,1170|-30,1210|-40,1200|-50,1240|-30,1280|-40,1270|-50,1310|-30,1300|-40,1340|-50,1380|-30,1370|-40,1410|-50,1400|-30,1440|-40,1480|-50,1470|-30,1510|-40,1500|-50,1540|-30,-800|-40,-760|-50,-770|-30,-730|-40,-740|-50,-700|-30,-660|-40,-670|-50,-630|-30,-640|-40,-600|-50,-560|-30,-570|-40,-530|-50,-540|-30,-500|-40,-460|-50,-470|-30,-430|-40,-440|-50,-400|-30,-360|-40,-370|-20,180|-30,220|-10,210|-20,250|-30,240|-10,280|-20,320|-30,310|-10,350|-20,340|-30,380|-10,420|-20,410|-30,450|-10,440|-20,480|-30,520|-10,510|-10,950|-30,980|-10,1020|-20,1010|-30,1050|-10,1040|-20,1080|-30,1120|-10,1110|-20,1150|-30,1140|-10,1180|-20,1220|-30,1210|-10,1250|-20,1240|-30,1280|-10,1320|-20,1310|-30,1350|-10,1340|-20,1380|-30,1420|-10,1410|-20,1450|-30,1440|-10,1480|-20,1520|-30,1510|-10,1550|-20,1540|-30,-820|-20,-790|-30,-800|-10,-760|-20,-720|-30,-730|-10,-690|-20,-700|-30,-660|-10,-620|-20,-630|-30,-590|-10,-600|-20,-560|-30,-520|-10,-530|-20,-490|-30,-500|-10,-460|-20,-420|-30,-430|-10,-390|-20,-400|-30,-360|10,160|-10,190|10,180|0,220|-10,260|10,250|0,290|-10,280|10,320|0,360|-10,350|10,390|0,380|-10,420|10,460|0,450|-10,490|10,480|0,520|0,960|-10,950|10,990|0,980|-10,1020|10,1060|0,1050|-10,1090|10,1080|0,1120|-10,1160|10,1150|0,1190|-10,1180|10,1220|0,1260|-10,1250|10,1290|0,1280|-10,1320|10,1360|0,1350|-10,1390|10,1380|0,1420|-10,1460|0,1490|-10,1480|-10,1550|0,-820|-10,-780|10,-790|0,-750|-10,-760|10,-720|0,-680|-10,-690|10,-650|0,-660|-10,-620|10,-580|0,-590|-10,-550|10,-560|0,-520|-10,-480|10,-490|0,-450|-10,-460|10,-420|0,-380|-10,-390|10,-350|0,-360|10,160|30,200|20,190|10,230|30,220|20,260|10,300|30,290|20,330|10,320|30,360|20,400|10,390|30,430|20,420|10,460|30,500|20,490|30,520|30,960|20,1000|10,990|30,1030|20,1020|10,1060|30,1100|20,1090|10,1130|30,1120|20,1160|10,1200|30,1190|20,1230|10,1220|30,1260|20,1300|10,1290|30,1330|20,1320|10,1360|30,1400|20,1390|10,-810|30,-820|20,-780|10,-740|30,-750|20,-710|10,-720|30,-680|20,-640|10,-650|30,-610|20,-620|10,-580|30,-540|20,-550|10,-510|30,-520|20,-480|10,-440|30,-450|20,-410|10,-420|30,-380|10,-350|40,0|50,30|40,70|50,100|40,140|50,170|40,160|30,200|50,240|40,230|30,270|50,260|40,300|30,340|50,330|40,370|30,360|50,400|40,440|30,430|50,470|40,460|30,500|40,970|30,960|50,1000|40,1040|30,1030|50,1070|40,1060|30,1100|50,1140|40,1130|30,1170|50,1160|40,1200|30,1240|50,1230|40,1270|30,1260|50,1300|40,1340|30,1330|50,1370|40,1360|30,1400|50,-800|40,-810|30,-770|50,-780|40,-740|30,-700|50,-710|40,-670|30,-680|50,-640|40,-600|30,-610|50,-570|40,-580|30,-540|50,-500|40,-510|30,-470|50,-480|40,-440|30,-400|50,-410|40,-370|30,-380|40,-160|50,-130|40,-90|50,-60|40,-20|50,10|70,0|60,40|50,80|70,70|60,110|50,100|70,140|60,180|50,170|70,210|60,200|50,240|70,280|60,270|50,310|70,300|60,340|50,380|70,370|60,410|50,400|70,440|60,480|50,470|70,510|60,500|50,980|70,970|60,1010|50,1000|70,1040|60,1080|50,1070|70,1110|60,1100|50,1140|70,1180|60,1170|50,1210|70,1200|60,1240|50,1280|70,1270|60,1310|50,1300|70,1340|60,1380|50,1370|60,1400|50,-800|70,-760|60,-770|50,-730|70,-740|60,-700|50,-660|70,-670|60,-630|50,-640|70,-600|50,-570|50,-500|50,-430|50,-360|70,-180|50,-150|70,-160|60,-120|50,-80|70,-90|60,-50|50,-60|70,-20|90,20|80,10|70,50|90,40|80,80|70,120|90,110|80,150|70,140|90,180|80,220|70,210|90,250|80,240|70,280|90,320|80,310|70,350|90,340|80,380|70,420|90,410|80,450|70,440|90,480|80,520|70,510|80,680|90,710|80,750|90,780|80,820|90,850|80,840|90,920|80,910|70,950|90,940|80,980|70,1020|90,1010|80,1050|70,1040|90,1080|80,1120|70,1110|90,1150|80,1140|70,1180|90,1220|80,1210|70,1250|90,1240|80,1280|70,1320|90,1310|80,1350|70,1340|90,1380|70,-760|90,-720|80,-730|70,-690|90,-700|80,-660|70,-620|90,-630|70,-600|70,-180|90,-140|80,-150|70,-110|90,-120|80,-80|70,-40|90,-50|80,-10|100,-20|90,20|110,60|100,50|90,90|110,80|100,120|90,160|110,150|100,190|90,180|110,220|100,260|90,250|110,290|100,280|90,320|110,360|100,350|90,390|110,380|100,420|90,460|110,450|100,490|90,480|110,520|110,660|100,650|90,690|110,680|100,720|90,760|110,750|100,790|90,780|110,820|100,860|90,850|110,890|100,880|90,920|110,960|100,950|90,990|110,980|100,1020|90,1060|110,1050|100,1090|90,1080|110,1120|100,1160|90,1150|110,1190|100,1180|90,1220|110,1260|100,1250|90,1290|110,1280|100,1320|90,1360|110,1350|100,1390|90,1380|100,-780|110,-750|100,-760|90,-720|110,-680|100,-690|90,-650|110,-660|100,-620|110,-170|100,-180|90,-140|110,-100|100,-110|90,-70|110,-80|100,-40|90,0|110,-10|130,30|120,20|110,60|130,100|120,90|110,130|130,120|120,160|110,200|130,190|120,230|110,220|130,260|120,300|110,290|130,330|120,320|110,360|130,400|120,390|110,430|130,420|120,460|110,500|130,490|120,530|110,520|130,560|120,600|110,660|130,700|120,690|110,730|130,720|120,760|110,800|130,790|120,830|110,820|130,860|120,900|110,890|130,930|120,920|110,960|130,1000|120,990|110,1030|130,1020|120,1060|110,1100|130,1090|120,1130|110,1120|130,1160|120,1200|110,1190|130,1230|120,1220|110,1260|130,1300|120,1290|110,1330|130,1320|120,1360|110,1400|130,1390|130,-780|120,-740|110,-750|130,-710|120,-720|110,-680|130,-640|120,-650|110,-610|130,-620|120,-160|110,-170|130,-130|120,-140|110,-100|130,-60|120,-70|110,-30|130,-40|150,0|140,40|130,30|150,70|140,60|130,100|150,140|140,130|130,170|150,160|140,200|130,240|150,230|140,270|130,260|150,300|140,340|130,330|150,370|140,360|130,400|150,440|140,430|130,470|150,460|140,500|130,540|150,530|140,570|130,560|150,600|150,670|140,660|130,700|150,740|140,730|130,770|150,760|140,800|130,840|150,830|140,870|130,860|150,900|140,940|130,930|150,970|140,960|130,1000|150,1040|140,1030|130,1070|150,1060|140,1100|130,1140|150,1130|140,1170|130,1160|150,1200|140,1240|130,1230|150,1270|140,1260|130,1300|150,1340|140,1330|130,1370|150,1360|140,1400|150,-1180|150,-1110|150,-1040|150,-970|150,-900|150,-880|150,-810|130,-780|130,-710|130,-640|150,-160|140,-120|130,-130|150,-90|140,-100|130,-60|150,-20|170,-30|160,10|150,0|170,40|160,80|150,70|170,110|160,100|150,140|170,180|160,170|150,210|170,200|160,240|150,280|170,270|160,310|150,300|170,340|160,380|150,370|170,410|160,400|150,440|170,480|160,470|150,510|170,500|160,540|150,580|170,570|150,600|160,680|150,670|170,710|160,700|150,740|170,780|160,770|150,810|170,800|160,840|150,880|170,870|160,910|150,900|170,940|160,980|150,970|170,1010|160,1000|150,1040|170,1080|160,1070|150,1110|170,1100|160,1140|150,1180|170,1170|160,1210|150,1200|170,1240|160,1280|150,1270|170,1310|160,1300|150,1340|170,1380|160,1370|170,1400|170,-1160|160,-1170|150,-1130|170,-1140|160,-1100|150,-1060|170,-1070|160,-1030|150,-1040|170,-1000|160,-960|150,-970|170,-930|160,-940|150,-900|170,-860|160,-870|150,-830|170,-840|160,-800|150,-180|160,-150|150,-160|170,-120|160,-80|150,-90|170,-50|160,-60|180,-20|170,20|190,10|180,50|170,40|190,80|180,120|170,110|190,150|180,140|170,180|190,220|180,210|170,250|190,240|180,280|170,320|190,310|180,350|170,340|190,380|180,420|170,410|190,450|180,440|170,480|190,520|180,510|170,550|190,540|180,580|180,650|190,680|180,720|170,710|190,750|180,740|170,780|190,820|180,810|170,850|190,840|180,880|170,920|190,910|180,950|170,940|190,980|180,1020|170,1010|190,1050|180,1040|170,1080|190,1120|180,1110|170,1150|190,1140|180,1180|170,1220|190,1210|180,1250|170,1240|190,1280|180,1320|170,1310|190,1350|180,1340|170,1380|170,-1160|190,-1120|180,-1130|170,-1090|190,-1100|180,-1060|170,-1020|190,-1030|180,-990|170,-1000|190,-960|180,-920|170,-930|190,-890|180,-900|170,-860|190,-820|180,-830|190,-800|180,-180|170,-140|190,-150|180,-110|170,-120|190,-80|180,-40|170,-50|190,-10|210,-20|200,20|190,60|210,50|200,90|190,80|210,120|200,160|190,150|210,190|200,180|190,220|210,260|200,250|190,290|210,280|200,320|190,360|210,350|200,390|190,380|210,420|200,460|190,450|210,490|200,480|190,520|210,560|200,550|190,590|210,580|190,660|210,650|200,690|190,680|210,720|200,760|190,750|210,790|200,780|190,820|210,860|200,850|190,890|210,880|200,920|190,960|210,950|200,990|190,980|210,1020|200,1060|190,1050|210,1090|200,1080|190,1120|210,1160|200,1150|190,1190|210,1180|200,1220|190,1260|210,1250|200,1290|190,1280|210,1320|200,1360|190,1350|210,1390|200,1380|200,1450|200,-1180|210,-1150|200,-1160|190,-1120|210,-1080|200,-1090|190,-1050|210,-1060|200,-1020|190,-980|210,-990|200,-950|190,-960|210,-920|200,-880|190,-890|210,-850|200,-860|190,-820|190,-170|210,-180|200,-140|190,-100|210,-110|200,-70|190,-80|210,-40|230,0|220,-10|210,30|230,20|220,60|210,100|230,90|220,130|210,120|230,160|220,200|210,190|230,230|220,220|210,260|230,300|220,290|210,330|230,320|220,360|210,400|230,390|220,430|210,420|230,460|220,500|210,490|230,530|220,520|210,560|230,600|220,590|220,660|210,700|230,690|220,730|210,720|230,760|220,800|210,790|230,830|220,820|210,860|230,900|220,890|210,930|230,920|220,960|210,1000|230,990|220,1030|210,1020|230,1060|220,1100|210,1090|230,1130|220,1120|210,1160|230,1200|220,1190|210,1230|230,1220|220,1260|210,1300|230,1290|220,1330|210,1320|230,1360|220,1400|210,1390|230,1430|220,1420|230,-1180|220,-1140|210,-1150|230,-1110|220,-1120|210,-1080|230,-1040|220,-1050|210,-1010|230,-1020|220,-980|210,-940|230,-950|220,-910|210,-920|230,-880|220,-840|210,-850|230,-810|220,-820|230,-160|220,-170|210,-130|230,-140|220,-100|210,-60|230,-70|220,-30|240,-40|230,0|250,40|240,30|230,70|250,60|240,100|230,140|250,130|240,170|230,160|250,200|240,240|230,230|250,270|240,260|230,300|250,340|240,330|230,370|250,360|240,400|230,440|250,430|240,470|230,460|250,500|240,540|230,530|250,570|240,560|230,600|230,670|250,660|240,700|230,740|250,730|240,770|230,760|250,800|240,840|230,830|250,870|240,860|230,900|250,940|240,930|230,970|250,960|240,1000|230,1040|250,1030|240,1070|230,1060|250,1100|240,1140|230,1130|250,1170|240,1160|230,1200|250,1240|240,1230|230,1270|250,1260|240,1300|230,1340|250,1330|240,1370|230,1360|250,1400|240,1440|230,1430|250,-1370|250,-1300|250,-1280|250,-1210|240,-1170|230,-1180|250,-1140|240,-1100|230,-1110|250,-1070|240,-1080|230,-1040|250,-1000|240,-1010|230,-970|250,-980|240,-940|230,-900|250,-910|240,-870|230,-880|250,-840|240,-800|250,-720|250,-700|250,-630|250,-560|230,-160|250,-120|240,-130|230,-90|250,-100|240,-60|230,-20|250,-30|270,10|260,0|250,40|270,80|260,70|250,110|270,100|260,140|250,180|270,170|260,210|250,200|270,240|260,280|250,270|270,310|260,300|250,340|270,380|260,370|250,410|270,400|260,440|250,480|270,470|260,510|250,500|270,540|260,580|250,570|260,600|270,680|260,670|250,710|270,700|260,740|250,780|270,770|260,810|250,800|270,840|260,880|250,870|270,910|260,900|250,940|270,980|260,970|250,1010|270,1000|260,1040|250,1080|270,1070|260,1110|250,1100|270,1140|260,1180|250,1170|270,1210|260,1200|250,1240|270,1280|260,1270|250,1310|270,1300|260,1340|250,1380|270,1370|260,1410|250,1400|270,1440|270,-1400|260,-1360|250,-1370|270,-1330|260,-1340|250,-1300|270,-1260|260,-1270|250,-1230|270,-1240|260,-1200|250,-1160|270,-1170|260,-1130|250,-1140|270,-1100|260,-1060|250,-1070|270,-1030|260,-1040|250,-1000|270,-960|260,-970|250,-930|270,-940|260,-900|250,-860|270,-820|260,-780|250,-790|270,-750|260,-760|250,-720|270,-680|260,-690|250,-650|270,-660|260,-620|250,-580|270,-590|260,-550|250,-560|270,-520|260,-180|270,-150|260,-160|250,-120|270,-80|260,-90|250,-50|270,-60|290,-20|280,20|270,10|290,50|280,40|270,80|290,120|280,110|270,150|290,140|280,180|270,220|290,210|280,250|270,240|290,280|280,320|270,310|290,350|280,340|270,380|290,420|280,410|270,450|290,440|280,480|270,520|290,510|280,550|270,540|290,580|290,650|270,680|290,720|280,710|270,750|290,740|280,780|270,820|290,810|280,850|270,840|290,880|280,920|270,910|290,950|280,940|270,980|290,1020|280,1010|270,1050|290,1040|280,1080|270,1120|290,1110|280,1150|270,1140|290,1180|280,1220|270,1210|290,1250|280,1240|270,1280|290,1320|280,1310|270,1350|290,1340|280,1380|270,1420|290,1410|280,1450|270,1440|280,-1390|270,-1400|290,-1360|280,-1320|270,-1330|290,-1290|280,-1300|270,-1260|290,-1220|280,-1230|270,-1190|290,-1200|280,-1160|270,-1120|290,-1130|280,-1090|270,-1100|290,-1060|280,-1020|270,-1030|290,-990|280,-1000|270,-960|290,-920|280,-880|270,-840|290,-850|280,-810|270,-820|290,-780|280,-740|270,-750|290,-710|280,-720|270,-680|290,-640|280,-650|270,-610|290,-620|280,-580|270,-540|290,-550|270,-520|290,-180|280,-140|270,-150|290,-110|280,-120|270,-80|290,-40|310,-50|300,-10|290,-20|310,20|300,60|290,50|310,90|300,80|290,120|310,160|300,150|290,190|310,180|300,220|290,260|310,250|300,290|290,280|310,320|300,360|290,350|310,390|300,380|290,420|310,460|300,450|290,490|310,480|300,520|290,560|310,550|300,590|290,580|300,660|290,650|310,690|300,680|290,720|310,760|300,750|290,790|310,780|300,820|290,860|310,850|300,890|290,880|310,920|300,960|290,950|310,990|300,980|290,1020|310,1060|300,1050|290,1090|310,1080|300,1120|290,1160|310,1150|300,1190|290,1180|310,1220|300,1260|290,1250|310,1290|300,1280|290,1320|310,1360|300,1350|290,1390|310,1380|300,1420|310,1450|290,-1380|310,-1390|300,-1350|290,-1360|310,-1320|300,-1280|290,-1290|310,-1250|300,-1260|290,-1220|310,-1180|300,-1190|290,-1150|310,-1160|300,-1120|290,-1080|310,-1090|300,-1050|290,-1060|310,-1020|300,-980|290,-940|310,-900|300,-910|290,-870|310,-880|300,-840|290,-800|310,-810|300,-770|290,-780|310,-740|300,-700|290,-710|310,-670|300,-680|290,-640|310,-600|300,-610|290,-570|310,-580|300,-540|300,-170|290,-180|310,-140|300,-100|290,-110|310,-70|300,-80|320,-40|310,0|330,-10|320,30|310,20|330,60|320,100|310,90|330,130|320,120|310,160|330,200|320,190|310,230|330,220|320,260|310,300|330,290|320,330|310,320|330,360|320,400|310,390|330,430|320,420|310,460|330,500|320,490|310,530|330,520|320,560|310,600|330,590|330,660|320,700|310,690|330,730|320,720|310,760|330,800|320,790|310,830|330,820|320,860|310,900|330,890|320,930|310,920|330,960|320,1000|310,990|330,1030|320,1020|310,1060|330,1100|320,1090|310,1130|330,1120|320,1160|310,1200|330,1190|320,1230|310,1220|330,1260|320,1300|310,1290|330,1330|320,1320|310,1360|330,1400|320,1390|310,1430|330,1420|320,1460|320,-1380|310,-1340|330,-1350|320,-1310|310,-1320|330,-1280|320,-1240|310,-1250|330,-1210|320,-1220|310,-1180|330,-1140|320,-1150|310,-1110|330,-1120|320,-1080|310,-1040|330,-1000|320,-960|310,-970|330,-930|320,-940|310,-900|330,-860|320,-870|310,-830|330,-840|320,-800|310,-760|330,-770|320,-730|310,-740|330,-700|320,-660|310,-670|330,-630|320,-640|310,-600|330,-560|320,-570|310,-530|330,-540|310,-160|330,-170|320,-130|310,-140|330,-100|320,-60|310,-70|330,-30|350,-40|340,0|330,40|350,30|340,70|330,60|350,100|340,140|330,130|350,170|340,160|330,200|350,240|340,230|330,270|350,260|340,300|330,340|350,330|340,370|330,360|350,400|340,440|330,430|350,470|340,460|330,500|350,540|340,530|330,570|350,560|340,600|350,630|340,670|330,660|350,700|340,740|330,730|350,770|340,760|330,800|350,840|340,830|330,870|350,860|340,900|330,940|350,930|340,970|330,960|350,1000|340,1040|330,1030|350,1070|340,1060|330,1100|350,1140|340,1130|330,1170|350,1160|340,1200|330,1240|350,1230|340,1270|330,1260|350,1300|340,1340|330,1330|350,1370|340,1360|330,1400|350,1440|340,1430|350,1460|350,-1400|330,-1370|350,-1380|340,-1340|330,-1300|350,-1310|340,-1270|330,-1280|350,-1240|340,-1200|330,-1210|350,-1170|340,-1180|330,-1140|350,-1100|340,-1060|330,-1020|350,-1030|340,-990|330,-1000|350,-960|340,-920|330,-930|350,-890|340,-900|330,-860|350,-820|340,-830|330,-790|350,-800|340,-760|330,-720|350,-730|340,-690|330,-700|350,-660|340,-620|330,-630|350,-590|340,-600|330,-560|350,-520|340,-530|340,-160|330,-120|350,-130|340,-90|330,-100|350,-60|370,-20|360,-30|350,10|370,0|360,40|350,80|370,70|360,110|350,100|370,140|360,180|350,170|370,210|360,200|350,240|370,280|360,270|350,310|370,300|360,340|350,380|370,370|360,410|350,400|370,440|360,480|350,470|370,510|360,500|350,540|370,580|360,570|350,610|370,600|360,640|350,680|370,670|360,710|350,700|370,740|360,780|350,770|370,810|360,800|350,840|370,880|360,870|350,910|370,900|350,980|360,1010|350,1000|370,1040|360,1080|350,1070|370,1110|360,1100|350,1140|370,1180|360,1170|350,1210|370,1200|360,1240|350,1280|370,1270|360,1310|350,1300|370,1340|360,1380|350,1370|370,1410|360,1400|350,1440|350,-1400|370,-1360|360,-1370|350,-1330|370,-1340|360,-1300|350,-1260|370,-1270|360,-1230|350,-1240|370,-1200|360,-1160|350,-1120|370,-1080|360,-1090|350,-1050|370,-1060|360,-1020|350,-980|370,-990|360,-950|350,-960|370,-920|360,-880|350,-890|370,-850|360,-860|350,-820|370,-780|360,-790|350,-750|370,-760|360,-720|350,-680|370,-690|360,-650|350,-660|370,-620|360,-580|350,-590|370,-550|360,-560|350,-520|370,-180|350,-150|370,-160|360,-120|350,-80|370,-90|360,-50|380,-60|370,-20|390,20|380,10|370,50|390,40|380,80|370,120|390,110|380,150|370,140|390,180|380,220|370,210|390,250|380,240|370,280|390,320|380,310|370,350|390,340|380,380|370,420|390,410|380,450|370,440|390,480|380,520|370,510|390,550|380,540|370,580|390,620|380,610|370,650|390,640|380,680|370,720|390,710|380,750|370,740|390,780|380,820|370,810|390,850|380,840|370,880|370,1020|390,1010|380,1050|370,1040|390,1080|380,1120|370,1110|390,1150|380,1140|370,1180|390,1220|380,1210|370,1250|390,1240|380,1280|370,1320|390,1310|380,1350|370,1340|390,1380|380,1420|370,1410|390,1450|380,1440|390,-1390|380,-1400|370,-1360|390,-1320|380,-1330|370,-1290|390,-1300|380,-1260|370,-1220|390,-1180|380,-1140|370,-1150|390,-1110|380,-1120|370,-1080|390,-1040|380,-1050|370,-1010|390,-1020|380,-980|370,-940|390,-950|380,-910|370,-920|390,-880|380,-840|370,-850|390,-810|380,-820|370,-780|390,-740|380,-750|370,-710|390,-720|380,-680|370,-640|390,-650|380,-610|370,-620|390,-580|380,-540|370,-550|380,-520|370,-180|370,-110|380,-80|370,-40|390,-50|410,-10|400,-20|390,20|410,60|400,50|390,90|410,80|400,120|390,160|410,150|400,190|390,180|410,220|400,260|390,250|410,290|400,280|390,320|410,360|400,350|390,390|410,380|400,420|390,460|410,450|400,490|390,480|410,520|400,560|390,550|410,590|400,580|390,620|410,660|400,650|390,690|410,680|400,720|390,760|410,750|400,790|390,780|410,820|400,860|390,850|410,890|400,880|400,1020|390,1060|410,1050|400,1090|390,1080|410,1120|400,1160|390,1150|410,1190|400,1180|390,1220|410,1260|400,1250|390,1290|410,1280|400,1320|390,1360|410,1350|400,1390|390,1380|410,1420|400,1460|390,1450|400,-1380|390,-1390|410,-1350|400,-1360|390,-1320|410,-1280|400,-1240|390,-1200|410,-1210|400,-1170|390,-1180|410,-1140|400,-1100|390,-1110|410,-1070|400,-1080|390,-1040|410,-1000|400,-1010|390,-970|410,-980|400,-940|390,-900|410,-910|400,-870|390,-880|410,-840|400,-800|390,-810|410,-770|400,-780|390,-740|410,-700|400,-710|390,-670|410,-680|400,-640|390,-600|410,-610|400,-570|390,-580|410,-540|410,-100|390,-70|410,-80|430,-40|420,0|410,-10|430,30|420,20|410,60|430,100|420,90|410,130|430,120|420,160|410,200|430,190|420,230|410,220|430,260|420,300|410,290|420,320|410,360|420,390|410,430|420,460|410,500|420,530|410,520|430,560|420,600|410,590|430,630|420,620|410,660|430,700|420,690|410,730|430,720|420,760|410,800|430,790|420,830|410,820|430,860|420,900|410,890|430,1000|410,1030|430,1020|420,1060|410,1100|430,1090|420,1130|410,1120|430,1160|420,1200|410,1190|430,1230|420,1220|410,1260|430,1300|420,1290|410,1330|430,1320|420,1360|410,1400|430,1390|420,1430|410,1420|430,1460|430,-1380|420,-1340|410,-1300|430,-1260|420,-1270|410,-1230|430,-1240|420,-1200|410,-1160|430,-1170|420,-1130|410,-1140|430,-1100|420,-1060|410,-1070|430,-1030|420,-1040|410,-1000|430,-960|420,-970|410,-930|430,-940|420,-900|410,-860|430,-870|420,-830|410,-840|430,-800|420,-760|410,-770|430,-730|420,-740|410,-700|430,-660|420,-670|410,-630|430,-640|420,-600|410,-560|430,-570|420,-530|410,-540|410,-100|430,-60|450,-70|440,-30|430,-40|450,0|440,40|430,30|450,70|440,60|430,100|450,140|440,130|430,170|450,160|440,200|430,240|450,230|440,270|430,260|450,300|440,500|430,540|450,530|440,570|430,560|450,600|440,640|430,630|450,670|440,660|430,700|450,740|440,730|430,770|450,760|440,800|430,840|450,830|440,870|430,860|450,900|430,1000|450,1040|440,1030|430,1070|450,1060|440,1100|430,1140|450,1130|440,1170|430,1160|450,1200|440,1240|430,1230|450,1270|440,1260|430,1300|450,1340|440,1330|430,1370|450,1360|440,1400|430,1440|450,1430|430,1460|430,-1400|450,-1360|440,-1320|430,-1330|450,-1290|440,-1300|430,-1260|450,-1220|440,-1230|430,-1190|450,-1200|440,-1160|430,-1120|450,-1130|440,-1090|430,-1100|450,-1060|440,-1020|430,-1030|450,-990|440,-1000|430,-960|450,-920|440,-930|430,-890|450,-900|440,-860|430,-820|450,-830|440,-790|430,-800|450,-760|440,-720|430,-730|450,-690|440,-700|430,-660|450,-620|440,-630|430,-590|450,-600|440,-560|430,-520|450,-530|450,-90|440,-100|460,-60|450,-20|470,-30|460,10|450,0|470,40|460,80|450,70|470,110|460,100|450,140|470,180|460,170|450,210|470,200|460,240|450,280|470,270|460,310|450,300|450,510|470,500|460,540|450,580|470,570|460,610|450,600|470,640|460,680|450,670|470,710|460,700|450,740|470,780|460,770|450,810|470,800|460,840|450,880|470,870|450,900|470,1010|460,1000|450,1040|470,1080|460,1070|450,1110|470,1100|460,1140|450,1180|470,1170|460,1210|450,1200|470,1240|460,1280|450,1270|470,1310|460,1300|450,1340|470,1380|460,1370|450,1410|470,1400|460,1440|450,-1380|470,-1390|460,-1350|450,-1360|470,-1320|460,-1280|450,-1290|470,-1250|460,-1260|450,-1220|470,-1180|460,-1190|450,-1150|470,-1160|460,-1120|450,-1080|470,-1090|460,-1050|450,-1060|470,-1020|460,-980|450,-990|470,-950|460,-960|450,-920|470,-880|460,-890|450,-850|470,-860|460,-820|450,-780|470,-790|460,-750|450,-760|470,-720|460,-680|450,-690|470,-650|460,-660|450,-620|470,-580|460,-590|450,-550|470,-560|460,-520|460,-80|450,-90|470,-50|490,-60|480,-20|470,20|490,10|480,50|470,40|490,80|480,120|470,110|490,150|480,140|470,180|490,220|480,210|470,250|490,240|480,280|470,320|490,310|490,520|480,510|470,550|490,540|480,580|470,620|490,610|480,650|470,640|490,680|480,720|470,710|490,750|480,740|470,780|490,820|480,810|470,850|490,840|480,880|480,1020|470,1010|490,1050|480,1040|470,1080|490,1120|480,1110|470,1150|490,1140|480,1180|470,1220|490,1210|480,1250|470,1240|490,1280|480,1320|470,1310|490,1350|480,1340|470,1380|490,1420|480,1410|470,1450|490,1440|480,-1380|470,-1340|490,-1350|480,-1310|470,-1320|490,-1280|480,-1240|470,-1250|490,-1210|480,-1220|470,-1180|490,-1140|480,-1150|470,-1110|490,-1120|480,-1080|470,-1040|490,-1050|480,-1010|470,-1020|490,-980|480,-940|470,-950|490,-910|480,-920|470,-880|490,-840|480,-850|470,-810|490,-820|480,-780|470,-740|490,-750|480,-710|470,-720|490,-680|480,-640|470,-650|490,-610|480,-620|470,-580|490,-540|480,-550|490,-520|490,-80|510,-40|500,-50|490,-10|510,-20|500,20|490,60|510,50|500,90|490,80|510,120|500,160|490,150|510,190|500,180|490,220|510,260|500,250|490,290|510,280|500,320|510,350|500,390|510,420|500,460|510,490|500,480|490,520|510,560|500,550|490,590|510,580|500,620|490,660|510,650|500,690|490,680|510,720|500,760|490,750|510,790|500,780|490,820|510,860|500,850|490,890|510,880|500,920|510,950|500,990|510,1020|500,1060|490,1050|510,1090|500,1080|490,1120|510,1160|500,1150|490,1190|510,1180|500,1220|490,1260|510,1250|500,1290|490,1280|510,1320|500,1360|490,1350|510,1390|500,1380|490,1420|510,1460|500,1450|510,1480|500,1520|510,1550|500,1590|510,1620|500,1660|510,1690|500,1680|510,1760|500,1750|500,-1780|510,-1750|500,-1760|510,-1680|500,-1690|510,-1400|490,-1370|510,-1380|500,-1340|490,-1300|510,-1310|500,-1270|490,-1280|510,-1240|500,-1200|490,-1210|510,-1170|500,-1180|490,-1140|510,-1100|500,-1110|490,-1070|510,-1080|500,-1040|490,-1000|510,-1010|500,-970|490,-980|510,-940|500,-900|490,-910|510,-870|500,-880|490,-840|510,-800|500,-810|490,-770|510,-780|500,-740|490,-700|510,-710|500,-670|490,-680|510,-640|500,-600|490,-610|510,-570|500,-580|490,-540|490,-100|510,-110|500,-70|520,-80|510,-40|530,0|520,-10|510,30|530,20|520,60|510,100|530,90|520,130|510,120|530,160|520,200|510,190|530,230|520,220|510,260|530,300|520,290|510,330|530,320|520,360|510,400|530,390|520,430|510,420|530,460|520,500|510,490|530,530|520,520|510,560|530,600|520,590|510,630|530,620|520,660|510,700|530,690|520,730|510,720|530,760|520,800|510,790|530,830|520,820|510,860|530,900|520,890|510,930|530,920|520,960|510,1000|530,990|520,1030|510,1020|530,1060|520,1100|510,1090|530,1130|520,1120|510,1160|530,1200|520,1190|510,1230|530,1220|520,1260|510,1300|530,1290|520,1330|510,1320|530,1360|520,1400|510,1390|530,1430|520,1420|510,1460|530,1500|520,1490|510,1530|530,1520|520,1560|510,1600|530,1590|520,1630|510,1620|530,1660|520,1700|510,1690|530,1730|520,1720|510,1760|530,1800|530,-1780|520,-1740|510,-1750|530,-1710|520,-1720|510,-1680|510,-1400|530,-1360|520,-1370|510,-1330|530,-1340|520,-1300|510,-1260|530,-1270|520,-1230|510,-1240|530,-1200|520,-1160|510,-1170|530,-1130|520,-1140|510,-1100|530,-1060|520,-1070|510,-1030|530,-1040|520,-1000|510,-960|530,-970|520,-930|510,-940|530,-900|520,-860|510,-870|530,-830|520,-840|510,-800|530,-760|520,-770|510,-730|530,-740|520,-700|510,-660|530,-670|520,-630|510,-640|530,-600|520,-560|510,-570|530,-530|520,-540|520,-100|510,-60|530,-70|550,-30|540,-40|530,0|550,40|540,30|530,70|550,60|540,100|530,140|550,130|540,170|530,160|550,200|540,240|530,230|550,270|540,260|530,300|550,340|540,330|530,370|550,360|540,400|530,440|550,430|540,470|530,460|550,500|540,540|530,530|550,570|540,560|530,600|550,640|540,630|530,670|550,660|540,700|530,740|550,730|540,770|530,760|550,800|540,840|530,830|550,870|540,860|530,900|550,940|540,930|530,970|550,960|540,1000|530,1040|550,1030|540,1070|530,1060|550,1100|540,1140|530,1130|550,1170|540,1160|530,1200|550,1240|540,1230|530,1270|550,1260|540,1300|530,1340|550,1330|540,1370|530,1360|550,1400|540,1440|530,1430|550,1470|540,1460|530,1500|550,1540|540,1530|530,1570|550,1560|540,1600|530,1640|550,1630|540,1670|530,1660|550,1700|540,1740|530,1730|550,1770|540,1760|530,-1800|540,-1770|530,-1780|550,-1740|540,-1700|550,-1620|550,-1600|550,-1530|550,-1460|550,-1390|540,-1400|530,-1360|550,-1320|540,-1330|530,-1290|550,-1300|540,-1260|530,-1220|550,-1230|540,-1190|530,-1200|550,-1160|540,-1120|530,-1130|550,-1090|540,-1100|530,-1060|550,-1020|540,-1030|530,-990|550,-1000|540,-960|530,-920|550,-930|540,-890|530,-900|550,-860|540,-820|530,-830|550,-790|540,-800|530,-760|550,-720|540,-730|530,-690|550,-700|540,-660|530,-620|550,-630|540,-590|530,-600|550,-560|540,-520|530,-530|530,-90|550,-100|570,-60|560,-20|550,-30|570,10|560,0|550,40|570,80|560,70|550,110|570,100|560,140|550,180|570,170|560,210|550,200|570,240|560,280|550,270|570,310|560,300|550,340|570,380|560,370|550,410|570,400|560,440|550,480|570,470|560,510|550,500|570,540|560,580|550,570|570,610|560,600|550,640|570,680|560,670|550,710|570,700|560,740|550,780|570,770|560,810|550,800|570,840|560,880|550,870|570,910|560,900|550,940|570,980|560,970|550,1010|570,1000|560,1040|550,1080|570,1070|560,1110|550,1100|570,1140|560,1180|550,1170|570,1210|560,1200|550,1240|570,1280|560,1270|550,1310|570,1300|560,1340|550,1380|570,1370|560,1410|550,1400|570,1440|560,1480|550,1470|570,1510|560,1500|550,1540|570,1580|560,1570|550,1610|570,1600|560,1640|550,1680|570,1670|560,1710|550,1700|570,1740|560,1780|550,1770|560,-1800|550,-1760|570,-1720|560,-1680|550,-1690|570,-1650|560,-1660|550,-1620|570,-1580|560,-1590|550,-1550|570,-1560|560,-1520|550,-1480|570,-1490|560,-1450|550,-1460|570,-1420|560,-1380|550,-1390|570,-1350|560,-1360|550,-1320|570,-1280|560,-1290|550,-1250|570,-1260|560,-1220|550,-1180|570,-1190|560,-1150|550,-1160|570,-1120|560,-1080|550,-1090|570,-1050|560,-1060|550,-1020|570,-980|560,-990|550,-950|570,-960|560,-920|550,-880|570,-890|560,-850|550,-860|570,-820|560,-780|550,-790|570,-750|560,-760|550,-720|570,-680|560,-690|550,-650|570,-660|560,-620|550,-580|570,-590|560,-550|550,-560|570,-520|570,-80|590,-90|580,-50|570,-60|590,-20|580,20|570,10|590,50|580,40|570,80|590,120|580,110|570,150|590,140|580,180|570,220|590,210|580,250|570,240|590,280|580,320|570,310|590,350|580,340|570,380|590,420|580,410|570,450|590,440|580,480|570,520|590,510|580,550|570,540|590,580|580,620|570,610|590,650|580,640|570,680|590,720|580,710|570,750|590,740|580,780|570,820|590,810|580,850|570,840|590,880|580,920|570,910|590,950|580,940|570,980|590,1020|580,1010|570,1050|590,1040|580,1080|570,1120|590,1110|580,1150|570,1140|590,1180|580,1220|570,1210|590,1250|580,1240|570,1280|590,1320|580,1310|570,1350|590,1340|580,1380|570,1420|590,1410|580,1450|570,1440|590,1480|580,1520|570,1510|590,1550|580,1540|570,1580|590,1620|580,1610|570,1650|590,1640|580,1680|570,1720|590,1710|580,1750|570,1740|590,1780|580,-1780|570,-1740|590,-1750|580,-1710|570,-1720|590,-1680|580,-1640|570,-1650|590,-1610|580,-1620|570,-1580|590,-1540|580,-1550|570,-1510|590,-1520|580,-1480|570,-1440|590,-1450|580,-1410|570,-1420|590,-1380|580,-1340|570,-1350|590,-1310|580,-1320|570,-1280|590,-1240|580,-1250|570,-1210|590,-1220|580,-1180|570,-1140|590,-1150|580,-1110|570,-1120|590,-1080|580,-1040|570,-1050|590,-1010|580,-1020|570,-980|590,-940|580,-950|570,-910|590,-920|580,-880|570,-840|590,-850|580,-810|570,-820|590,-780|580,-740|570,-750|590,-710|580,-720|570,-680|590,-640|580,-650|570,-610|590,-620|580,-580|570,-540|590,-550|570,-520|590,-110|600,-80|590,-40|610,-50|600,-10|590,-20|610,20|600,60|590,50|610,90|600,80|590,120|610,160|600,150|590,190|610,180|600,220|590,260|610,250|600,290|590,280|610,320|600,360|590,350|610,390|600,380|590,420|610,460|600,450|590,490|610,480|600,520|590,560|610,550|600,590|590,580|610,620|600,660|590,650|610,690|600,680|590,720|610,760|600,750|590,790|610,780|600,820|590,860|610,850|600,890|590,880|610,920|600,960|590,950|610,990|600,980|590,1020|610,1060|600,1050|590,1090|610,1080|600,1120|590,1160|610,1150|600,1190|590,1180|610,1220|600,1260|590,1250|610,1290|600,1280|590,1320|610,1360|600,1350|590,1390|610,1380|600,1420|590,1460|610,1450|600,1490|590,1480|610,1520|600,1560|590,1550|610,1590|600,1580|590,1620|610,1660|600,1650|590,1690|610,1680|600,1720|590,1760|610,1750|600,1790|590,-1770|610,-1780|600,-1740|590,-1700|610,-1710|600,-1670|590,-1680|610,-1640|600,-1600|590,-1610|610,-1570|600,-1580|590,-1540|610,-1500|600,-1510|590,-1470|610,-1480|600,-1440|590,-1400|610,-1410|600,-1370|590,-1380|610,-1340|600,-1300|590,-1310|610,-1270|600,-1280|590,-1240|610,-1200|600,-1210|590,-1170|610,-1180|600,-1140|590,-1100|610,-1110|600,-1070|590,-1080|610,-1040|600,-1000|590,-1010|610,-970|600,-980|590,-940|610,-900|600,-910|590,-870|610,-880|600,-840|590,-800|610,-810|600,-770|590,-780|610,-740|600,-700|590,-710|610,-670|600,-680|590,-640|610,-600|600,-610|590,-570|610,-580|600,-540|610,-510|600,-470|610,-440|600,-400|610,-370|600,-380|610,-300|600,-310|610,-280|600,-240|610,-210|600,-170|600,-100|590,-110|610,-70|630,-80|620,-40|610,0|630,-10|620,30|610,20|630,60|620,100|610,90|630,130|620,120|610,160|630,200|620,190|610,230|630,220|620,260|610,300|630,290|620,330|610,320|630,360|620,400|610,390|630,430|620,420|610,460|630,500|620,490|610,530|630,520|620,560|610,600|630,590|620,630|610,620|630,660|620,700|610,690|630,730|620,720|610,760|630,800|620,790|610,830|630,820|620,860|610,900|630,890|620,930|610,920|630,960|620,1000|610,990|630,1030|620,1020|610,1060|630,1100|620,1090|610,1130|630,1120|620,1160|610,1200|630,1190|620,1230|610,1220|630,1260|620,1300|610,1290|630,1330|620,1320|610,1360|630,1400|620,1390|610,1430|630,1420|620,1460|610,1500|630,1490|620,1530|610,1520|630,1560|620,1600|610,1590|630,1630|620,1620|610,1660|630,1700|620,1690|610,1730|630,1720|620,1760|610,1800|610,-1800|630,-1760|620,-1770|610,-1730|630,-1740|620,-1700|610,-1660|630,-1670|620,-1630|610,-1640|630,-1600|620,-1560|610,-1570|630,-1530|620,-1540|610,-1500|630,-1460|620,-1470|610,-1430|630,-1440|620,-1400|610,-1360|630,-1370|620,-1330|610,-1340|630,-1300|620,-1260|610,-1270|630,-1230|620,-1240|610,-1200|630,-1160|620,-1170|610,-1130|630,-1140|620,-1100|610,-1060|630,-1070|620,-1030|610,-1040|630,-1000|620,-960|610,-970|630,-930|620,-940|610,-900|630,-860|620,-870|610,-830|630,-840|620,-800|610,-760|630,-770|620,-730|610,-740|630,-700|620,-660|610,-670|630,-630|620,-640|610,-600|630,-560|620,-570|610,-530|630,-540|620,-500|610,-460|630,-470|620,-430|610,-440|630,-400|620,-360|610,-370|630,-330|620,-340|610,-300|630,-260|620,-270|610,-230|630,-240|620,-200|610,-160|630,-170|630,-100|650,-60|640,-70|630,-30|650,-40|640,0|630,40|650,30|640,70|630,60|650,100|640,140|630,130|650,170|640,160|630,200|650,240|640,230|630,270|650,260|640,300|630,340|650,330|640,370|630,360|650,400|640,440|630,430|650,470|640,460|630,500|650,540|640,530|630,570|650,560|640,600|630,640|650,630|640,670|630,660|650,700|640,740|630,730|650,770|640,760|630,800|650,840|640,830|630,870|650,860|640,900|630,940|650,930|640,970|630,960|650,1000|640,1040|630,1030|650,1070|640,1060|630,1100|650,1140|640,1130|630,1170|650,1160|640,1200|630,1240|650,1230|640,1270|630,1260|650,1300|640,1340|630,1330|650,1370|640,1360|630,1400|650,1440|640,1430|630,1470|650,1460|640,1500|630,1540|650,1530|640,1570|630,1560|650,1600|640,1640|630,1630|650,1670|640,1660|630,1700|650,1740|640,1730|630,1770|650,1760|650,-1790|640,-1800|630,-1760|650,-1720|640,-1730|630,-1690|650,-1700|640,-1660|630,-1620|650,-1630|640,-1590|630,-1600|650,-1560|640,-1520|630,-1530|650,-1490|640,-1500|630,-1460|650,-1420|640,-1430|630,-1390|650,-1400|640,-1360|630,-1320|650,-1330|640,-1290|630,-1300|650,-1260|640,-1220|630,-1230|650,-1190|640,-1200|630,-1160|650,-1120|640,-1130|630,-1090|650,-1100|640,-1060|630,-1020|650,-1030|640,-990|630,-1000|650,-960|640,-920|630,-930|650,-890|640,-900|630,-860|650,-820|640,-830|630,-790|650,-800|640,-760|630,-720|650,-730|640,-690|630,-700|650,-660|640,-620|630,-630|650,-590|640,-600|630,-560|650,-520|640,-530|630,-490|650,-500|640,-460|630,-420|650,-430|640,-390|630,-400|650,-360|640,-320|630,-330|650,-290|640,-300|630,-260|650,-220|640,-230|630,-190|650,-200|640,-160|650,-130|640,-90|660,-100|650,-60|670,-20|660,-30|650,10|670,0|660,40|650,80|670,70|660,110|650,100|670,140|660,180|650,170|670,210|660,200|650,240|670,280|660,270|650,310|670,300|660,340|650,380|670,370|660,410|650,400|670,440|660,480|650,470|670,510|660,500|650,540|670,580|660,570|650,610|670,600|660,640|650,680|670,670|660,710|650,700|670,740|660,780|650,770|670,810|660,800|650,840|670,880|660,870|650,910|670,900|660,940|650,980|670,970|660,1010|650,1000|670,1040|660,1080|650,1070|670,1110|660,1100|650,1140|670,1180|660,1170|650,1210|670,1200|660,1240|650,1280|670,1270|660,1310|650,1300|670,1340|660,1380|650,1370|670,1410|660,1400|650,1440|670,1480|660,1470|650,1510|670,1500|660,1540|650,1580|670,1570|660,1610|650,1600|670,1640|660,1680|650,1670|670,1710|660,1700|650,1740|670,1780|660,1770|660,-1780|650,-1790|670,-1750|660,-1760|650,-1720|670,-1680|660,-1690|650,-1650|670,-1660|660,-1620|650,-1580|670,-1590|660,-1550|650,-1560|670,-1520|660,-1480|650,-1490|670,-1450|660,-1460|650,-1420|670,-1380|660,-1390|650,-1350|670,-1360|660,-1320|650,-1280|670,-1290|660,-1250|650,-1260|670,-1220|660,-1180|650,-1190|670,-1150|660,-1160|650,-1120|670,-1080|660,-1090|650,-1050|670,-1060|660,-1020|650,-980|670,-990|660,-950|650,-960|670,-920|660,-880|650,-890|670,-850|660,-860|650,-820|670,-780|660,-790|650,-750|670,-760|660,-720|650,-680|670,-690|660,-650|650,-660|670,-620|660,-580|650,-590|670,-550|660,-560|650,-520|670,-480|660,-490|650,-450|670,-460|660,-420|650,-380|670,-390|660,-350|650,-360|670,-320|660,-280|650,-290|670,-250|660,-260|650,-220|670,-180|660,-190|650,-150|670,-160|650,-80|670,-90|690,-50|680,-60|670,-20|690,20|680,10|670,50|690,40|680,80|670,120|690,110|680,150|670,140|690,180|680,220|670,210|690,250|680,240|670,280|690,320|680,310|670,350|690,340|680,380|670,420|690,410|680,450|670,440|690,480|680,520|670,510|690,550|680,540|670,580|690,620|680,610|670,650|690,640|680,680|670,720|690,710|680,750|670,740|690,780|680,820|670,810|690,850|680,840|670,880|690,920|680,910|670,950|690,940|680,980|670,1020|690,1010|680,1050|670,1040|690,1080|680,1120|670,1110|690,1150|680,1140|670,1180|690,1220|680,1210|670,1250|690,1240|680,1280|670,1320|690,1310|680,1350|670,1340|690,1380|680,1420|670,1410|690,1450|680,1440|670,1480|690,1520|680,1510|670,1550|690,1540|680,1580|670,1620|690,1610|680,1650|670,1640|690,1680|680,1720|670,1710|690,1750|680,1740|670,1780|690,-1780|680,-1740|670,-1750|690,-1710|680,-1720|670,-1680|690,-1640|680,-1650|670,-1610|690,-1620|680,-1580|670,-1540|690,-1550|680,-1510|670,-1520|690,-1480|680,-1440|670,-1450|690,-1410|680,-1420|670,-1380|690,-1340|680,-1350|670,-1310|690,-1320|680,-1280|670,-1240|690,-1250|680,-1210|670,-1220|690,-1180|680,-1140|670,-1150|690,-1110|680,-1120|670,-1080|690,-1040|680,-1050|670,-1010|690,-1020|680,-980|670,-940|690,-950|680,-910|670,-920|690,-880|680,-840|670,-850|690,-810|680,-820|670,-780|690,-740|680,-750|670,-710|690,-720|680,-680|670,-640|690,-650|680,-610|670,-620|690,-580|680,-540|670,-550|690,-510|680,-520|670,-480|690,-440|680,-450|670,-410|690,-420|680,-380|670,-340|690,-350|680,-310|670,-320|690,-280|680,-240|670,-250|690,-210|680,-220|670,-180|680,-150|710,-80|700,-40|690,-50|710,-10|700,-20|690,20|710,60|700,50|690,90|710,80|700,120|690,160|710,150|700,190|690,180|710,220|700,260|690,250|710,290|700,280|690,320|710,360|700,350|690,390|710,380|700,420|690,460|710,450|700,490|690,480|710,520|700,560|690,550|710,590|700,580|690,620|710,660|700,650|690,690|710,680|700,720|690,760|710,750|700,790|690,780|710,820|700,860|690,850|710,890|700,880|690,920|710,960|700,950|690,990|710,980|700,1020|690,1060|710,1050|700,1090|690,1080|710,1120|700,1160|690,1150|710,1190|700,1180|690,1220|710,1260|700,1250|690,1290|710,1280|700,1320|690,1360|710,1350|700,1390|690,1380|710,1420|700,1460|690,1450|710,1490|700,1480|690,1520|710,1560|700,1550|690,1590|710,1580|700,1620|690,1660|710,1650|700,1690|690,1680|710,1720|700,1760|690,1750|710,1790|700,-1770|690,-1780|710,-1740|700,-1700|690,-1710|710,-1670|700,-1680|690,-1640|710,-1600|700,-1610|690,-1570|710,-1580|700,-1540|690,-1500|710,-1510|700,-1470|690,-1480|710,-1440|700,-1400|690,-1410|710,-1370|700,-1380|690,-1340|710,-1300|700,-1310|690,-1270|710,-1280|700,-1240|690,-1200|710,-1210|700,-1170|690,-1180|710,-1140|700,-1100|690,-1110|710,-1070|700,-1080|690,-1040|710,-1000|700,-1010|690,-970|710,-980|700,-940|690,-900|710,-910|700,-870|690,-880|710,-840|700,-800|690,-810|710,-770|700,-780|690,-740|710,-700|700,-710|690,-670|710,-680|700,-640|690,-600|710,-610|700,-570|690,-580|710,-540|700,-500|690,-510|710,-470|700,-480|690,-440|710,-400|700,-410|690,-370|710,-380|700,-340|690,-300|710,-310|700,-270|690,-280|710,-240|700,-200|690,-210|710,-170|700,-180|710,-100|710,-80|710,-10|710,60|710,130|710,200|710,220|720,300|710,290|730,330|720,320|710,360|730,400|720,390|710,430|730,420|720,460|710,500|730,490|720,530|710,520|730,560|720,600|710,590|730,630|720,620|710,660|730,700|720,690|710,730|730,720|720,760|710,800|730,790|720,830|710,820|730,860|720,900|710,890|730,930|720,920|710,960|730,1000|720,990|710,1030|730,1020|720,1060|710,1100|730,1090|720,1130|710,1120|730,1160|720,1200|710,1190|730,1230|720,1220|710,1260|730,1300|720,1290|710,1330|730,1320|720,1360|710,1400|730,1390|720,1430|710,1420|730,1460|720,1500|710,1490|730,1530|720,1520|710,1560|730,1600|720,1590|710,1630|730,1620|720,1660|710,1700|730,1690|720,1730|710,1720|730,1760|720,1800|720,-1800|710,-1760|730,-1770|720,-1730|710,-1740|730,-1700|720,-1660|710,-1670|720,-1640|710,-1600|720,-1570|710,-1530|720,-1500|710,-1460|720,-1430|710,-1440|720,-1360|710,-1370|720,-1340|710,-1300|720,-1270|710,-1230|720,-1200|710,-1160|720,-1130|710,-1140|720,-1060|710,-1070|720,-1040|710,-1000|720,-970|710,-930|720,-900|710,-860|720,-830|710,-840|720,-760|710,-770|720,-740|710,-700|720,-670|710,-630|720,-600|710,-560|720,-530|710,-540|730,-500|720,-460|710,-470|730,-430|720,-440|710,-400|730,-360|720,-370|710,-330|730,-340|720,-300|710,-260|730,-270|720,-230|710,-240|730,-200|720,-160|710,-170|750,300|740,340|730,330|750,370|740,360|730,400|750,440|740,430|730,470|750,460|740,500|730,540|750,530|740,570|730,560|750,600|740,640|730,630|750,670|740,660|730,700|750,740|740,730|730,770|750,760|740,800|730,840|750,830|740,870|730,860|750,900|740,940|730,930|750,970|740,960|730,1000|750,1040|740,1030|730,1070|750,1060|740,1100|730,1140|750,1130|740,1170|730,1160|750,1200|740,1240|730,1230|750,1270|740,1260|730,1300|750,1340|740,1330|730,1370|750,1360|740,1400|730,1440|750,1430|740,1470|730,1460|750,1500|740,1540|730,1530|750,1570|740,1560|730,1600|750,1640|740,1630|730,1670|750,1660|740,1700|730,1740|750,1730|740,1770|730,1760|730,-1790|750,-1800|740,-1760|730,-1720|750,-1730|740,-1690|730,-1700|730,-520|750,-530|740,-490|730,-500|750,-460|740,-420|730,-430|750,-390|740,-400|730,-360|750,-320|740,-330|730,-290|750,-300|740,-260|730,-220|750,-230|740,-190|730,-200|750,-160|750,300|750,370|750,440|750,510|750,580|750,600|750,670|750,740|750,810|750,880|750,900|750,970|750,1040|750,1110|750,1180|750,1200|750,1270|750,1340|750,1410|750,1480|750,1500|750,1570|750,1640|750,1710|750,1780|750,-1750|750,-1680|750,-550|760,-520|750,-480|770,-490|760,-450|750,-460|770,-420|760,-380|750,-390|770,-350|760,-360|750,-320|770,-280|760,-290|750,-250|770,-260|760,-220|750,-180|770,-190|760,-150|750,-160|790,-540|780,-550|770,-510|790,-520|780,-480|770,-440|790,-450|780,-410|770,-420|790,-380|780,-340|770,-350|790,-310|780,-320|770,-280|790,-240|780,-250|770,-210|790,-220|780,-180|790,-150|790,-540|810,-500|800,-510|790,-470|810,-480|800,-440|790,-400|810,-410|800,-370|790,-380|810,-340|800,-300|790,-310|810,-270|800,-280|790,-240|810,-200|800,-210|790,-170|810,-180|830,-530|820,-540|810,-500|830,-460|820,-470|810,-430|830,-440|820,-400|810,-360|830,-370|820,-330|810,-340|830,-300|820,-260|810,-270|830,-230|820,-240|810,-200|830,-160|820,-170
';

                function toVec(lat, lng) {
                    var phi = (90 - lat) * Math.PI / 180;
                    var theta = lng * Math.PI / 180;
                    return [
                        Math.sin(phi) * Math.sin(theta),
                        Math.cos(phi),
                        Math.sin(phi) * Math.cos(theta)
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
                        var r = Math.max(6, Math.min(18, 8 + (dev.reputation || 0) / 80)) * zoom;
                        points.push({ dev: dev, sx: sx, sy: sy, z: z, r: r });
                    }
                }

                function draw() {
                    ctx.clearRect(0, 0, width, height);

                    cosY = Math.cos(yaw); sinY = Math.sin(yaw);
                    cosP = Math.cos(pitch); sinP = Math.sin(pitch);

                    // Dot-matrix continents (front hemisphere only)
                    var dotR = Math.max(0.8, Math.min(2.5, R * 0.007));
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
                    var bestDist = 20;
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
                    document.getElementById('globe-tip-link').href = '{{ url('/devid') }}/' + encodeURIComponent(p.dev.handle);
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

        @fluxScripts
    </body>
</html>
