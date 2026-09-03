@php
    $sponsors = \App\Models\Sponsor::where('is_active', true)->running()->orderBy('sort_order')->orderByDesc('created_at')->get();
    $ads = \App\Models\Ad::where('is_active', true)->running()->orderBy('sort_order')->orderByDesc('created_at')->limit(1)->get();
@endphp

@if ($sponsors->isNotEmpty() || $ads->isNotEmpty())
    <section class="border-t border-zinc-200 bg-zinc-50 dark:border-white/5 dark:bg-white/[0.02]">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-3">

                @if ($ads->isNotEmpty())
                    <div class="lg:col-span-1">
                        <div class="rounded-xl bg-zinc-100 overflow-hidden dark:bg-white/5">
                            <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-2.5 dark:border-white/5">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Sponsored</span>
                            </div>
                            @foreach ($ads as $ad)
                                <a href="{{ $ad->target_url ?: '#' }}" target="_blank" rel="noopener noreferrer sponsored" class="group block">
                                    @if ($ad->image_url)
                                        <img src="{{ $ad->image_url }}" alt="{{ $ad->title }}" class="w-full object-contain" style="max-height:180px" loading="lazy" />
                                    @endif
                                    <div class="px-4 py-3">
                                        <div class="text-sm font-semibold text-zinc-900 group-hover:text-accent dark:text-white">{{ $ad->title }}</div>
                                        @if ($ad->description)
                                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 line-clamp-2">{{ $ad->description }}</p>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($sponsors->isNotEmpty())
                    <div class="{{ $ads->isNotEmpty() ? 'lg:col-span-2' : 'lg:col-span-3' }}">
                        <div class="rounded-xl bg-zinc-100 overflow-hidden dark:bg-white/5">
                            <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-2.5 dark:border-white/5">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Our Sponsors</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                @foreach ($sponsors as $sponsor)
                                    <a
                                        href="{{ $sponsor->website_url ?: '#' }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        title="{{ $sponsor->name }}"
                                        class="group flex flex-col items-center gap-2 rounded-lg p-3 transition hover:bg-zinc-50 dark:hover:bg-white/5"
                                    >
                                        @if ($sponsor->logo_url)
                                            <img src="{{ $sponsor->logo_url }}" alt="{{ $sponsor->name }}" class="h-10 w-auto object-contain" loading="lazy" />
                                        @else
                                            <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 text-sm font-bold text-zinc-600 dark:bg-white/10 dark:text-zinc-300">
                                                {{ \Illuminate\Support\Str::initials($sponsor->name) }}
                                            </div>
                                        @endif
                                        <span class="text-xs font-medium text-zinc-600 group-hover:text-accent dark:text-zinc-400">{{ \Illuminate\Support\Str::limit($sponsor->name, 16) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </section>
@endif
