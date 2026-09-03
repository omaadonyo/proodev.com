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
                    <a href="{{ route('developers') }}" class="border-b-4 px-3 pb-1 pt-2 transition hover:text-zinc-900 dark:hover:text-white {{ request()->routeIs('developers') ? 'border-zinc-900 font-medium text-zinc-900 dark:border-white dark:text-white' : 'border-transparent' }}">Developers</a>
                    <a href="#recruiters" class="border-b-4 border-transparent px-3 pb-1 pt-2 transition hover:text-zinc-900 dark:hover:text-white">Recruiters</a>
                    <a href="{{ route('for-companies') }}" class="border-b-4 px-3 pb-1 pt-2 transition hover:text-zinc-900 dark:hover:text-white {{ request()->routeIs('for-companies') ? 'border-zinc-900 font-medium text-zinc-900 dark:border-white dark:text-white' : 'border-transparent' }}">Companies</a>
                    <a href="#jobs" class="border-b-4 border-transparent px-3 pb-1 pt-2 transition hover:text-zinc-900 dark:hover:text-white">Opportunities</a>
                    <a href="#how-it-works" class="border-b-4 border-transparent px-3 pb-1 pt-2 transition hover:text-zinc-900 dark:hover:text-white">How It Works</a>
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

                <div class="overflow-hidden rounded-lg bg-zinc-100 shadow-2xl shadow-zinc-900/10 backdrop-blur-xl dark:bg-white/5 dark:shadow-zinc-900/20">
                    {{-- Window chrome --}}
                    <div class="flex items-center justify-between gap-4 border-b border-zinc-200 bg-zinc-50/50 px-5 py-3 dark:border-white/5 dark:bg-white/[0.02]">
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-full bg-zinc-900"></span>
                            <span class="size-2.5 rounded-full bg-zinc-400"></span>
                            <span class="size-2.5 rounded-full bg-zinc-300 dark:bg-zinc-600"></span>
                            <span class="ml-2 hidden text-xs font-medium text-zinc-600 dark:text-zinc-400 sm:inline">proodev scout</span>
                        </div>
                        <div class="flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400">
                            <span class="relative flex size-1.5">
                                <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-500 opacity-75"></span>
                                <span class="relative inline-flex size-1.5 rounded-full bg-emerald-500"></span>
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
                            <div class="flex flex-col gap-3 rounded-xl bg-zinc-100 p-4 shadow-sm dark:bg-white/5">
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
                    <div class="flex flex-col rounded-xl bg-zinc-100 p-6 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-zinc-900/10 dark:bg-white/5 dark:hover:bg-white/[0.06]">
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
                    <div class="flex flex-col rounded-xl bg-zinc-100 p-6 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-zinc-900/10 dark:bg-white/5 dark:hover:bg-white/[0.06]">
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
                    <div class="flex flex-col rounded-xl bg-zinc-100 p-6 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-zinc-900/10 dark:bg-white/5 dark:hover:bg-white/[0.06]">
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
                    <div class="flex flex-col rounded-xl bg-zinc-100 p-5 dark:bg-white/5">
                        <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"/></svg>
                            GitHub
                        </div>
                        <div class="mt-4 rounded-lg bg-zinc-100 p-4 font-mono text-sm dark:bg-white/5">
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
                        <div class="mt-4 rounded-lg bg-zinc-100 p-4 dark:bg-white/5">
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

                    <div class="rounded-xl bg-zinc-100 p-5 shadow-xl shadow-zinc-900/5 dark:bg-white/5">
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
                        <div class="group relative rounded-lg bg-zinc-100 p-5 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-zinc-900/10 dark:bg-white/5 dark:hover:bg-white/[0.04]">
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
                            <div class="flex items-start gap-4 rounded-xl bg-zinc-100 p-5 transition duration-300 hover:shadow-lg dark:bg-white/5">
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
                        <div class="rounded-xl bg-zinc-100 p-4 text-center dark:bg-white/5">
                            <div class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $globeStat['value'] }}</div>
                            <div class="mt-0.5 text-xs font-medium uppercase tracking-wider text-zinc-500">{{ $globeStat['label'] }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="relative mt-10">
                    <div class="pointer-events-none absolute inset-0 -z-10 rounded-full bg-zinc-900/10 blur-3xl" aria-hidden="true"></div>
                    <div class="relative z-10 w-full h-[400px] overflow-hidden rounded-xl bg-zinc-100 sm:h-[500px] dark:bg-white/5">
                        <canvas id="talent-globe" class="block size-full cursor-grab active:cursor-grabbing" style="width:100%;height:100%;display:block" aria-label="3D globe of developers"></canvas>

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

                        <div class="mt-4 rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
                            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Why you match</div>
                            <p class="mt-1 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">Your projects and open-source contributions demonstrate the backend architecture experience this role requires.</p>
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-3">
                            <div class="rounded-lg bg-zinc-100 p-3 text-center dark:bg-white/5">
                                <div class="text-lg font-bold text-zinc-900 dark:text-white">{{ number_format($stats[0]['value']) }}</div>
                                <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500">Developers</div>
                            </div>
                            <div class="rounded-lg bg-zinc-100 p-3 text-center dark:bg-white/5">
                                <div class="text-lg font-bold text-zinc-900 dark:text-white">{{ count($openJobs) }}</div>
                                <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500">Open roles</div>
                            </div>
                            <div class="rounded-lg bg-zinc-100 p-3 text-center dark:bg-white/5">
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
                    <div class="rounded-xl bg-zinc-100 p-5 shadow-xl shadow-zinc-900/5 dark:bg-white/5">
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
                                <figure class="mb-5 rounded-lg bg-zinc-100 p-4 dark:bg-white/5">
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
                                    <figure class="mb-5 rounded-lg bg-zinc-100 p-4 dark:bg-white/5">
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
                            <figure class="mb-5 rounded-lg bg-zinc-100 p-4 dark:bg-white/5">
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
                            <figure class="rounded-lg bg-zinc-100 p-4 dark:bg-white/5">
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
                        <div data-faq class="overflow-hidden rounded-xl bg-zinc-100 dark:bg-white/5">
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
            <div class="relative overflow-hidden rounded-xl bg-zinc-100 px-6 py-16 text-center sm:px-16 dark:bg-white/5">
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

                @php
                    $footerSocials = collect([
                        ['key' => 'social.x', 'label' => 'X'],
                        ['key' => 'social.bsky', 'label' => 'Bluesky'],
                        ['key' => 'social.youtube', 'label' => 'YouTube'],
                        ['key' => 'social.tiktok', 'label' => 'TikTok'],
                        ['key' => 'social.pinkary', 'label' => 'Pinkary'],
                    ])->map(function ($s) { $url = app(\App\Services\SiteSettings::class)->get($s['key']); if ($s['key']==='social.x' && !filled($url)) $url = app(\App\Services\SiteSettings::class)->get('social.twitter'); return array_merge($s, ['url' => $url]); })->filter(fn ($s) => filled($s['url']))->values();
                @endphp
                @if($footerSocials->isNotEmpty())
                    <div class="mt-10 flex justify-center gap-3 border-t border-zinc-200 pt-6 dark:border-white/5">
                        @foreach($footerSocials as $social)
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $social['label'] }}" class="flex size-9 items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-600 transition hover:border-zinc-900 hover:bg-zinc-900 hover:text-white dark:border-white/10 dark:bg-white/5 dark:text-zinc-400 dark:hover:bg-white dark:hover:text-zinc-900">
                                @if($social['label']==='X')
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="size-4" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                @elseif($social['label']==='Bluesky')
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="size-4" aria-hidden="true"><path d="M5.202 2.857C7.954 4.922 10.913 9.11 12 11.358c1.087-2.247 4.046-6.436 6.798-8.501C20.783 1.366 24 .213 24 3.883c0 .732-.42 6.156-.667 7.037-.856 3.061-3.978 3.842-6.755 3.37 4.854.826 6.089 3.562 3.422 6.299-5.065 5.196-7.28-1.304-7.847-2.97-.104-.305-.152-.448-.153-.327 0-.121-.05.022-.153.327-.568 1.666-2.782 8.166-7.847 2.97-2.667-2.737-1.432-5.473 3.422-6.3-2.777.473-5.899-.308-6.755-3.369C.42 10.04 0 4.615 0 3.883c0-3.67 3.217-2.517 5.202-1.026"/></svg>
                                @elseif($social['label']==='YouTube')
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="size-4" aria-hidden="true"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                                @elseif($social['label']==='TikTok')
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="size-4" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.11V8.94a6.27 6.27 0 0 0-.79-.05A6.34 6.34 0 0 0 3.15 15.2a6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V8.75a8.2 8.2 0 0 0 4.76 1.52V6.84a4.83 4.83 0 0 1-3.77-4.25z"/></svg>
                                @elseif($social['label']==='Pinkary')
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="size-4" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Bottom bar --}}
                <div class="mt-6 flex flex-col items-center justify-between gap-3 border-t border-zinc-200 pt-6 sm:flex-row dark:border-white/5">
                    <p class="text-xs text-zinc-400">&copy; {{ date('Y') }} {{ config('app.name', 'ProoDev') }}. Proof over claims.</p>
                    <p class="text-xs text-zinc-400">Built for engineers who back their claims with evidence.</p>
                </div>
            </div>
        </footer>

        <script src="/continents.js"></script>
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

                function toVec(lat, lng) {
                    var phi = (90 - lat) * Math.PI / 180;
                    var theta = lng * Math.PI / 180;
                    return [
                        Math.sin(phi) * Math.sin(theta),
                        Math.cos(phi),
                        Math.sin(phi) * Math.cos(theta)
                    ];
                }

                var continentsVec = CONTINENTS.map(function(ring){ return ring.map(function(c){ return toVec(c[1], c[0]); }); });

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
                        var r = Math.max(6, Math.min(11, 7 + (dev.reputation || 0) / 80)) * zoom;
                        points.push({ dev: dev, sx: sx, sy: sy, z: z, r: r });
                    }
                }

                function draw() {
                    ctx.clearRect(0, 0, width, height);

                    cosY = Math.cos(yaw); sinY = Math.sin(yaw);
                    cosP = Math.cos(pitch); sinP = Math.sin(pitch);

                    // --- Ocean: #f4f4f5 smoke ---
                    ctx.save();
                    ctx.beginPath();
                    ctx.arc(cx, cy, R, 0, Math.PI * 2);
                    ctx.clip();
                    ctx.fillStyle = '#f4f4f5';
                    ctx.fillRect(cx - R - 2, cy - R - 2, R * 2 + 4, R * 2 + 4);
                    var oceanGrad = ctx.createRadialGradient(cx - R * 0.3, cy - R * 0.35, R * 0.2, cx, cy, R);
                    oceanGrad.addColorStop(0, 'rgba(255,255,255,1)');
                    oceanGrad.addColorStop(0.45, 'rgba(244,244,245,0.7)');
                    oceanGrad.addColorStop(0.75, 'rgba(228,228,231,0.4)');
                    oceanGrad.addColorStop(1, 'rgba(212,212,216,0.5)');
                    ctx.fillStyle = oceanGrad;
                    ctx.fillRect(cx - R - 2, cy - R - 2, R * 2 + 4, R * 2 + 4);
                    ctx.restore();

                    // Continents - vector polygons with horizon culling (no flicker)
                    var cY2 = cosY, sY2 = sinY, cP2 = cosP, sP2 = sinP;
                    ctx.save();
                    ctx.beginPath(); ctx.arc(cx, cy, R, 0, Math.PI * 2); ctx.clip();
                    for (var ci = 0; ci < continentsVec.length; ci++) {
                        var ring = continentsVec[ci];
                        // whole-polygon culling: average Z decides visibility (prevents flicker on horizon)
                        var avgZ2 = 0;
                        for (var vi = 0; vi < ring.length; vi++) {
                            var v2 = ring[vi];
                            var z2t = -v2[0] * sY2 + v2[2] * cY2;
                            var y2t = v2[1];
                            avgZ2 += y2t * sP2 + z2t * cP2;
                        }
                        avgZ2 /= ring.length;
                        if (avgZ2 <= 0.08) continue;
                        var op = Math.min(0.55, 0.22 + avgZ2 * 0.32);
                        ctx.beginPath();
                        for (var vi2 = 0; vi2 < ring.length; vi2++) {
                            var v2b = ring[vi2];
                            var x2b = v2b[0] * cY2 + v2b[2] * sY2;
                            var z2tb = -v2b[0] * sY2 + v2b[2] * cY2;
                            var y2tb = v2b[1];
                            var y2pb = y2tb * cP2 - z2tb * sP2;
                            var sx2b = cx + R * x2b;
                            var sy2b = cy - R * y2pb;
                            if (vi2 === 0) ctx.moveTo(sx2b, sy2b); else ctx.lineTo(sx2b, sy2b);
                        }
                        ctx.closePath();
                        ctx.fillStyle = 'rgba(63,63,70,' + op.toFixed(3) + ')';
                        ctx.fill();
                    }
                    ctx.restore();

                    // Sphere rim
                    ctx.beginPath();
                    ctx.arc(cx, cy, R, 0, Math.PI * 2);
                    ctx.strokeStyle = 'rgba(113,113,122,0.35)';
                    ctx.lineWidth = 1.2;
                    ctx.stroke();

                    projectAll();

                    // Sort front-to-back
                    var front = points.filter(function (p) { return p.z > 0; })
                        .sort(function (a, b) { return b.z - a.z; });

                    for (var j = 0; j < front.length; j++) {
                        var p = front[j];
                        var isActive = (p.dev.id === hoverId) || (p.dev.id === pinnedIndex);
                        var av = avatars[developers.indexOf(p.dev)];
                        var drawR = isActive ? p.r * 1.4 : p.r;
                        if (av && av.ok && av.img) {
                            ctx.save();
                            ctx.beginPath();
                            ctx.arc(p.sx, p.sy, drawR, 0, Math.PI * 2);
                            ctx.clip();
                            ctx.drawImage(av.img, p.sx - drawR, p.sy - drawR, drawR * 2, drawR * 2);
                            ctx.restore();
                            ctx.beginPath();
                            ctx.arc(p.sx, p.sy, drawR, 0, Math.PI * 2);
                            ctx.strokeStyle = isActive ? 'rgba(24,24,27,1)' : 'rgba(255,255,255,0.95)';
                            ctx.lineWidth = isActive ? 2.5 : 1.6;
                            ctx.stroke();
                        } else {
                            ctx.beginPath();
                            ctx.arc(p.sx, p.sy, drawR, 0, Math.PI * 2);
                            ctx.fillStyle = isActive ? '#18181b' : '#09090b';
                            ctx.fill();
                            ctx.strokeStyle = 'rgba(255,255,255,0.95)';
                            ctx.lineWidth = isActive ? 2.5 : 1.6;
                            ctx.stroke();
                        }
                        if (isActive) {
                            ctx.beginPath();
                            ctx.arc(p.sx, p.sy, drawR + 6, 0, Math.PI * 2);
                            ctx.strokeStyle = 'rgba(9,9,11,0.18)';
                            ctx.lineWidth = 1.2;
                            ctx.stroke();
                            ctx.beginPath();
                            ctx.arc(p.sx, p.sy, drawR + 10, 0, Math.PI * 2);
                            ctx.strokeStyle = 'rgba(9,9,11,0.06)';
                            ctx.lineWidth = 8;
                            ctx.stroke();
                        }
                    }
                    // keep pinned tooltip anchored to moving dot
                    if (pinnedIndex !== -1) {
                        for (var k = 0; k < points.length; k++) {
                            if (points[k].dev.id === pinnedIndex) {
                                if (points[k].z > 0) {
                                    var tp = clampTooltip(points[k].sx, points[k].sy - points[k].r - 16);
                                    tooltip.style.left = tp.x + 'px';
                                    tooltip.style.top = tp.y + 'px';
                                } else {
                                    hideTooltip();
                                    pinnedIndex = -1;
                                }
                                break;
                            }
                        }
                    }
                }

                function tick() {
                    // auto-rotate, pause on hover/drag/pinned
                    if (!dragging && autoRotate && hoverId === -1 && pinnedIndex === -1) {
                        yaw += 0.0018;
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
                            hoverId = points[idx].dev.id;
                            canvas.style.cursor = 'pointer';
                        } else {
                            hoverId = -1;
                            canvas.style.cursor = 'grab';
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
