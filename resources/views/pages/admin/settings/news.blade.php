<?php

use App\Livewire\Concerns\ExportsSelectedRows;
use App\Models\News;
use App\Support\Markdown;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('News')] class extends Component
{
    use ExportsSelectedRows;

    public string $search = '';

    public string $statusFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [
        'title' => '',
        'slug' => '',
        'excerpt' => '',
        'body' => '',
        'cover_url' => '',
        'is_featured' => false,
        'published_at' => '',
    ];

    public function create(): void
    {
        $this->resetForm();
        $this->form['published_at'] = now()->format('Y-m-d\TH:i');
        $this->showForm = true;
        $this->editingId = null;
    }

    public function edit(int $id): void
    {
        $news = News::findOrFail($id);

        $this->editingId = $id;
        $this->form = [
            'title' => $news->title,
            'slug' => $news->slug,
            'excerpt' => (string) $news->excerpt,
            'body' => $news->body,
            'cover_url' => (string) $news->cover_url,
            'is_featured' => (bool) $news->is_featured,
            'published_at' => $news->published_at?->format('Y-m-d\TH:i'),
        ];
        $this->showForm = true;
    }

    public function updatedFormTitle(): void
    {
        if ($this->form['slug'] === '' || $this->form['slug'] === Str::slug($this->previousTitle())) {
            if ($this->form['title'] !== '') {
                $this->form['slug'] = Str::slug($this->form['title']);
            }
        }
    }

    private function previousTitle(): string
    {
        $news = News::find($this->editingId);

        return $news?->title ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'form.title' => ['required', 'string', 'max:200'],
            'form.slug' => ['required', 'string', 'max:225', 'alpha_dash', "unique:news,slug,{$this->editingId}"],
            'form.excerpt' => ['nullable', 'string', 'max:400'],
            'form.body' => ['required', 'string'],
            'form.cover_url' => ['nullable', 'string', 'url', 'max:2048'],
            'form.is_featured' => ['boolean'],
            'form.published_at' => ['nullable', 'date'],
        ]);

        $data = [
            'title' => $validated['form']['title'],
            'slug' => $validated['form']['slug'],
            'excerpt' => $validated['form']['excerpt'] ?: null,
            'body' => $validated['form']['body'],
            'cover_url' => $validated['form']['cover_url'] ?: null,
            'is_featured' => $validated['form']['is_featured'],
            'published_at' => $validated['form']['published_at'] ?: null,
        ];

        if ($this->editingId === null) {
            $data['author_id'] = auth()->id();
        }

        News::updateOrCreate(['id' => $this->editingId], $data);

        $this->showForm = false;
        unset($this->rows);

        Flux::toast(variant: 'success', text: $this->editingId ? 'News updated.' : 'News created.');
    }

    public function toggleFeatured(int $id): void
    {
        $news = News::findOrFail($id);
        $news->update(['is_featured' => ! $news->is_featured]);

        unset($this->rows);

        Flux::toast(variant: 'success', text: $news->is_featured ? 'Featured.' : 'Unfeatured.');
    }

    public function delete(int $id): void
    {
        News::findOrFail($id)->delete();

        unset($this->rows);

        Flux::toast(variant: 'success', text: 'News deleted.');
    }

    private function resetForm(): void
    {
        $this->form = [
            'title' => '',
            'slug' => '',
            'excerpt' => '',
            'body' => '',
            'cover_url' => '',
            'is_featured' => false,
            'published_at' => '',
        ];
        $this->resetErrorBag();
    }

    #[Computed]
    public function overview(): array
    {
        return [
            'total' => News::count(),
            'published' => News::published()->count(),
            'drafts' => News::whereNull('published_at')->count(),
            'scheduled' => News::scheduled()->count(),
        ];
    }

    #[Computed]
    public function rows()
    {
        return News::query()
            ->with('author')
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('body', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($query) => match ($this->statusFilter) {
                'draft' => $query->whereNull('published_at'),
                'scheduled' => $query->scheduled(),
                default => $query->published(),
            })
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function updatedSearch(): void
    {
        $this->selectedIds = [];
    }

    public function updatedStatusFilter(): void
    {
        $this->selectedIds = [];
    }

    protected function selectableIds(): array
    {
        return $this->rows->pluck('id')->toArray();
    }

    protected function exportData(): array
    {
        $selected = News::whereIn('id', $this->selectedIds)->orderByDesc('published_at')->get();

        $rows = $selected->map(fn (News $news) => [
            $news->title,
            $news->slug,
            Markdown::plain($news->excerpt),
            $news->is_featured ? 'Featured' : '',
            $news->published_at?->toDateString() ?? 'Draft',
            $news->status(),
            $news->views_count,
            $news->created_at->toDateTimeString(),
        ])->all();

        return [
            ['Title', 'Slug', 'Excerpt', 'Featured', 'Published', 'Status', 'Views', 'Created'],
            $rows,
        ];
    }

    protected function exportTitle(): string
    {
        return 'Selected news';
    }

    protected function exportBasename(): string
    {
        return 'news';
    }
}
?>

