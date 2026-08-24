<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen overflow-x-clip bg-white dark:bg-zinc-950">
        <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-[32rem] glow" aria-hidden="true"></div>
        <flux:sidebar sticky :collapsible="true" class="border-e border-zinc-200 bg-zinc-50 transition-[width,padding] duration-300 ease-in-out dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('home') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                @auth
                    @if (auth()->user()->isRecruiterOrCompanyAccount())
                        @php $owned = auth()->user()->ownedCompany(); @endphp
                        <flux:sidebar.group :heading="__('Overview')" class="grid">
                            <flux:sidebar.item icon="home" :href="route('home')" :current="request()->routeIs('home', 'dashboard', 'welcome')" wire:navigate>
                                {{ __('Home') }}
                            </flux:sidebar.item>
                        </flux:sidebar.group>

                        @if ($owned)
                            <flux:sidebar.group :heading="__('Company')" class="grid">
                                <flux:sidebar.item icon="squares-2x2" :href="route('companies.dashboard', $owned)" :current="request()->routeIs('companies.dashboard')" wire:navigate>
                                    {{ __('Dashboard') }}
                                </flux:sidebar.item>
                                <flux:sidebar.item icon="building-office-2" :href="route('companies.manage', $owned)" :current="request()->routeIs('companies.manage')" wire:navigate>
                                    {{ __('Company') }}
                                </flux:sidebar.item>
                                <flux:sidebar.item icon="briefcase" :href="route('companies.jobs.create', $owned)" :current="request()->routeIs('companies.jobs.create')" wire:navigate>
                                    {{ __('Post a Job') }}
                                </flux:sidebar.item>
                                <flux:sidebar.item icon="building-office-2" :href="route('companies.show', $owned)" :current="request()->routeIs('companies.show')" wire:navigate>
                                    {{ __('Public Profile') }}
                                </flux:sidebar.item>
                                <flux:sidebar.item icon="document-text" :href="route('companies.applicants', $owned)" :current="request()->routeIs('companies.applicants')" wire:navigate>
                                    {{ __('Applicants') }}
                                </flux:sidebar.item>
                            </flux:sidebar.group>
                        @endif
                    @else
                        <flux:sidebar.group :heading="__('Overview')" class="grid">
                            <flux:sidebar.item icon="home" :href="route('home')" :current="request()->routeIs('home', 'dashboard', 'welcome')" wire:navigate>
                                {{ __('Home') }}
                            </flux:sidebar.item>
                        </flux:sidebar.group>

                        @unless (auth()->user()->isAdmin())
                            <flux:sidebar.group :heading="__('Engineer')" class="grid">
                                <flux:sidebar.item icon="identification" :href="route('devid', auth()->user()->handle())" :current="request()->routeIs('passport')" wire:navigate>
                                    {{ __('My DevID') }}
                                </flux:sidebar.item>
                                <flux:sidebar.item icon="folder-git-2" :href="route('projects.index')" :current="request()->routeIs('projects.*')" wire:navigate>
                                    {{ __('Projects') }}
                                </flux:sidebar.item>
                                <flux:sidebar.item icon="book-open-text" :href="route('journal.index')" :current="request()->routeIs('journal.*')" wire:navigate>
                                    {{ __('Journal') }}
                                </flux:sidebar.item>
                                <flux:sidebar.item icon="finger-print" :href="route('vouches')" :current="request()->routeIs('vouches')" wire:navigate>
                                    {{ __('Vouches') }}
                                </flux:sidebar.item>
                                <flux:sidebar.item
                                    icon="chat-bubble-oval-left-ellipsis"
                                    :href="auth()->user()->isVerified() ? route('wirechat.chats.chats') : route('verify')"
                                    :current="request()->routeIs('wirechat.chats.*')"
                                    tooltip="Connect"
                                    wire:navigate
                                >
                                    <span class="flex w-full items-center justify-between gap-2">
                                        <span class="flex items-center gap-1.5">
                                            {{ __('Connect') }}
                                            <livewire:unread-messages-badge :key="'engineer-unread'" />
                                        </span>
                                        @unless (auth()->user()->isVerified())
                                            <span
                                                class="inline-flex shrink-0 items-center gap-1 rounded-full bg-amber-400/10 px-2 py-0.5 text-[10px] font-semibold text-amber-600 dark:text-amber-400"
                                                title="{{ __('Verify your account to read and send messages') }}"
                                            >
                                                <flux:icon name="lock-closed" variant="micro" class="size-2.5" />
                                                {{ __('Verify') }}
                                            </span>
                                        @endunless
                                    </span>
                                </flux:sidebar.item>
                            </flux:sidebar.group>
                        @endunless

                       
                    @endif
                @endauth

                @if (\App\Support\FeatureFlags::active('companies'))
                    <flux:sidebar.group :heading="__('Careers')" class="grid">
                        @auth
                            @unless (auth()->user()->isRecruiterOrCompanyAccount())
                                <flux:sidebar.item icon="document-text" :href="route('applications.index')" :current="request()->routeIs('applications.*')" wire:navigate>
                                    {{ __('My Applications') }}
                                </flux:sidebar.item>
                            @endunless
                        @endauth
                        <flux:sidebar.item icon="building-office-2" :href="route('companies.index')" :current="request()->routeIs('companies.index')" wire:navigate>
                            {{ __('Companies') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="briefcase" :href="route('jobs.index')" :current="request()->routeIs('jobs.index')" wire:navigate>
                            {{ __('Open Roles') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                @auth
                    @if (auth()->user()->isRecruiterAccount() || auth()->user()->isCompanyAccount())
                        <flux:sidebar.group :heading="__('Recruiter Intelligence')" class="grid">
                            <flux:sidebar.item icon="sparkles" :href="route('recruiter.index')" :current="request()->routeIs('recruiter.index')" wire:navigate>
                                {{ __('Intelligence Hub') }}
                            </flux:sidebar.item>
                        </flux:sidebar.group>

                        @php
                            $pipelineActiveStep = match (true) {
                                request()->routeIs('recruiter.search', 'recruiter.rankings', 'recruiter.alerts') => 1,
                                request()->routeIs('recruiter.compare', 'recruiter.validate', 'recruiter.interviews') => 2,
                                request()->routeIs('recruiter.workspace') => 3,
                                default => 0,
                            };

                            $pipelineSteps = [
                                1 => [
                                    'label' => __('Discover'),
                                    'items' => [
                                        ['icon' => 'magnifying-glass', 'label' => __('Evidence Search'), 'route' => 'recruiter.search'],
                                        ['icon' => 'arrow-trending-up', 'label' => __('Magnitude Rankings'), 'route' => 'recruiter.rankings'],
                                        ['icon' => 'bell-alert', 'label' => __('Talent Alerts'), 'route' => 'recruiter.alerts'],
                                    ],
                                ],
                                2 => [
                                    'label' => __('Evaluate'),
                                    'items' => [
                                        ['icon' => 'scale', 'label' => __('Compare Candidates'), 'route' => 'recruiter.compare'],
                                        ['icon' => 'document-check', 'label' => __('Resume Validation'), 'route' => 'recruiter.validate'],
                                        ['icon' => 'chat-bubble-oval-left-ellipsis', 'label' => __('Interview Builder'), 'route' => 'recruiter.interviews'],
                                    ],
                                ],
                                3 => [
                                    'label' => __('Hire'),
                                    'items' => [
                                        ['icon' => 'folder', 'label' => __('Agency Workspace'), 'route' => 'recruiter.workspace'],
                                    ],
                                ],
                            ];

                            $pipelineProgress = $pipelineActiveStep > 0 ? round(($pipelineActiveStep / 3) * 100) : 0;
                        @endphp

                        <div class="px-3 pb-1 pt-2 in-data-flux-sidebar-collapsed-desktop:hidden">
                            <div class="flex items-center justify-between px-1 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
                                <span>{{ __('Hiring journey') }}</span>
                                <span class="tabular-nums">{{ $pipelineActiveStep }}/3</span>
                            </div>
                            <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-zinc-200/80 dark:bg-white/10">
                                <div class="h-full rounded-full bg-zinc-900 dark:bg-white transition-all duration-500 ease-out" style="width: {{ $pipelineProgress }}%"></div>
                            </div>
                        </div>

                        @foreach ($pipelineSteps as $stepNumber => $step)
                            <div class="relative in-data-flux-sidebar-collapsed-desktop:hidden">
                                <div class="flex items-center gap-2 px-3 pb-1.5 pt-3">
                                    <span @class([
                                        'flex size-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold leading-none tabular-nums',
                                        'bg-gradient-to-br from-indigo-500 to-violet-500 text-white shadow-sm shadow-indigo-500/30' => $pipelineActiveStep > 0 && $stepNumber === $pipelineActiveStep,
                                        'bg-emerald-500/10 text-emerald-600 ring-1 ring-emerald-500/30 dark:text-emerald-400 dark:ring-emerald-400/30' => $pipelineActiveStep > 0 && $stepNumber < $pipelineActiveStep,
                                        'bg-zinc-100 text-zinc-400 ring-1 ring-zinc-200 dark:bg-white/5 dark:text-zinc-500 dark:ring-white/10' => $pipelineActiveStep === 0 || $stepNumber > $pipelineActiveStep,
                                    ])>
                                        @if ($pipelineActiveStep > 0 && $stepNumber < $pipelineActiveStep)
                                            <svg class="size-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                            </svg>
                                        @else
                                            {{ $stepNumber }}
                                        @endif
                                    </span>
                                    <span class="text-sm font-medium leading-none text-zinc-400 dark:text-zinc-500">{{ $step['label'] }}</span>
                                </div>

                                <div class="flex flex-col">
                                    @foreach ($step['items'] as $item)
                                        <flux:sidebar.item :icon="$item['icon']" :href="route($item['route'])" :current="request()->routeIs($item['route'])" wire:navigate>
                                            {{ $item['label'] }}
                                        </flux:sidebar.item>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                @endauth

                @auth
                    @if (auth()->user()->isAdmin())
                        <x-admin-nav />
                        <flux:sidebar.group :heading="__('Communications')" class="grid">
                            <flux:sidebar.item icon="chat-bubble-oval-left-ellipsis" :href="url('/admin/chats')" :current="request()->routeIs('wirechat.admin/*')">
                                {{ __('Chat Management') }}
                            </flux:sidebar.item>
                        </flux:sidebar.group>
                    @endif
                @endauth
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                @auth
                    <flux:sidebar.group :heading="__('Settings')" class="grid">
                        <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>
                            {{ __('Settings') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="envelope" :href="route('emails.preview')" :current="request()->routeIs('emails.preview*')">
                            {{ __('Email previews') }}
                        </flux:sidebar.item>
                        @unless (auth()->user()->isAdmin())
                            <flux:sidebar.item icon="receipt-percent" :href="route('billing')" :current="request()->routeIs('billing')" wire:navigate>
                                {{ __('Billing') }}
                            </flux:sidebar.item>
                        @endunless
                        @if (auth()->user()->isRecruiterOrCompanyAccount())
                            <flux:sidebar.item icon="credit-card" :href="route('subscription')" :current="request()->routeIs('subscription')" wire:navigate>
                                {{ __('Subscription') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="building-office" :href="route(auth()->user()->hasWorkspaceAccess() ? 'workspaces' : 'subscription')" :current="request()->routeIs('workspaces')" wire:navigate>
                                {{ auth()->user()->hasWorkspaceAccess() ? __('Manage Workspaces') : __('Upgrade for Workspaces') }}
                            </flux:sidebar.item>
                        @else
                            <flux:sidebar.item icon="currency-dollar" :href="route('credits')" :current="request()->routeIs('credits', 'auto-scan')" wire:navigate>
                                {{ __('Credits & Auto-Scan') }}
                            </flux:sidebar.item>
                            @if (\App\Support\FeatureFlags::active('verification'))
                                <flux:sidebar.item icon="check-badge" :href="route('verify')" :current="request()->routeIs('verify')" wire:navigate>
                                    {{ __('Verification') }}
                                </flux:sidebar.item>
                            @endif
                        @endunless
                    </flux:sidebar.group>

                    @if (\App\Support\FeatureFlags::active('verification') && ! auth()->user()->isAdmin() && ! auth()->user()->isRecruiterOrCompanyAccount() && ! auth()->user()->isVerified())
                        <x-verify-promo-banner />
                    @endif


                


                    

                @else
                    <flux:sidebar.item icon="arrow-right-end-on-rectangle" :href="route('login')">
                        {{ __('Sign in') }}
                    </flux:sidebar.item>
                @endauth
            </flux:sidebar.nav>

            @auth
                <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
            @endauth
        </flux:sidebar>

        @auth
            <flux:header class="lg:hidden">
                <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
                <flux:spacer />
                <livewire:notifications-bell />
                <x-theme-toggle />
                <flux:dropdown position="top" align="end">
                    <flux:profile avatar:src="{{ auth()->user()->avatarUrl() }}" avatar:circle icon-trailing="chevron-down" />
                    <flux:menu>
                        <flux:menu.radio.group>
                            <div class="flex items-center gap-2 px-1 py-1.5 text-sm">
                                <flux:avatar :src="auth()->user()->avatarUrl()" :alt="auth()->user()->name" circle />
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </flux:menu.radio.group>
                        <flux:menu.separator />
                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                        </flux:menu.radio.group>
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </flux:header>

            <div class="[grid-area:header] sticky top-0 z-20 hidden lg:block">
                @auth
                    @if (! auth()->user()->isAdmin() && ! auth()->user()->isRecruiterOrCompanyAccount() && app(\App\Services\ProfileCompletionService::class)->percentage(auth()->user()) <= 75)
                        <x-onboarding-prompt-bar :user="auth()->user()" />
                    @endif
                @endauth

                <div class="flex items-center gap-3 px-6 py-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <flux:sidebar.collapse class="hidden lg:flex" tooltip:position="bottom" />
                        @auth
                            @if (auth()->user()->isRecruiterAccount() || auth()->user()->isCompanyAccount())
                                <livewire:workspace-switcher :compact="true" />
                            @endif
                        @endauth
                        <flux:heading size="lg" class="truncate">{{ $title ?? config('app.name', 'ProoDev') }}</flux:heading>
                    </div>

                    <flux:spacer />

                    <div class="flex items-center gap-3">
                    @auth
                        @unless (auth()->user()->isRecruiterOrCompanyAccount())
                            <x-developer-summary-header :user="auth()->user()" />
                            <a
                                href="{{ route('projects.create') }}"
                                wire:navigate
                                class="hidden h-8 items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-4 text-sm font-semibold text-zinc-900 shadow-sm transition-all duration-200 hover:bg-zinc-50 hover:shadow dark:border-zinc-600 dark:bg-black dark:text-white dark:hover:border-zinc-500 dark:hover:bg-zinc-900 md:inline-flex"
                            >
                                <flux:icon name="plus" variant="micro" class="size-4" />
                                New Project
                            </a>
                        @endunless
                        <livewire:notifications-bell />
                        <x-theme-toggle />
                    @endauth
                </div>
                </div>
            </div>
        @endauth

        {{ $slot }}

        @auth
            <livewire:feed-suggestions />
        @endauth

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
