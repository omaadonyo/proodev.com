<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Email previews · {{ config('app.name', 'ProoDev') }}</title>

        <link rel="icon" href="/images/favicon-128.png" sizes="128x128" type="image/png">
        <link rel="icon" href="/images/favicon-64.png" sizes="64x64" type="image/png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @fluxAppearance
    </head>
    <body class="min-h-screen bg-zinc-100 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <div class="mx-auto max-w-7xl px-6 py-10">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-widest text-zinc-400">Internal review tool</div>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight">Transactional email previews</h1>
                    <p class="mt-1 max-w-2xl text-sm text-zinc-500 dark:text-zinc-400">
                        Every email is rendered with realistic sample data exactly as it would be sent, the same
                        mailable classes, templates and layout. Sample records are rolled back; nothing is persisted.
                    </p>
                </div>
                <a href="{{ route('emails.preview.show', 'welcome') }}" target="_blank"
                   class="inline-flex h-9 items-center gap-1.5 rounded-[0.35rem] border border-zinc-300 bg-white px-5 py-2 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:hover:bg-zinc-800">
                    Open latest in new tab
                </a>
            </div>

            <div class="mt-8 grid gap-8">
                @foreach ($mails as $key => $mail)
                    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-3.5 dark:border-zinc-800">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300">
                                    {{ $mail['label'] }}
                                </span>
                                <span class="truncate text-sm text-zinc-500 dark:text-zinc-400">
                                    Subject: <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $mail['subject'] }}</span>
                                </span>
                            </div>
                            <a href="{{ route('emails.preview.show', $key) }}" target="_blank"
                               class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 transition hover:text-indigo-500 dark:text-indigo-400">
                                Open full page
                                <svg class="size-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M11 3a1 1 0 1 0 0 2h2.586l-6.293 6.293a1 1 0 1 0 1.414 1.414L15 6.414V9a1 1 0 1 0 2 0V4a1 1 0 0 0-1-1h-5Z"/>
                                    <path d="M5 5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-3a1 1 0 1 0-2 0v3H5V7h3a1 1 0 0 0 0-2H5Z"/>
                                </svg>
                            </a>
                        </header>
                        <iframe srcdoc="{{ $mail['html'] }}" class="h-[820px] w-full bg-[#f4f5f7]" title="{{ $mail['label'] }} preview"></iframe>
                    </section>
                @endforeach
            </div>

            <footer class="mt-10 pb-6 text-center text-xs text-zinc-400">
                {{ config('app.name', 'ProoDev') }} · Proof over claims. These previews reflect the exact HTML that reaches recipients.
            </footer>
        </div>
    </body>
</html>
