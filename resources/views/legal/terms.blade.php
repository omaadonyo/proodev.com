@extends('layouts.legal')

@section('title', 'Terms of Service')
@section('meta_description', 'The terms and conditions governing your use of the ProoDev platform.')
@section('last_updated', 'August 10, 2026')

@section('content')
    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">1. Acceptance of terms</h2>
        <p>By creating an account or using {{ config('app.name', 'ProoDev') }}, you agree to these Terms of Service. If you do not agree, please do not use the platform.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">2. Your account</h2>
        <p>You are responsible for your account credentials and for the activity that happens under your account. You must provide accurate information and keep it up to date. Notify us immediately if you believe your account has been compromised.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">3. Evidence and content you submit</h2>
        <p>You retain ownership of the content you submit. By submitting evidence, projects, articles, and other material, you grant {{ config('app.name', 'ProoDev') }} a limited license to process, store, analyze, and display that material as needed to operate the service, including showing it on your public passport.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">4. AI analysis</h2>
        <p>Scores and reports are produced by automated analysis of the material you submit. They are provided for informational purposes and are not a guarantee of hiring outcomes, quality, or performance. We may refine our analysis models over time, which can change scores.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">5. Acceptable use</h2>
        <p>You agree not to misuse the platform, including: attempting to access accounts you do not own, submitting material you do not have the right to use, gaming or misrepresenting evidence and scores, scraping at scale, or using the platform for unlawful activity. We may suspend accounts that violate these rules.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">6. Intellectual property</h2>
        <p>The platform, its branding, and its software are owned by {{ config('app.name', 'ProoDev') }} and its licensors. You may use them as intended by the service, but may not copy, resell, or redistribute them without permission.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">7. Services and subscriptions</h2>
        <p>Some features are free and some require a paid plan. Prices and billing terms are shown at the point of purchase and may change with notice. Paid plans renew automatically unless cancelled before the renewal date. Refunds are handled according to our stated policy at the time of purchase.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">8. Disclaimers</h2>
        <p>The service is provided "as is" and "as available", without warranties of any kind, whether express or implied. We do not warrant that the service will be uninterrupted, error-free, or that analysis results will be accurate or complete.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">9. Limitation of liability</h2>
        <p>To the maximum extent permitted by law, {{ config('app.name', 'ProoDev') }} will not be liable for indirect, incidental, special, consequential, or punitive damages, or for any loss of profits, data, or opportunities, arising from your use of the service.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">10. Termination</h2>
        <p>You may delete your account at any time. We may suspend or terminate accounts that violate these terms or that pose a risk to the platform or its users. Upon termination, your personal data is handled in accordance with our Privacy Policy.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">11. Changes to these terms</h2>
        <p>We may update these terms from time to time. We will post changes on this page and update the "Last updated" date. Continued use of the platform after changes take effect means you accept the updated terms.</p>
    </div>

    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">12. Governing law</h2>
        <p>These terms are governed by the laws of the jurisdiction in which {{ config('app.name', 'ProoDev') }} is established, without regard to conflict of law principles.</p>
    </div>
@endsection
