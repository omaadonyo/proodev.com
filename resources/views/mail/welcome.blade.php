<x-mail.layout subject="Welcome to ProoDev, {{ $user->name }}" docLabel="WELCOME">
    <h1>Welcome to ProoDev, {{ $user->name }}!</h1>
    <p class="lead">Your evidence-backed engineering identity is ready.</p>

    <p class="muted" style="margin-bottom: 16px;">Here's how to get started:</p>

    <div class="grid">
        <div class="col">
            <div class="value"><strong>1 · Complete your profile</strong></div>
            <p class="muted" style="font-size: 13px;">Add your skills, bio and links.</p>
        </div>
        <div class="col">
            <div class="value"><strong>2 · Submit a project</strong></div>
            <p class="muted" style="font-size: 13px;">Publish real work and get AI-analyzed evidence.</p>
        </div>
        <div class="col">
            <div class="value"><strong>3 · Get verified</strong></div>
            <p class="muted" style="font-size: 13px;">Claim your proo.dev short link and verified badge.</p>
        </div>
    </div>

    <div class="btn-row">
        <a class="btn" href="{{ route('dashboard') }}">Start building</a>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px; margin-bottom: 8px;">
        Your username is <strong style="color:#1a202c">{{ $user->handle() }}</strong> — view your public
        <a href="{{ route('devid', $user->handle()) }}">passport</a> anytime.
    </p>
    <p class="muted" style="font-size: 12px;">
        Questions? Just reply to this email and we'll help you out.
    </p>
</x-mail.layout>
