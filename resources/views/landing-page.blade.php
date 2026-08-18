<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>ProoDev - Meaning over Activity: Your Work, Proven</title>

        <meta name="description" content="ProoDev doesn't measure how active a developer is. It discovers how meaningful their engineering work is. Paste a repo or project, AI reads the real work, and you get an evidence-backed Engineering Magnitude score.">

        <meta name="keywords" content="engineering personality, career evidence, developer portfolio, engineer, ai engineering analysis, engineering magnitude, prove your skills">

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

            .score-ring {
                background: conic-gradient(#3750eb calc(var(--score, 0) * 1%), rgb(228 228 231 / 0.6) 0);
            }
            .dark .score-ring {
                background: conic-gradient(#5b6cff calc(var(--score, 0) * 1%), rgb(255 255 255 / 0.12) 0);
            }
        </style>
    </head>
    <body class="min-h-screen overflow-x-clip bg-white text-zinc-900 antialiased selection:bg-[#3750eb]/30 dark:bg-zinc-950 dark:text-zinc-100">

        {{-- Ambient glow --}}
        <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-[40rem] glow" aria-hidden="true"></div>

        @php
            $iconPaths = [
                'bolt' => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z',
                'check' => 'M4.5 12.75l6 6 9-13.5',
                'x-mark' => 'M6 18 18 6M6 6l12 12',
                'chevron-down' => 'M19.5 8.25l-7.5 7.5-7.5-7.5',
                'arrow-right' => 'M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z',
                'sparkles' => 'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z',
                'code-bracket' => 'M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5',
                'document-text' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 12h4.5m-4.5 3h5.25m-9.75-6h.008v.008h-.008v-.008ZM7.5 21h9a3 3 0 0 0 3-3V7.5A4.5 4.5 0 0 0 15 3H7.5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3Z',
                'shield-check' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
                'map-pin' => 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z',
                'trophy' => 'M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0',
                'book-open' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
                'arrow-trending-up' => 'M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941',
                'rocket-launch' => 'M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z',
            ];

            $meaningPoints = [
                ['icon' => 'code-bracket', 'title' => 'Technical depth of your code', 'description' => 'AI reads the source, not the metadata. It judges how the code is written, structured, and reasoned about.'],
                ['icon' => 'trophy', 'title' => 'Problems actually solved', 'description' => 'Shipped projects and real outcomes count. A solved problem outweighs a hundred idle commits.'],
                ['icon' => 'book-open', 'title' => 'Knowledge you shared', 'description' => 'Articles, talks, and documented decisions show the impact that spreads beyond your own work.'],
                ['icon' => 'shield-check', 'title' => 'Trust from real engineers', 'description' => 'Verified vouches from peers who have worked with you - proof that doesn’t come from a streak.'],
            ];

            $activityPoints = [
                'Commits and GitHub streaks',
                'Time spent online',
                'Pull request counts',
                'Follower or reaction counts',
                'How much you typed',
            ];
        @endphp

        {{-- ===================== NAV ===================== --}}
        <header class="site-header fixed inset-x-0 top-0 z-50 border-b border-transparent">
            <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('welcome') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo-black.png') }}" alt="ProoDev" class="h-7 w-auto dark:hidden" />
                    <img src="{{ asset('images/logo-white.png') }}" alt="ProoDev" class="hidden h-7 w-auto dark:block" />
                </a>

                <div class="hidden items-center gap-1 text-sm text-zinc-500 md:flex dark:text-zinc-400">
                    <a href="#why" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">Why meaning</a>
                    <a href="#how" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">How it works</a>
                    <a href="#passport" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">The passport</a>
                    <a href="#faq" class="rounded-lg px-3 py-2 transition hover:text-zinc-900 dark:hover:text-white">FAQ</a>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 transition hover:text-zinc-900 sm:inline-block dark:text-zinc-300 dark:hover:text-white">Sign in</a>
                    <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-[#3750eb] px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-[#3750eb]/25 transition hover:opacity-90">
                        Start proving - it's free
                    </a>
                    <x-theme-toggle />
                    <button type="button" data-mobile-menu-toggle class="inline-flex size-9 items-center justify-center rounded-lg border border-zinc-200 text-zinc-600 md:hidden dark:border-white/10 dark:text-zinc-300" aria-label="Toggle menu">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5"><path fill-rule="evenodd" d="{{ $iconPaths['x-mark'] }}" clip-rule="evenodd"/></svg>
                    </button>
                </div>
            </nav>

            <div data-mobile-menu class="hidden border-t border-zinc-200 bg-white/95 px-4 py-4 md:hidden dark:border-white/5 dark:bg-zinc-950/95">
                <div class="grid gap-1 text-sm">
                    <a href="#why" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">Why meaning</a>
                    <a href="#how" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">How it works</a>
                    <a href="#passport" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">The passport</a>
                    <a href="#faq" class="rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">FAQ</a>
                    <a href="{{ route('login') }}" class="mt-2 rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/5 dark:hover:text-white">Sign in</a>
                </div>
            </div>
        </header>

        {{-- ===================== HERO ===================== --}}
        <section id="top" class="relative mx-auto max-w-7xl overflow-hidden px-4 pb-20 pt-32 text-center sm:px-6 sm:pt-40 lg:px-8">
            <div class="relative mx-auto max-w-4xl animate-fade-up">
                <a href="#why" class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white/60 px-4 py-1.5 text-xs font-medium text-zinc-600 transition hover:border-[#3750eb]/40 hover:text-zinc-900 dark:border-white/10 dark:bg-white/5 dark:text-zinc-300 dark:hover:text-white">
                    <span class="relative flex size-2">
                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-[#3750eb] opacity-60"></span>
                        <span class="relative inline-flex size-2 rounded-full bg-[#3750eb]"></span>
                    </span>
                    Not activity. Meaning.
                </a>

                <h1 class="mt-8 text-2xl font-bold leading-tight tracking-tight text-zinc-900 sm:text-4xl lg:text-7xl dark:text-white">
                    Behind every repository is a story of persistence, learning, and growth.
                    <span class="text-gradient mt-2 block">ProoDev brings that story together in one place.</span>
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-zinc-600 sm:text-xl dark:text-zinc-400">
                    Behind every repository, pull request, and project is a story of curiosity, persistence, and problem-solving. ProoDev helps bring that story to life, transforming your work into a living record of your growth, achievements, and journey as an engineer.
                </p>

                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#3750eb] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#3750eb]/25 transition hover:opacity-90 sm:w-auto">
                        Start proving your work
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                    </a>
                    <a href="#how" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-white/60 px-6 py-3 text-sm font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-white sm:w-auto dark:border-white/10 dark:bg-white/5 dark:text-zinc-200 dark:hover:border-white/25 dark:hover:bg-white/10">
                        See how the evidence works
                    </a>
                </div>

                {{-- Trust line --}}
                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
                    <div class="flex -space-x-2.5">
                        @forelse ($engineers->take(5) as $engineer)
                            <flux:avatar :src="$engineer->avatarUrl()" :alt="$engineer->name" circle class="size-9 ring-2 ring-white dark:ring-zinc-950" />
                        @empty
                            <span class="flex size-9 items-center justify-center rounded-full bg-black text-xs font-bold text-white ring-2 ring-white dark:bg-white dark:text-black dark:ring-zinc-950">A</span>
                            <span class="flex size-9 items-center justify-center rounded-full bg-black text-xs font-bold text-white ring-2 ring-white dark:bg-white dark:text-black dark:ring-zinc-950">E</span>
                            <span class="flex size-9 items-center justify-center rounded-full bg-black text-xs font-bold text-white ring-2 ring-white dark:bg-white dark:text-black dark:ring-zinc-950">O</span>
                        @endforelse
                    </div>
                    <p class="text-sm text-zinc-500">
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $engineersCount }}</span> engineers with evidence-backed identities
                    </p>
                </div>
            </div>
        </section>

        {{-- ===================== WHY: ACTIVITY VS MEANING ===================== --}}
        <section id="why" class="relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">The difference</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Activity tells you someone showed up. Meaning tells you what they built.</h2>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">Every platform measures how much. ProoDev is built to measure how good, how deep, and how real.</p>
                </div>

                <div class="mx-auto mt-10 grid max-w-4xl gap-6 md:grid-cols-2">
                    {{-- Activity --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-white/[0.03]">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-red-500/10 text-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['x-mark'] }}" clip-rule="evenodd"/></svg>
                            </span>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">What activity measures</h3>
                        </div>
                        <p class="mt-3 text-sm text-zinc-500">Easy to game, easy to fake, and says almost nothing about the work.</p>
                        <ul class="mt-5 grid gap-2.5">
                            @foreach ($activityPoints as $point)
                                <li class="flex items-start gap-2.5 text-sm text-zinc-600 dark:text-zinc-300">
                                    <span class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full bg-red-500/10 text-red-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-2.5"><path fill-rule="evenodd" d="{{ $iconPaths['x-mark'] }}" clip-rule="evenodd"/></svg>
                                    </span>
                                    {{ $point }}
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Meaning --}}
                    <div class="rounded-xl border border-[#3750eb]/30 bg-gradient-to-br from-[#3750eb]/[0.06] to-transparent p-6 dark:border-[#3750eb]/40 dark:bg-white/[0.04]">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-[#3750eb]/10 text-[#3750eb] dark:text-[#8f9dff]">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['check'] }}" clip-rule="evenodd"/></svg>
                            </span>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">What meaning measures</h3>
                        </div>
                        <p class="mt-3 text-sm text-zinc-500">Real work, read and analyzed by AI, backed by verifiable evidence.</p>
                        <ul class="mt-5 grid gap-2.5">
                            @foreach ($meaningPoints as $point)
                                <li class="flex items-start gap-2.5 text-sm text-zinc-600 dark:text-zinc-300">
                                    <span class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full bg-[#3750eb]/10 text-[#3750eb] dark:text-[#8f9dff]">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-2.5"><path fill-rule="evenodd" d="{{ $iconPaths['check'] }}" clip-rule="evenodd"/></svg>
                                    </span>
                                    <span><span class="font-medium text-zinc-900 dark:text-white">{{ $point['title'] }}.</span> {{ $point['description'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== HOW IT WORKS ===================== --}}
        <section id="how" class="relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">How it works</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Meaning is discovered, not self-reported</h2>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">No forms, no claims, no "describe your impact". Paste the work. ProoDev reads it.</p>
                </div>

                <div class="mx-auto mt-10 grid max-w-5xl gap-6 md:grid-cols-3">
                    @foreach ([
                        ['icon' => 'code-bracket', 'number' => '01', 'title' => 'Add real evidence', 'description' => 'Paste a GitHub repo, GitLab project, article, talk, or shipped product URL. No self-reported claims - just work you can point to.'],
                        ['icon' => 'sparkles', 'number' => '02', 'title' => 'AI reads the actual work', 'description' => 'ProoDev fetches the source and articles, drafts an engineering report, and analyzes technical depth, structure, and decisions.'],
                        ['icon' => 'trophy', 'number' => '03', 'title' => 'Get a Meaning score', 'description' => 'Every piece of evidence flows into an explainable Engineering Magnitude score - factor by factor, tied to proof.'],
                    ] as $step)
                        <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-6 transition duration-300 hover:-translate-y-1 hover:border-[#3750eb]/50 hover:shadow-xl hover:shadow-[#3750eb]/10 dark:border-white/10 dark:bg-white/[0.03] dark:hover:border-[#3750eb]/30 dark:hover:bg-white/[0.05]">
                            <div class="flex items-center justify-between">
                                <span class="flex size-10 items-center justify-center rounded-lg bg-[#3750eb]/10 text-[#3750eb] dark:text-[#8f9dff]">
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

        {{-- ===================== PASSPORT MOCKUP ===================== --}}
        <section id="passport" class="relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">The passport</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">A public passport you can't fake</h2>
                        <p class="mt-4 leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Your Engineering Magnitude is computed from eight factors - evidence quality, technical depth, knowledge sharing,
                            breadth, consistency, community trust, verification, and open-source contribution. Every point points back to real evidence.
                        </p>
                        <ul class="mt-6 grid gap-2.5">
                            @foreach ([
                                'Backend architecture analyzed from source',
                                'Repositories verified and scored by AI',
                                'Articles and knowledge sharing included',
                                'Vouches from engineers who worked with you',
                            ] as $item)
                                <li class="flex items-start gap-2.5 text-sm text-zinc-700 dark:text-zinc-300">
                                    <span class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full bg-[#3750eb]/10 text-[#3750eb] dark:text-[#8f9dff]">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-2.5"><path fill-rule="evenodd" d="{{ $iconPaths['check'] }}" clip-rule="evenodd"/></svg>
                                    </span>
                                    {{ $item }}
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-8">
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#3750eb] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#3750eb]/25 transition hover:opacity-90">
                                Get your passport
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="{{ $iconPaths['arrow-right'] }}" clip-rule="evenodd"/></svg>
                            </a>
                        </div>
                    </div>

                    {{-- Mock passport card --}}
                    <div class="relative animate-float-slow">
                        <div class="pointer-events-none absolute -inset-6 -z-10 rounded-xl bg-[#3750eb]/10 blur-3xl" aria-hidden="true"></div>
                        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white/80 shadow-2xl shadow-zinc-900/10 backdrop-blur-xl dark:border-white/10 dark:bg-zinc-950/80">
                            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3 dark:border-white/5">
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-4 text-emerald-500"><path fill-rule="evenodd" d="{{ $iconPaths['shield-check'] }}" clip-rule="evenodd"/></svg>
                                    Public passport
                                </span>
                                <span class="rounded-full bg-emerald-400/10 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-300">Verified</span>
                            </div>

                            <div class="grid gap-5 p-5 sm:grid-cols-[auto_1fr]">
                                <div class="flex items-center justify-center">
                                    <div id="score-ring" class="score-ring size-32 rounded-full p-1.5" style="--score: 0">
                                        <div class="flex size-full items-center justify-center rounded-full bg-white dark:bg-zinc-950">
                                            <div class="text-center">
                                                <div class="text-4xl font-bold tabular-nums tracking-tight text-zinc-900 dark:text-white">
                                                    <span id="score-counter">0</span>
                                                </div>
                                                <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500">Magnitude</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="min-w-0">
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#3750eb]/10 text-lg font-bold text-[#3750eb] dark:text-[#8f9dff]">A</span>
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">Alex Morgan</div>
                                            <div class="flex items-center gap-1 truncate text-xs text-zinc-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-3"><path fill-rule="evenodd" d="{{ $iconPaths['map-pin'] }}" clip-rule="evenodd"/></svg>
                                                Berlin, Germany
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">Staff engineer focused on distributed systems. 12 repositories analyzed, 5 articles published, 8 vouches from peers.</p>
                                </div>
                            </div>

                            <div class="border-t border-zinc-200 px-5 py-4 dark:border-white/5">
                                <div class="grid grid-cols-2 gap-2.5">
                                    @foreach ([
                                        ['label' => 'Evidence quality', 'value' => '94'],
                                        ['label' => 'Technical depth', 'value' => '91'],
                                        ['label' => 'Knowledge sharing', 'value' => '87'],
                                        ['label' => 'Community trust', 'value' => '89'],
                                    ] as $factor)
                                        <div class="rounded-lg border border-zinc-200 bg-white/60 px-3 py-2 dark:border-white/10 dark:bg-white/[0.03]">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="truncate text-[11px] font-medium text-zinc-500">{{ $factor['label'] }}</span>
                                                <span class="text-xs font-bold tabular-nums text-zinc-900 dark:text-white">{{ $factor['value'] }}</span>
                                            </div>
                                            <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-zinc-200 dark:bg-white/10">
                                                <div class="h-full rounded-full bg-[#3750eb] dark:bg-[#5b6cff]" style="width: {{ $factor['value'] }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== COMMUNITY MARQUEE ===================== --}}
        <section class="relative overflow-hidden border-t border-zinc-200 py-14 dark:border-white/5">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">Engineers with proof, all over the world</h2>
                    <p class="mt-3 text-zinc-600 dark:text-zinc-400">Real developers building evidence-backed identities on ProoDev.</p>
                </div>
            </div>
            <div class="relative mt-10 overflow-hidden" data-marquee-mask>
                <div class="flex w-max animate-marquee gap-4 pr-4">
                    @php
                        $members = $engineers->count() > 0 ? $engineers->values() : collect([
                            (object) ['name' => 'Alex Morgan', 'location' => 'Berlin'],
                            (object) ['name' => 'Priya Sharma', 'location' => 'Mumbai'],
                            (object) ['name' => 'Kenji Sato', 'location' => 'Tokyo'],
                            (object) ['name' => 'Lena Fischer', 'location' => 'Prague'],
                            (object) ['name' => 'Diego Alvarez', 'location' => 'Lisbon'],
                            (object) ['name' => 'Amina Yusuf', 'location' => 'Lagos'],
                            (object) ['name' => 'Sofia Rossi', 'location' => 'Milan'],
                            (object) ['name' => 'Noah Chen', 'location' => 'Singapore'],
                        ]);
                    @endphp
                    @foreach ($members->merge($members) as $member)
                        <div class="flex items-center gap-2.5 rounded-full border border-zinc-200 bg-white/60 px-4 py-2 dark:border-white/10 dark:bg-white/[0.03]">
                            <span class="flex size-7 items-center justify-center rounded-full bg-[#3750eb]/10 text-xs font-bold text-[#3750eb] dark:text-[#8f9dff]">{{ strtoupper(substr($member->name ?? 'A', 0, 1)) }}</span>
                            <span class="whitespace-nowrap text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $member->name ?? 'Engineer' }}</span>
                            <span class="text-xs text-zinc-500">{{ $member->location ?? '' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ===================== FAQ ===================== --}}
        <section id="faq" class="relative overflow-hidden border-t border-zinc-200 dark:border-white/5">
            <div class="relative mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-[#3750eb] dark:text-[#8f9dff]">FAQ</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Questions from engineers like you</h2>
                </div>

                <div class="mt-10 grid gap-3">
                    @foreach ([
                        ['q' => 'What is Engineering Magnitude?', 'a' => 'Engineering Magnitude is an explainable 0-1000 score computed from your evidence across eight factors - evidence quality, technical depth, knowledge sharing, breadth, consistency, community trust, verification, and open-source contribution. Every point is tied to real evidence, not self-reporting.'],
                        ['q' => 'How is this different from GitHub streaks or contribution graphs?', 'a' => 'Those measure activity - how often you showed up. ProoDev reads the actual work and measures what it means: technical depth, problems solved, knowledge shared, and trust from peers. You cannot game meaning with a commit on a Sunday.'],
                        ['q' => 'Do I have to do anything to get scored?', 'a' => 'Just paste the work. A repository, article, talk, or project URL is enough. ProoDev fetches the source, drafts an engineering report, and scores the evidence automatically.'],
                        ['q' => 'Can I keep my work private?', 'a' => 'Absolutely. Every piece of evidence, project, and journal entry can be public or private. You control what appears on your passport, and only public evidence powers your discoverability.'],
                        ['q' => 'Is it really free?', 'a' => 'Yes. Adding evidence, running AI analysis, building your Engineering Magnitude, and sharing your public passport are all free. Your evidence and identity are yours to keep.'],
                        ['q' => 'Who is ProoDev for?', 'a' => 'Engineers who want their work to speak louder than their streaks - and recruiters smart enough to look past activity metrics.'],
                    ] as $index => $faq)
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
            <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-gradient-to-br from-[#f1f4ff] via-white to-[#eef1ff] px-6 py-16 text-center sm:px-16 dark:border-white/10 dark:from-[#3750eb]/25 dark:via-zinc-900 dark:to-[#3750eb]/10">
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[#3750eb]/5 blur-3xl" aria-hidden="true"></div>
                <div class="relative">
                    <h2 class="mx-auto max-w-2xl text-3xl font-bold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                        Stop collecting activity. Start proving meaning.
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-zinc-700 dark:text-zinc-300">
                        Your best work deserves better than a streak counter. Paste one repository and let the evidence speak.
                    </p>
                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 sm:w-auto dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            Create your free account
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
                    <a href="{{ route('welcome') }}" class="transition hover:text-zinc-900 dark:hover:text-white">Main site</a>
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

                var ring = document.getElementById('score-ring');
                var counter = document.getElementById('score-counter');
                if (ring && counter) {
                    var target = 82;
                    var start = performance.now();
                    function frame(t) {
                        var p = Math.min(1, (t - start) / 1800);
                        var eased = 1 - Math.pow(1 - p, 3);
                        var value = Math.round(target * eased);
                        counter.textContent = value;
                        ring.style.setProperty('--score', value);
                        if (p < 1) { requestAnimationFrame(frame); }
                    }
                    requestAnimationFrame(frame);
                }
            })();
        </script>

        @fluxScripts
    </body>
</html>
