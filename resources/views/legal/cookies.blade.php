@extends('layouts.legal')

@section('title', 'Cookie Policy')
@section('meta_description', 'How ProoDev uses cookies and similar technologies.')
@section('last_updated', 'August 10, 2026')

@section('content')
    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">1. What are cookies?</h2>
        <p>Cookies are small text files stored on your device when you visit a website. They help the site remember your preferences and understand how it is used. This policy explains the cookies {{ config('app.name', 'ProoDev') }} uses and why.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">2. Cookies we use</h2>
        <ul class="list-disc space-y-2 pl-5">
            <li><span class="font-semibold text-zinc-900 dark:text-white">Essential cookies:</span> required for the platform to work - for example, keeping you signed in and protecting your account. These cannot be disabled.</li>
            <li><span class="font-semibold text-zinc-900 dark:text-white">Preference cookies:</span> remember choices such as your language and appearance (light or dark mode).</li>
            <li><span class="font-semibold text-zinc-900 dark:text-white">Analytics cookies:</span> help us understand how the platform is used so we can improve it. These are non-essential and can be declined.</li>
        </ul>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">3. Managing cookies</h2>
        <p>You can control and delete cookies through your browser settings. Blocking essential cookies may prevent the platform from working correctly, including keeping you signed in. Most browsers also support "Do Not Track" signals, which we respect where practical.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">4. Changes</h2>
        <p>We may update this Cookie Policy as the platform evolves. Changes will be posted on this page with an updated "Last updated" date.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">5. Contact</h2>
        <p>Questions about cookies? Contact us at support@{{ strtolower(config('app.name', 'proodev')) }}.com.</p>
    </div>
@endsection
