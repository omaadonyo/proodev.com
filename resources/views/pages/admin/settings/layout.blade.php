<div class="flex items-start gap-8 max-lg:flex-col">
    <div class="w-full shrink-0 lg:w-[220px]">
        <flux:navlist aria-label="Platform settings">
            <flux:navlist.item :href="route('admin.settings.seo')" wire:navigate>{{ __('SEO') }}</flux:navlist.item>
            <flux:navlist.item :href="route('admin.settings.social')" wire:navigate>{{ __('Social') }}</flux:navlist.item>
            <flux:navlist.item :href="route('admin.settings.backups')" wire:navigate>{{ __('Backups') }}</flux:navlist.item>
            <flux:navlist.item :href="route('admin.settings.system')" wire:navigate>{{ __('System') }}</flux:navlist.item>
            <flux:navlist.item :href="route('admin.settings.news')" wire:navigate>{{ __('News') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="lg:hidden" />

    <div class="min-w-0 flex-1 self-stretch">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full">
            {{ $slot }}
        </div>
    </div>
</div>