<x-pages::admin.settings.layout :heading="__('News')" :subheading="__('Publish announcements, changelogs, and community updates.')">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:button variant="primary" wire:click="create">
            <flux:icon name="plus" variant="micro" />
            Write article
        </flux:button>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Total articles</div>
            <div class="text-2xl font-bold">{{ number_format($this->overview['total']) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Published</div>
            <div class="text-2xl font-bold text-emerald-600">{{ number_format($this->overview['published']) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Scheduled</div>
            <div class="text-2xl font-bold text-sky-600">{{ number_format($this->overview['scheduled']) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-xs text-zinc-500">Drafts</div>
            <div class="text-2xl font-bold text-zinc-500">{{ number_format($this->overview['drafts']) }}</div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <flux:input icon="magnifying-glass" type="search" placeholder="Search articles..." wire:model.live.debounce.300ms="search" class="w-full sm:w-64" />
            <x-searchable-select wire:model.live="statusFilter" size="sm" placeholder="All statuses" class="w-40">
                <option value="">All statuses</option>
                <option value="published">Published</option>
                <option value="scheduled">Scheduled</option>
                <option value="draft">Drafts</option>
            </x-searchable-select>
        </div>
        @if (count($this->selectedIds) > 0)
            <span class="text-xs font-medium text-accent">{{ count($this->selectedIds) }} selected</span>
            <button type="button" wire:click="exportSelectedPdf" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
                <flux:icon name="document-arrow-down" variant="micro" />
                PDF
            </button>
            <button type="button" wire:click="exportSelectedExcel" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-sm font-medium text-zinc-700 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-200">
                <flux:icon name="table-cells" variant="micro" />
                Excel
            </button>
        @endif
    </div>

    <div class="overflow-x-auto rounded-md border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                    <th class="w-8 px-3 py-2.5 font-medium">
                        <input type="checkbox" wire:click="toggleSelectAll" {{ count($this->selectedIds) === $this->rows->count() && $this->rows->count() > 0 ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                    </th>
                    <th class="px-3 py-2.5 font-medium">Article</th>
                    <th class="px-3 py-2.5 font-medium">Status</th>
                    <th class="px-3 py-2.5 font-medium">Views</th>
                    <th class="px-3 py-2.5 font-medium">Featured</th>
                    <th class="px-3 py-2.5 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->rows as $news)
                    <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800 {{ in_array($news->id, $this->selectedIds) ? 'bg-accent/5' : '' }}">
                        <td class="px-3 py-2.5">
                            <input type="checkbox" wire:click="toggleSelect({{ $news->id }})" {{ in_array($news->id, $this->selectedIds) ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-3">
                                @if ($news->cover_url)
                                    <img src="{{ $news->cover_url }}" alt="{{ $news->title }}" class="size-14 shrink-0 rounded-lg object-cover" loading="lazy" />
                                @else
                                    <div class="flex size-14 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-xs font-bold text-accent">
                                        <flux:icon name="newspaper" />
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ route('news.show', $news) }}" target="_blank" class="line-clamp-1 font-medium hover:text-accent">{{ $news->title }}</a>
                                    <div class="line-clamp-1 text-xs text-zinc-500">{{ \App\Support\Markdown::plain($news->excerpt) ?: '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2.5">
                            @php($status = $news->status())
                            <flux:badge size="sm" inset="top bottom" :color="$status === 'published' ? 'emerald' : ($status === 'scheduled' ? 'sky' : 'zinc')">
                                {{ ucfirst($status) }}
                            </flux:badge>
                            @if ($news->published_at)
                                <div class="mt-1 text-xs tabular-nums text-zinc-500">{{ $news->published_at->format('M j, Y g:i A') }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 tabular-nums text-zinc-500">{{ number_format($news->views_count) }}</td>
                        <td class="px-3 py-2.5">
                            <flux:button size="sm" variant="subtle" wire:click="toggleFeatured({{ $news->id }})">
                                <flux:icon :name="$news->is_featured ? 'star' : 'star'" :variant="$news->is_featured ? 'solid' : 'outline'" class="{{ $news->is_featured ? 'text-amber-500' : 'text-zinc-400' }}" />
                            </flux:button>
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="flex justify-end gap-1.5">
                                <flux:button size="sm" variant="subtle" wire:click="edit({{ $news->id }})">Edit</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="delete({{ $news->id }})">Delete</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-sm text-zinc-500">
                            No articles yet. Write the first update for the community.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="news-form" wire:model="showForm" class="max-w-2xl">
        <form wire:submit="save" class="grid gap-4">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit article' : 'Write article' }}</flux:heading>
                <flux:text>Markdown is supported. New articles publish immediately - clear the date to keep a draft.</flux:text>
            </div>

            <flux:field>
                <flux:label>Title</flux:label>
                <flux:input wire:model="form.title" placeholder="e.g. v2.0 of the ProoDev DevID is here" wire:keydown.enter.prevent />
                <flux:error name="form.title" />
            </flux:field>

            <flux:field>
                <flux:label>Slug</flux:label>
                <flux:input wire:model="form.slug" placeholder="auto-generated from title" />
                <flux:error name="form.slug" />
            </flux:field>

            <flux:field>
                <flux:label>Excerpt</flux:label>
                <flux:textarea wire:model="form.excerpt" rows="2" placeholder="Short intro shown on cards" />
                <flux:error name="form.excerpt" />
            </flux:field>

            <flux:field>
                <flux:label>Body</flux:label>
                <flux:textarea wire:model="form.body" rows="10" placeholder="Write in markdown..." />
                <flux:error name="form.body" />
            </flux:field>

            <flux:field>
                <flux:label>Cover image URL</flux:label>
                <flux:input wire:model="form.cover_url" placeholder="https://… (optional)" />
                <flux:error name="form.cover_url" />
            </flux:field>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Publish at</flux:label>
                    <flux:input type="datetime-local" wire:model="form.published_at" />
                    <flux:error name="form.published_at" />
                </flux:field>
                <flux:switch wire:model="form.is_featured" label="Feature this article" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="subtle" @click="$flux.modal('news-form').close()">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save article</flux:button>
            </div>
        </form>
    </flux:modal>
</x-pages::admin.settings.layout>