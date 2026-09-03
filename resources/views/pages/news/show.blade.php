<?php

use App\Models\News;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('News')] class extends Component
{
    public News $article;

    public function mount(News $article): void
    {
        abort_unless($article->isPublished(), 404);

        $this->article = $article;

        $article->increment('views_count');
    }

    #[Computed]
    public function more()
    {
        return News::published()
            ->whereKeyNot($this->article->getKey())
            ->latest('published_at')
            ->take(3)
            ->get();
    }
}
?>

<div class="mx-auto w-full max-w-3xl">
    <article class="grid gap-6">
        <div class="grid gap-3">
            <a href="{{ route('news.index') }}" wire:navigate class="inline-flex w-fit items-center gap-1.5 text-sm font-medium text-zinc-500 transition hover:text-accent">
                <flux:icon name="arrow-left" variant="micro" />
                Back to news
            </a>

            <flux:heading size="xl">{{ $this->article->title }}</flux:heading>

            <div class="flex flex-wrap items-center gap-3 text-sm text-zinc-400">
                @if ($this->article->author)
                    <div class="flex items-center gap-2">
                        <flux:avatar :src="$this->article->author->avatarUrl()" :alt="$this->article->author->name" circle class="size-6" />
                        {{ $this->article->author->name }}
                    </div>
                @endif
                <span>{{ $this->article->published_at?->format('F j, Y') }}</span>
                <span class="inline-flex items-center gap-1"><flux:icon name="eye" variant="micro" /> {{ number_format($this->article->views_count) }} views</span>
            </div>
        </div>

        @if ($this->article->cover_url)
            <img src="{{ $this->article->cover_url }}" alt="{{ $this->article->title }}" class="aspect-video w-full rounded-2xl object-cover" loading="lazy" />
        @endif

        <x-markdown :text="$this->article->body" />
    </article>

    @if ($this->more->isNotEmpty())
        <div class="mt-12 grid gap-4">
            <flux:heading size="lg">More news</flux:heading>
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ($this->more as $news)
                    <a href="{{ route('news.show', $news) }}" wire:navigate class="group overflow-hidden rounded-xl bg-zinc-100 transition hover:border-accent dark:bg-white/5">
                        @if ($news->cover_url)
                            <img src="{{ $news->cover_url }}" alt="{{ $news->title }}" class="aspect-video w-full object-cover" loading="lazy" />
                        @endif
                        <div class="grid gap-1.5 p-4">
                            <div class="text-[11px] text-zinc-400">{{ $news->published_at?->format('M j, Y') }}</div>
                            <div class="line-clamp-2 text-sm font-semibold leading-snug text-zinc-900 group-hover:text-accent dark:text-white">{{ $news->title }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>