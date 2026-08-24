<x-mail.layout subject="New contact message from {{ $senderName }}" docLabel="CONTACT">
    <h1>New contact message</h1>
    <p class="lead">{{ $senderName }} sent a message through the ProoDev About page.</p>

    <div class="grid">
        <div class="col">
            <div class="value"><strong>{{ $senderName }}</strong></div>
            <p class="muted" style="font-size: 13px;">{{ $senderEmail }}</p>
        </div>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px; margin-bottom: 8px;">Message:</p>
    <p style="font-size: 14px; line-height: 1.7; color: #1a202c;">{{ nl2br(e($messageBody)) }}</p>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        Reply directly to this email to respond to {{ $senderName }}.
    </p>
</x-mail.layout>