@php
    $adminTabs = [
        ['label' => 'Overview', 'route' => 'admin.index', 'icon' => 'squares-2x2'],
        ['label' => 'Analytics', 'route' => 'admin.analytics', 'icon' => 'chart-bar'],
        ['label' => 'Verifications', 'route' => 'admin.verifications', 'icon' => 'check-badge'],
        ['label' => 'Vouches', 'route' => 'admin.vouches', 'icon' => 'chat-bubble-oval-left-ellipsis'],
        ['label' => 'Reports', 'route' => 'admin.reports', 'icon' => 'flag'],
        ['label' => 'Plagiarism', 'route' => 'admin.plagiarism', 'icon' => 'exclamation-triangle'],
        ['label' => 'Users', 'route' => 'admin.users', 'icon' => 'users'],
        ['label' => 'Companies', 'route' => 'admin.companies', 'icon' => 'building-office-2'],
        ['label' => 'Subscriptions', 'route' => 'admin.subscriptions', 'icon' => 'credit-card'],
        ['label' => 'Auto-Scan', 'route' => 'admin.auto-scan', 'icon' => 'arrow-path'],
        ['label' => 'Sales', 'route' => 'admin.sales', 'icon' => 'currency-dollar'],
        ['label' => 'AI Models', 'route' => 'admin.ai', 'icon' => 'cpu-chip'],
        ['label' => 'Payments', 'route' => 'admin.payments', 'icon' => 'banknotes'],
        ['label' => 'Ads', 'route' => 'admin.ads', 'icon' => 'megaphone'],
        ['label' => 'Sponsors', 'route' => 'admin.sponsors', 'icon' => 'hand-raised'],
        ['label' => 'Settings', 'route' => 'admin.settings', 'icon' => 'cog-6-tooth'],
        ['label' => 'Feature Requests', 'route' => 'admin.feature-requests', 'icon' => 'light-bulb'],
    ];
@endphp

<flux:sidebar.group :heading="__('Administration')" class="grid">
    @foreach ($adminTabs as $tab)
        <flux:sidebar.item
            :icon="$tab['icon']"
            :href="route($tab['route'])"
            :current="request()->routeIs($tab['route'], $tab['route'].'/*')"
            wire:navigate
        >
            {{ $tab['label'] }}
        </flux:sidebar.item>
    @endforeach
</flux:sidebar.group>
