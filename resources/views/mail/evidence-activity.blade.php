<x-mail.layout :subject="$analyzed ? 'Your scan of '.$evidence->title.' is ready' : 'Evidence added: '.$evidence->title" docLabel="EVIDENCE">
    @if ($analyzed)
        <h1>Your scan is ready</h1>
        <p class="lead">
            We finished analyzing <strong>{{ $evidence->title }}</strong>. It's now part of your evidence library.
        </p>

        <div class="card">
            <div class="card-title">{{ $evidence->title }}</div>
            <p class="muted" style="margin-top: 6px;">{{ $evidence->type->label() }} · {{ $evidence->url }}</p>
            @if ($evidence->ai_score !== null)
                <div class="label" style="margin-top: 12px;">Evidence score</div>
                <div class="value" style="font-size: 28px;"><strong>{{ $evidence->ai_score }}</strong><span style="font-size: 13px; color: #6b7280;"> / 100</span></div>
            @endif
        </div>
    @else
        <h1>Evidence added</h1>
        <p class="lead">
            <strong>{{ $evidence->title }}</strong> was added to your evidence library. We'll email you when the analysis is ready.
        </p>

        <div class="card">
            <div class="card-title">{{ $evidence->title }}</div>
            <p class="muted" style="margin-top: 6px;">{{ $evidence->type->label() }} · {{ $evidence->url }}</p>
        </div>
    @endif

    <div class="btn-row">
        <a class="btn" href="{{ route('passport', $evidence->user->handle()) }}">View my passport</a>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        You opted in to scan &amp; evidence emails. Manage your preferences in
        <a href="{{ route('profile.edit') }}" style="color: #4f46e5;">Settings → Email preferences</a>.
    </p>
</x-mail.layout>
