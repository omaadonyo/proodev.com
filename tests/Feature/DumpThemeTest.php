<?php

use App\Models\User;

it('renders the admin header with collapse, spacing, and theme toggle', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();

    $html = (string) $response->getContent();

    expect($html)
        ->toContain('collapsible="true"')
        ->toContain('transition-[width,padding]')
        ->toContain('data-flux-sidebar-collapse')
        ->toContain('x-on:click="$flux.dark = ! $flux.dark"')
        ->toContain('flex items-center gap-3')
        ->toContain('bg-white')
        ->toContain('dark:bg-black')
        ->toContain('text-zinc-900')
        ->toContain('dark:text-white')
        ->toContain('rounded-full border border-zinc-200')
        ->not->toContain('<html lang="en" class="dark">');
});

it('keeps the appearance overrides and scrollbar styles in the stylesheet', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('.dark\:bg-zinc-800:where(.dark, .dark *)')
        ->toContain('#000000')
        ->toContain('.dark\:border-zinc-700:where(.dark, .dark *)')
        ->toContain('#1a1a1a')
        ->toContain('scrollbar-width: thin')
        ->toContain('::-webkit-scrollbar-thumb');
});

it('keeps nav groups visible as icons when the sidebar is collapsed', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('data-flux-sidebar-collapsed-desktop]) [data-flux-sidebar-group]')
        ->toContain('display: flex')
        ->toContain('> div:first-child')
        ->toContain('display: none');
});
