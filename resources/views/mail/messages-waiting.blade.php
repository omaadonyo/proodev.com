<x-mail.layout subject="You have 2 new messages waiting" docLabel="MESSAGES">
    <h1>Hi {{ $user->name }}, you have messages</h1>
    <p class="lead">
        <strong style="color:#1a202c">2 new messages</strong> are waiting for you in your ProoDev inbox.
    </p>

    @if (! $user->is_verified)
        <div class="grid">
            <div class="value"><strong>One quick step first</strong></div>
            <p class="muted" style="font-size: 13px;">
                Messages are only visible to verified members. Verify your account to unlock your inbox
                and read what's waiting.
            </p>
        </div>
    @endif

    <div class="btn-row">
        @if ($user->is_verified)
            <a class="btn" href="{{ route('wirechat.chats.chats') }}">Read your messages</a>
        @else
            <a class="btn" href="{{ route('verify') }}">Verify to read your messages</a>
        @endif
        <a class="btn" href="{{ route('wirechat.chats.chats') }}" style="background:transparent;color:inherit;border:1px solid #d4d4d8;">View messages</a>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        We won't spoil who it's from — sign in and see for yourself.
    </p>
</x-mail.layout>