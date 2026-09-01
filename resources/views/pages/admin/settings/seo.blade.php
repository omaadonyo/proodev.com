<?php

use App\Services\SiteSettings;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('SEO Settings')] class extends Component
{
    public string $metaKeywords = '';

    public function mount(): void
    {
        $this->metaKeywords = app(SiteSettings::class)->metaKeywords();
    }

    public function save(): void
    {
        $this->validate([
            'metaKeywords' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        app(SiteSettings::class)->set('seo.meta_keywords', trim($this->metaKeywords));

        Flux::toast(variant: 'success', text: 'SEO keywords updated.');
    }
}
?>

<x-pages::admin.settings.layout :heading="__('SEO keywords')" :subheading="__('Set the meta keywords written into the head of public pages.')">
    <form wire:submit="save" class="grid max-w-2xl gap-4">
        <flux:field>
            <flux:label>Meta keywords</flux:label>
            <flux:textarea wire:model="metaKeywords" rows="5" placeholder="proodev, engineering magnitude, evidence-backed portfolio, ..." />
            <flux:error name="metaKeywords" />
            <flux:description>Comma separated list of keywords used for search engine indexing.</flux:description>
        </flux:field>

        <div>
            <flux:button type="submit" variant="primary">
                <flux:icon name="check" variant="micro" />
                Save keywords
            </flux:button>
        </div>
    </form>
</x-pages::admin.settings.layout>