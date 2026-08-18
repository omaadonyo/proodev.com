<x-mail.layout :subject="'Interview invitation on ProoDev'" docLabel="INTERVIEW">
    <h1>You're invited to an interview</h1>
    <p class="lead">
        <strong>{{ $interview->recruiter->name }}</strong> scheduled an interview with you on ProoDev.
        A calendar invite (.ics) is attached — click it to add to your calendar.
    </p>

    <div class="grid">
        <div class="col">
            <div class="card">
                <div class="card-title">Date</div>
                <p style="font-size: 15px; font-weight: 700; color: #111827; margin-top: 4px;">
                    {{ $interview->scheduled_at?->format('l, F j, Y') }}
                </p>
            </div>
        </div>
        <div class="col">
            <div class="card">
                <div class="card-title">Time</div>
                <p style="font-size: 15px; font-weight: 700; color: #111827; margin-top: 4px;">
                    {{ $interview->scheduled_at?->format('g:i A') }}
                    <span style="font-weight: 500; color: #6b7280;">({{ $interview->scheduled_at?->format('T') ?: 'local time' }})</span>
                </p>
            </div>
        </div>
        <div class="col">
            <div class="card">
                <div class="card-title">Mode</div>
                <p style="font-size: 15px; font-weight: 700; color: #111827; margin-top: 4px;">
                    {{ \Illuminate\Support\Str::title($interview->mode ?? 'Not set') }}
                </p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Interviewing with</div>
        <p style="font-size: 14px; color: #374151; margin-top: 4px;">
            {{ $interview->recruiter->name }}
            @if ($interview->recruiter->headline)
                <span style="color: #6b7280;">· {{ $interview->recruiter->headline }}</span>
            @endif
        </p>
    </div>

    <p class="muted" style="margin-top: 16px;">
        The recruiter will share the meeting link separately and will reach out through ProoDev chat if anything changes.
    </p>

    <div class="btn-row">
        <a class="btn" href="{{ route('passport', $interview->candidate->username) }}">View my passport</a>
        <a class="btn-ghost" href="{{ route('wirechat.chats.chats') }}">Open messages</a>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        Questions? Reply to this email and the recruiter will follow up.
    </p>
</x-mail.layout>
