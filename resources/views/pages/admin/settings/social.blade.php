<?php

use App\Services\SiteSettings;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Social Links')] class extends Component
{
    public string $github = '';
    public string $twitter = '';
    public string $linkedin = '';
    public string $youtube = '';
    public string $discord = '';
    public string $x = '';
    public string $bsky = '';
    public string $tiktok = '';
    public string $pinkary = '';

    public function mount(): void
    {
        $settings = app(SiteSettings::class);
        $this->github = (string) $settings->get('social.github', '');
        $this->twitter = (string) $settings->get('social.twitter', '');
        $this->x = (string) ($settings->get('social.x', $settings->get('social.twitter', '')));
        $this->linkedin = (string) $settings->get('social.linkedin', '');
        $this->youtube = (string) $settings->get('social.youtube', '');
        $this->discord = (string) $settings->get('social.discord', '');
        $this->bsky = (string) $settings->get('social.bsky', '');
        $this->tiktok = (string) $settings->get('social.tiktok', '');
        $this->pinkary = (string) $settings->get('social.pinkary', '');
    }

    public function save(): void
    {
        $this->validate([
            'github' => ['nullable', 'url', 'max:2048'],
            'twitter' => ['nullable', 'url', 'max:2048'],
            'x' => ['nullable', 'url', 'max:2048'],
            'linkedin' => ['nullable', 'url', 'max:2048'],
            'youtube' => ['nullable', 'url', 'max:2048'],
            'discord' => ['nullable', 'url', 'max:2048'],
            'bsky' => ['nullable', 'url', 'max:2048'],
            'tiktok' => ['nullable', 'url', 'max:2048'],
            'pinkary' => ['nullable', 'url', 'max:2048'],
        ]);

        $settings = app(SiteSettings::class);
        $settings->set('social.github', trim($this->github));
        $settings->set('social.twitter', trim($this->x ?: $this->twitter));
        $settings->set('social.x', trim($this->x ?: $this->twitter));
        $settings->set('social.linkedin', trim($this->linkedin));
        $settings->set('social.youtube', trim($this->youtube));
        $settings->set('social.discord', trim($this->discord));
        $settings->set('social.bsky', trim($this->bsky));
        $settings->set('social.tiktok', trim($this->tiktok));
        $settings->set('social.pinkary', trim($this->pinkary));

        Flux::toast(variant: 'success', text: 'Social links updated, footers on landing pages and feed will reflect your changes.');
    }
};
?>

<x-pages::admin.settings.layout :heading="__('Social links')" :subheading="__('Links shown on all landing pages and the feed footer. Leave empty to hide an icon.')">
    <form wire:submit="save" class="grid max-w-2xl gap-4">
        <flux:field>
            <flux:label>X URL</flux:label>
            <flux:input wire:model="x" placeholder="https://x.com/your-handle" />
            <flux:error name="x" />
        </flux:field>

        <flux:field>
            <flux:label>Bluesky URL</flux:label>
            <flux:input wire:model="bsky" placeholder="https://bsky.app/profile/your-handle" />
            <flux:error name="bsky" />
        </flux:field>

        <flux:field>
            <flux:label>YouTube URL</flux:label>
            <flux:input wire:model="youtube" placeholder="https://youtube.com/@your-channel" />
            <flux:error name="youtube" />
        </flux:field>

        <flux:field>
            <flux:label>TikTok URL</flux:label>
            <flux:input wire:model="tiktok" placeholder="https://tiktok.com/@your-handle" />
            <flux:error name="tiktok" />
        </flux:field>

        <flux:field>
            <flux:label>Pinkary URL</flux:label>
            <flux:input wire:model="pinkary" placeholder="https://pinkary.com/@your-handle" />
            <flux:error name="pinkary" />
        </flux:field>

        <flux:field>
            <flux:label>GitHub URL (optional)</flux:label>
            <flux:input wire:model="github" placeholder="https://github.com/your-org" />
            <flux:error name="github" />
        </flux:field>

        <flux:field>
            <flux:label>LinkedIn URL (optional)</flux:label>
            <flux:input wire:model="linkedin" placeholder="https://linkedin.com/company/your-company" />
            <flux:error name="linkedin" />
        </flux:field>

        <flux:field>
            <flux:label>Discord URL (optional)</flux:label>
            <flux:input wire:model="discord" placeholder="https://discord.gg/your-invite" />
            <flux:error name="discord" />
        </flux:field>

        <div class="flex items-center gap-2">
            <flux:button type="submit" variant="primary">
                <flux:icon name="check" variant="micro" />
                Save social links
            </flux:button>
            <span class="text-xs text-zinc-500">Shown with copyright © {{ date('Y') }} ProoDev on all landing footers + feed.</span>
        </div>
    </form>

    @if (collect([$x, $bsky, $youtube, $tiktok, $pinkary, $github, $linkedin, $discord])->filter()->isNotEmpty())
        <div class="mt-8 rounded-xl bg-zinc-100 p-4 dark:bg-white/5">
            <div class="text-xs font-semibold uppercase tracking-widest text-zinc-500">Preview</div>
            <div class="mt-3 flex items-center gap-2">
                @foreach (collect([['url' => $x, 'label' => 'X'], ['url' => $bsky, 'label' => 'Bsky'], ['url' => $youtube, 'label' => 'YouTube'], ['url' => $tiktok, 'label' => 'TikTok'], ['url' => $pinkary, 'label' => 'Pinkary'], ['url' => $github, 'label' => 'GitHub'], ['url' => $linkedin, 'label' => 'LinkedIn'], ['url' => $discord, 'label' => 'Discord']])->filter(fn ($i) => filled($i['url'])) as $item)
                    <span class="flex size-8 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">{{ Str::substr($item['label'], 0, 1) }}</span>
                @endforeach
            </div>
            <div class="mt-2 text-xs text-zinc-500">© {{ date('Y') }} ProoDev. All rights reserved. • Proof over claims, every engineer backed by evidence.</div>
        </div>
    @endif
</x-pages::admin.settings.layout>
