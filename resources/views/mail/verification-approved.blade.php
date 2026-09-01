<x-mail.layout :subject="'Verification approved, '.($request->label ?: 'professional identity').($copy ? ' (Admin copy)' : '')" docLabel="VERIFIED">
    <h1>You're verified. 🎉</h1>
    <p class="lead">
        {{ $request->user->name }}, your ProoDev verification has been approved @if ($request->label) <strong>{{ $request->label }}</strong> @endif.
    </p>

    <div class="card">
        <div class="card-title">What changed</div>
        <p class="muted">
            Your profile now carries a verified badge, and you've unlocked verified perks such as community messaging.
        </p>
    </div>

    <div class="btn-row">
        <a class="btn" href="{{ route('devid', $request->user->handle()) }}">View your DevID</a>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        @if ($copy)
            This is an admin copy, no action required.
        @else
            Questions about your verification? Reply to this email and we'll help.
        @endif
    </p>
</x-mail.layout>
