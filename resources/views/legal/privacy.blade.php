@extends('layouts.legal')

@section('title', 'Privacy Policy')
@section('meta_description', 'How ProoDev collects, uses, and protects your personal data and evidence.')
@section('last_updated', 'August 10, 2026')

@section('content')
    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">1. Information we collect</h2>
        <p>When you create an account, we collect the information you provide: your name, email address, username, profile details, and location. When you connect external accounts or submit work, we collect the URLs and source material you choose to share. We also collect basic usage data, such as how you interact with the platform, so we can improve it.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">2. How we use your information</h2>
        <p>We use your information to run and improve the service: to create and authenticate your account, to analyze the evidence you submit, to compute your Engineering Magnitude score, to show your public passport to others, and to communicate with you about the platform. We never sell your personal information.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">3. Public evidence and passports</h2>
        <p>Passports are public by default so the community can verify work and find collaborators. You control what appears on your passport, and every piece of evidence, project, and journal entry can be public or private. Only public evidence powers your discoverability on the platform.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">4. AI analysis of public sources</h2>
        <p>When you paste a repository, article, or project URL, our AI fetches and reads the source material to draft an engineering report. We only process material you submit, and we only retain the analysis and any excerpts required to back your evidence on the platform.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">5. Data retention</h2>
        <p>We keep your account and evidence for as long as your account is active. If you delete your account, we remove your personal data and public profiles, subject to legal or accounting obligations that may require us to retain certain records.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">6. Your rights</h2>
        <p>Depending on where you live, you may have the right to access, correct, export, or delete your personal data, and to object to or restrict certain processing. You can exercise most of these directly from your account settings, or contact us and we will help.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">7. Contact</h2>
        <p>If you have questions about this policy or your data, please contact us at privacy@{{ strtolower(config('app.name', 'proodev')) }}.com. We will respond as soon as we can.</p>
    </div>
@endsection
