<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>ProoDev for Developers · Let Your Work Speak for You</title>

        <meta name="description" content="Build your free ProoDev DevID from the work you've already done. Showcase engineering achievements, open-source contributions, expertise and impact, and discover opportunities that match what you can actually do.">

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
            @keyframes fade-up {
                from { opacity: 0; transform: translateY(16px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .animate-fade-up { animation: fade-up 0.6s ease-out both; }
            .delay-100 { animation-delay: 0.1s; }
            .delay-200 { animation-delay: 0.2s; }

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
        </style>
    </head>
    <body class="min-h-screen overflow-x-clip bg-white text-zinc-900 antialiased selection:bg-zinc-900/20 dark:bg-zinc-950 dark:text-zinc-100">
        @php
            $icon = [
                'arrow-right' => 'M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z',
                'check' => 'M4.5 12.75l6 6 9-13.5',
                'chevron-down' => 'M19.5 8.25l-7.5 7.5-7.5-7.5',
                'bars' => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
                'code-bracket' => 'M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5',
                'trophy' => 'M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0',
                'sparkles' => 'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z',
                'shield-check' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
                'folder' => 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z',
                'document-text' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 12h4.5m-4.5 3h5.25m-9.75-6h.008v.008h-.008v-.008ZM7.5 21h9a3 3 0 0 0 3-3V7.5A4.5 4.5 0 0 0 15 3H7.5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3Z',
                'rocket-launch' => 'M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z',
                'chart-bar' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
                'users' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
            ];
        @endphp

        {{-- Ambient glow --}}
        <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-[36rem] glow" aria-hidden="true"></div>

        {{-- ===================== NAV ===================== --}}
        <header class="site-header fixed inset-x-0 top-0 z-50 border-b backdrop-blur-xl supports-[backdrop-filter]:bg-white/60 supports-[backdrop-filter]:dark:bg-zinc-950/50">
            <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('welcome') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo-black.png') }}" alt="ProoDev" class="h-7 w-auto dark:hidden" />
                    <img src="{{ asset('images/logo-white.png') }}" alt="ProoDev" class="hidden h-7 w-auto dark:block" />
                </a>

                <div class="hidden items-center gap-1 text-sm text-zinc-500 md:flex dark:text-zinc-400">
                    <a href="{{ route('welcome') }}" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">Home</a>
                    <a href="{{ route('developers') }}" class="rounded-lg px-3 py-2 font-medium text-zinc-900 transition dark:text-white hover:text-zinc-700 dark:hover:text-[#9db8ff]">For developers</a>
                    <a href="{{ route('for-companies') }}" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">For Companies</a>
                    <a href="{{ route('jobs.index') }}" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">Opportunities</a>
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
                            Create Your DevID
                        </a>
                    @endauth
                    <x-theme-toggle />
                </div>
            </nav>
        </header>

        {{-- ===================== HERO ===================== --}}
        <section class="section-contained relative mx-auto max-w-7xl px-4 pb-16 pt-32 text-center sm:px-6 sm:pt-40 lg:px-8">
            <div class="relative mx-auto max-w-3xl animate-fade-up">
                <p class="text-sm font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">ProoDev for developers</p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight text-zinc-900 sm:text-6xl dark:text-white">
                    What You've Built <span class="text-gradient">Shouldn't Go Unnoticed.</span>
                </h1>
                <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-zinc-600 dark:text-zinc-400">
                    You've spent countless hours building, debugging, contributing, learning, and solving problems.
                    ProoDev helps turn that work into a story of your engineering achievements, expertise, and impact -
                    and helps that work open doors to new opportunities.
                </p>

                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    @auth
                        <a href="{{ route('home') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#3750eb] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#3750eb]/25 transition hover:opacity-90 sm:w-auto">
                            Create Your DevID
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $icon['arrow-right'] }}" clip-rule="evenodd"/></svg>
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#3750eb] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#3750eb]/25 transition hover:opacity-90 sm:w-auto">
                            Create Your DevID
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $icon['arrow-right'] }}" clip-rule="evenodd"/></svg>
                        </a>
                    @endauth
                    <a href="#how-it-works" class="inline-flex w-full items-center justify-center rounded-xl border border-zinc-200 bg-white/60 px-6 py-3 text-sm font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-white sm:w-auto dark:border-white/10 dark:bg-white/5 dark:text-zinc-200 dark:hover:border-white/25 dark:hover:bg-white/10">
                        See How It Works
                    </a>
                </div>

                <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">Free to join. No resume required.</p>
            </div>
        </section>

        {{-- ===================== WORKFLOW ===================== --}}
        <section id="how-it-works" class="section-contained relative overflow-hidden border-t border-zinc-200 bg-zinc-50 dark:border-white/5 dark:bg-white/[0.02]">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">How it works</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Start With the Work You've Already Done.</h2>
                </div>

                <div class="mt-14 grid gap-5 md:grid-cols-2 lg:grid-cols-5">
                    @foreach ([
                        ['n' => '01', 'title' => 'Add Your Work', 'copy' => 'Paste links to GitHub repositories, pull requests, projects, packages, articles, demos and documentation.'],
                        ['n' => '02', 'title' => 'ProoDev Finds the Meaning', 'copy' => 'Achievements, problems solved, expertise, technical depth, project context, engineering significance and impact.'],
                        ['n' => '03', 'title' => 'Build Your DevID', 'copy' => 'Your work becomes a living engineering identity.'],
                        ['n' => '04', 'title' => 'Get Discovered', 'copy' => 'Recruiters and companies can discover developers based on demonstrated capabilities.'],
                        ['n' => '05', 'title' => 'Find Better Opportunities', 'copy' => 'Apply using your DevID and evidence instead of starting every application from scratch.'],
                    ] as $step)
                        <div class="relative rounded-lg border border-zinc-200 bg-white p-4 transition duration-300 hover:-translate-y-1 hover:border-zinc-900 hover:shadow-xl hover:shadow-zinc-900/10 dark:border-white/10 dark:bg-zinc-900/50 dark:hover:border-white/25 dark:hover:bg-white/[0.04]">
                            <span class="inline-flex items-center rounded-full bg-zinc-950 px-3 py-1 text-xs font-bold tracking-widest text-white ring-1 ring-zinc-200 dark:bg-white dark:text-zinc-950 dark:ring-white/10">{{ $step['n'] }}</span>
                            <h3 class="mt-4 text-base font-semibold text-zinc-900 dark:text-white">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $step['copy'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===================== ACHIEVEMENT TRANSFORMATION ===================== --}}
        <section class="section-contained relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">Engineering achievements</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">A Pull Request Can Say More Than a Résumé.</h2>
                </div>

                <div class="mx-auto mt-14 grid max-w-5xl items-stretch gap-5 lg:grid-cols-2">
                    <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-zinc-900/50">
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Before</div>
                        <div class="mt-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 font-mono text-sm dark:border-white/10 dark:bg-zinc-900/70">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-400/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3"><path fill-rule="evenodd" d="M4.5 12.75l6 6 9-13.5" clip-rule="evenodd"></path></svg> Merged</span>
                                <span class="text-zinc-500">PR #4821</span>
                            </div>
                            <p class="mt-3 text-zinc-700 dark:text-zinc-300">"Fix connection pool race condition"</p>
                        </div>
                    </div>

                    <div class="relative flex flex-col rounded-xl border border-zinc-300 dark:border-white/15 bg-gradient-to-br from-zinc-100 to-white p-5 shadow-lg shadow-zinc-900/10 dark:border-zinc-300 dark:border-white/15 dark:from-white/10 dark:to-zinc-950/60">
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">After: engineering achievement</div>
                        <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-4 dark:bg-zinc-900/50">
                            <p class="text-base font-semibold text-zinc-900 dark:text-white">Solved a high-complexity concurrency problem.</p>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Demonstrated</div>
                                    <ul class="mt-1.5 space-y-1 text-xs text-zinc-600 dark:text-zinc-300">
                                        @foreach (['Concurrency', 'Debugging', 'Database Systems', 'Testing'] as $skill)
                                            <li class="flex items-center gap-2">
                                                <span class="size-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                                                {{ $skill }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div>
                                    <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Engineering significance</div>
                                    <span class="mt-1.5 inline-flex rounded-full bg-rose-400/10 px-2.5 py-1 text-xs font-bold text-rose-600 dark:text-rose-400">VERY HIGH</span>
                                    <div class="mt-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Evidence</div>
                                    <div class="mt-1 text-xs font-medium text-zinc-500 dark:text-zinc-400">Issue → Pull Request → Code → Tests → Review</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 text-center">
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#3750eb] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#3750eb]/25 transition hover:opacity-90">
                        Discover Your Achievements
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $icon['arrow-right'] }}" clip-rule="evenodd"/></svg>
                    </a>
                </div>
            </div>
        </section>

        {{-- ===================== OPEN SOURCE ===================== --}}
        <section class="section-contained relative overflow-hidden border-t border-zinc-200 bg-zinc-50 dark:border-white/5 dark:bg-white/[0.02]">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">Open source</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Let People Understand the Contribution Behind the Contribution.</h2>
                </div>

                <div class="mx-auto mt-12 max-w-3xl space-y-4">
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900/50">
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Instead of</div>
                        <p class="mt-1.5 font-mono text-sm text-zinc-500">"Contributed to Filament"</p>
                    </div>
                    <div class="rounded-xl border border-zinc-300 dark:border-white/15 bg-gradient-to-br from-zinc-100 to-white p-5 dark:border-zinc-300 dark:border-white/15 dark:from-white/10 dark:to-zinc-950/60">
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">ProoDev shows</div>
                        <p class="mt-1.5 text-base font-semibold text-zinc-900 dark:text-white">Contributed to a major Laravel ecosystem project.</p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Contribution</div>
                                <p class="mt-1.5 text-sm text-zinc-700 dark:text-zinc-300">Improved component validation.</p>
                            </div>
                            <div>
                                <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Demonstrated</div>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach (['PHP', 'Laravel', 'Component Architecture', 'Testing'] as $skill)
                                        <span class="rounded-md bg-white/80 px-2 py-1 text-[11px] text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-900/50 dark:text-zinc-200 dark:ring-white/10">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 border-t border-zinc-200 pt-3 text-xs text-zinc-500 dark:border-white/10 dark:text-zinc-400">
                            Evidence: PR, Commit, Discussion. Every conclusion links back to its source.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== OPPORTUNITIES ===================== --}}
        <section class="section-contained relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">Opportunities</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Your Work Can Take You Further Than Your Résumé.</h2>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">Companies aren't only looking for job titles. They're looking for people who can solve problems, build systems, and make things work.</p>
                    <p class="mt-2 text-zinc-600 dark:text-zinc-400">ProoDev helps employers discover that evidence and helps you find opportunities where your demonstrated skills matter.</p>
                </div>

                <div class="mx-auto mt-14 grid max-w-4xl items-stretch gap-5 lg:grid-cols-2">
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-zinc-900/50">
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Your DevID</div>
                        <div class="mt-3 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-zinc-900/70">
                            <div class="text-sm font-semibold text-zinc-900 dark:text-white">Senior Laravel Engineer profile</div>
                            <ul class="mt-2 space-y-1 text-xs text-zinc-600 dark:text-zinc-300">
                                <li class="flex items-center gap-2"><span class="size-1.5 shrink-0 rounded-full bg-emerald-500"></span> 8 projects</li>
                                <li class="flex items-center gap-2"><span class="size-1.5 shrink-0 rounded-full bg-emerald-500"></span> 4 open-source contributions</li>
                                <li class="flex items-center gap-2"><span class="size-1.5 shrink-0 rounded-full bg-emerald-500"></span> 3 architecture achievements</li>
                                <li class="flex items-center gap-2"><span class="size-1.5 shrink-0 rounded-full bg-emerald-500"></span> Verified Laravel expertise</li>
                            </ul>
                        </div>
                    </div>

                    <div class="relative flex flex-col rounded-xl border border-zinc-300 dark:border-white/15 bg-gradient-to-br from-zinc-100 to-white p-5 shadow-lg shadow-zinc-900/10 dark:border-zinc-300 dark:border-white/15 dark:from-white/10 dark:to-zinc-950/60">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">Matched opportunity</div>
                                <div class="mt-1 text-lg font-bold text-zinc-900 dark:text-white">Senior Laravel Engineer</div>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-emerald-400/10 px-3 py-1 text-sm font-bold tabular-nums text-emerald-600 dark:text-emerald-400">94% Work Match</span>
                        </div>
                        <div class="mt-4 rounded-lg border border-zinc-200 bg-white/70 p-3 dark:border-white/10 dark:bg-zinc-900/50">
                            <div class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Why you match</div>
                            <p class="mt-1 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">Your evidence maps directly onto what this role needs. Apply with your work instead of starting from scratch.</p>
                        </div>
                        <a href="{{ route('jobs.index') }}" class="mt-6 inline-flex items-center justify-center gap-2 rounded-xl bg-[#3750eb] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#3750eb]/25 transition hover:opacity-90">
                            Explore Opportunities
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $icon['arrow-right'] }}" clip-rule="evenodd"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== DEVID GROWS ===================== --}}
        <section class="section-contained relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-zinc-900 dark:text-white">Your DevID</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Your DevID Grows With You.</h2>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">It evolves as you build, contribute, solve, learn, publish, collaborate, get recognized, get verified, and get hired.</p>
                </div>

                <div class="mx-auto mt-14 max-w-2xl">
                    <ol class="relative space-y-6 border-s-2 border-zinc-200 ps-6 dark:border-white/10">
                        @foreach ([
                            ['JAN', 'Joined ProoDev'],
                            ['FEB', 'Built payment service'],
                            ['MAR', 'Open-source contribution'],
                            ['APR', 'Solved performance issue'],
                            ['MAY', 'Published package'],
                            ['JUN', 'Verified Laravel expertise'],
                            ['JUL', 'Matched with company'],
                        ] as $moment)
                            <li class="relative">
                                <span class="absolute -start-[31px] top-1 size-3.5 rounded-full border-2 border-white bg-[#3750eb] dark:border-zinc-950"></span>
                                <div class="rounded-lg border border-zinc-200 bg-white p-3.5 dark:border-white/10 dark:bg-zinc-900/50">
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-900 dark:text-white">{{ $moment[0] }}</span>
                                    <p class="mt-0.5 text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $moment[1] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </section>

        {{-- ===================== FINAL CTA ===================== --}}
        <section class="section-contained mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-gradient-to-br from-zinc-100 via-white to-zinc-50 px-6 py-16 text-center sm:px-16 dark:border-white/10 dark:from-white/10 dark:via-zinc-900 dark:to-white/5">
                <div class="relative">
                    <h2 class="mx-auto max-w-2xl text-3xl font-bold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                        You've Already Done the Hard Work.
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-lg text-zinc-700 dark:text-zinc-300">
                        Now let ProoDev help the right people see what you're capable of.
                    </p>
                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        @auth
                            <a href="{{ route('home') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#3750eb] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#3750eb]/25 transition hover:opacity-90 sm:w-auto">
                                Create Your DevID
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $icon['arrow-right'] }}" clip-rule="evenodd"/></svg>
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#3750eb] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#3750eb]/25 transition hover:opacity-90 sm:w-auto">
                                Create Your DevID
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $icon['arrow-right'] }}" clip-rule="evenodd"/></svg>
                            </a>
                        @endauth
                        <a href="{{ route('jobs.index') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-zinc-200 bg-white/60 px-6 py-3 text-sm font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-white sm:w-auto dark:border-white/10 dark:bg-white/5 dark:text-zinc-200 dark:hover:border-white/25 dark:hover:bg-white/10">
                            Find Opportunities
                        </a>
                    </div>
                    <p class="mt-8 text-sm font-medium text-zinc-500 dark:text-zinc-400">Your work. Your achievements. Your next opportunity.</p>
                </div>
            </div>
        </section>

        <x-landing-sponsors-ads />

        {{-- ===================== FOOTER ===================== --}}
        <footer class="relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="mx-auto max-w-7xl px-4 pt-16 pb-8 sm:px-6 lg:px-8">
                <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-1">
                        <div class="flex items-center gap-2.5">
                            <img src="{{ asset('logo-black.svg') }}" alt="ProoDev" class="h-6 w-auto dark:hidden" />
                            <img src="{{ asset('logo-white.svg') }}" alt="ProoDev" class="hidden h-6 w-auto dark:block" />
                        </div>
                        <p class="mt-4 max-w-xs text-sm leading-relaxed text-zinc-500">Show what you've built. Get noticed by the right people. Evidence-backed engineering identities for developers.</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Product</h3>
                        <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                            <li><a href="{{ route('home') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Dashboard</a></li>
                            <li><a href="{{ route('developers') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Verified Directory</a></li>
                            <li><a href="{{ route('jobs.index') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Open Roles</a></li>
                            <li><a href="{{ url('/devid') }}" class="transition hover:text-zinc-900 dark:hover:text-white">DevID</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Company</h3>
                        <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                            <li><a href="{{ route('for-companies') }}" class="transition hover:text-zinc-900 dark:hover:text-white">About</a></li>
                            <li><a href="{{ route('news.index') }}" class="transition hover:text-zinc-900 dark:hover:text-white">News</a></li>
                            @auth
                                <li><a href="{{ route('home') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Dashboard</a></li>
                            @else
                                <li><a href="{{ route('login') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Sign in</a></li>
                                <li><a href="{{ route('register') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Register</a></li>
                            @endauth
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Legal</h3>
                        <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                            <li><a href="{{ route('privacy') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Privacy Policy</a></li>
                            <li><a href="{{ route('terms') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Terms &amp; Conditions</a></li>
                            <li><a href="{{ route('cookies') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Cookie Policy</a></li>
                        </ul>
                    </div>
                </div>
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

                var update = function () {
                    header.classList.toggle('is-scrolled', window.scrollY > 8);
                };

                window.addEventListener('scroll', update, { passive: true });
                update();
            })();
        </script>

        @fluxScripts
    </body>
</html>