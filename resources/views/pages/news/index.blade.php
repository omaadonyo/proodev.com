<?php

use App\Models\News;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('News')] class extends Component
{
    use WithPagination;

    public string $search = '';

    #[Computed]
    public function featured()
    {
        return News::published()->featured()->with('author')->latest('published_at')->first();
    }

    #[Computed]
    public function articles()
    {
        return News::published()
            ->with('author')
            ->when($this->search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('body', 'like', "%{$this->search}%")
                ->orWhere('excerpt', 'like', "%{$this->search}%")))
            ->latest('published_at')
            ->paginate(12);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}
?>

<div class="mx-auto w-full max-w-5xl">
    <div class="grid gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="grid gap-1">
                <flux:heading size="xl">News</flux:heading>
                <flux:text>Announcements and updates from the ProoDev team.</flux:text>
            </div>
            <div class="w-full sm:w-72">
                <flux:input icon="magnifying-glass" type="search" placeholder="Search articles..." wire:model.live.debounce.300ms="search" />
            </div>
        </div>

        @if ($this->featured && ! $this->search)
            @php($news = $this->featured)
            <a href="{{ route('news.show', $news) }}" wire:navigate class="group relative overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                @if ($news->cover_url)
                    <img src="{{ $news->cover_url }}" alt="{{ $news->title }}" class="aspect-[21/9] w-full object-cover" loading="lazy" />
                @endif
                <div class="grid gap-2 p-6">
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" inset="top bottom" color="amber">Featured</flux:badge>
                        <span class="text-xs text-zinc-400">{{ $news->published_at?->format('F j, Y') }}</span>
                    </div>
                    <div class="text-xl font-bold leading-snug text-zinc-900 group-hover:text-accent dark:text-white">{{ $news->title }}</div>
                    @if ($news->excerpt)
                        <p class="line-clamp-2 text-sm text-zinc-500">{{ \App\Support\Markdown::plain($news->excerpt) }}</p>
                    @endif
                    @if ($news->author)
                        <div class="mt-1 flex items-center gap-2 text-sm text-zinc-400">
                            <flux:avatar :src="$news->author->avatarUrl()" :alt="$news->author->name" circle class="size-6" />
                            {{ $news->author->name }}
                        </div>
                    @endif
                </div>
            </a>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->articles as $news)
                @if ($news->is($this->featured) && ! $this->search)
                    @continue
                @endif
                <a href="{{ route('news.show', $news) }}" wire:navigate class="group flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white transition hover:border-accent dark:border-zinc-700 dark:bg-zinc-800">
                    @if ($news->cover_url)
                        <img src="{{ $news->cover_url }}" alt="{{ $news->title }}" class="aspect-video w-full object-cover" loading="lazy" />
                    @else
                        <div class="flex aspect-video w-full items-center justify-center bg-gradient-to-br from-violet-500/10 via-white to-cyan-500/10 text-accent dark:from-violet-500/15 dark:via-zinc-800 dark:to-cyan-500/15">
                            <flux:icon name="newspaper" />
                        </div>
                    @endif
                    <div class="grid flex-1 gap-2 p-4">
                        <div class="text-xs text-zinc-400">{{ $news->published_at?->format('F j, Y') }}</div>
                        <div class="line-clamp-2 font-semibold leading-snug text-zinc-900 group-hover:text-accent dark:text-white">{{ $news->title }}</div>
                        @if ($news->excerpt)
                            <p class="line-clamp-2 text-sm text-zinc-500">{{ \App\Support\Markdown::plain($news->excerpt) }}</p>
                        @endif
                        @if ($news->author)
                            <div class="mt-auto flex items-center gap-2 pt-2 text-xs text-zinc-400">
                                <flux:avatar :src="$news->author->avatarUrl()" :alt="$news->author->name" circle class="size-5" />
                                {{ $news->author->name }}
                            </div>
                        @endif
                    </div>
                </a>
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-600">
                    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-900">
                        <flux:icon name="newspaper" />
                    </div>
                    <flux:heading>{{ $this->search ? 'No articles match' : 'No articles yet' }}</flux:heading>
                    <flux:text class="mt-2">Check back soon for updates from the team.</flux:text>
                </div>
            @endforelse
        </div>

        @if ($this->articles->hasPages())
            <div>{{ $this->articles->links() }}</div>
        @endif
    </div>
</div>