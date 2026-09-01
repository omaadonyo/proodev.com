<x-mail.layout :subject="'New message from '.$sender->name.' on ProoDev'" docLabel="NEW MESSAGE">
    <h1>You have an unread message</h1>
    <p class="lead">
        <strong>{{ $sender->name }}</strong> messaged you on ProoDev and you haven't replied yet.
    </p>

    <div class="card">
        <div class="card-title">Latest message</div>
        <p class="muted" style="font-size: 14px; color: #374151;">"{{ \Illuminate\Support\Str::limit($preview, 220) }}"</p>
    </div>

    <div class="btn-row">
        <a class="btn" href="{{ route('wirechat.chats.chat', $conversation) }}">Open chat</a>
        <a class="btn-ghost" href="{{ route('wirechat.chats.chats') }}">View all messages</a>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        {{ $sender->name }} messaged you on ProoDev, reply in the app to continue the conversation.
    </p>
</x-mail.layout>
