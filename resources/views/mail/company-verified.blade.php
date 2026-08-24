<x-mail.layout subject="Recruiter tools unlocked" docLabel="VERIFIED">
    <h1>You're verified, {{ $payment->company?->name }} 🎉</h1>
    <p class="lead">Your hiring verification is active — invoice #{{ $payment->id }} is confirmed.</p>

    <div class="grid">
        <div class="col">
            <div class="value"><strong>Unlocked</strong></div>
            <p class="muted" style="font-size: 13px;">Full recruiter & company tools</p>
        </div>
        <div class="col">
            <div class="value"><strong>Live now</strong></div>
            <p class="muted" style="font-size: 13px;">Your held job post published automatically</p>
        </div>
    </div>

    <div class="btn-row">
        <a class="btn" href="{{ route('companies.manage', $payment->company) }}">Manage your jobs</a>
    </div>

    <div class="divider"></div>
    <p class="muted" style="font-size: 12px;">Welcome to evidence-based hiring on ProoDev.</p>
</x-mail.layout>