<x-mail.layout subject="{{ $vouch->voucher->name }} vouched for you on ProoDev" docLabel="VOUCH">
    <h1>Someone vouched for you</h1>
    <p class="lead">
        <strong>{{ $vouch->voucher->name }}</strong> vouched for you on ProoDev —
        a {{ $vouch->type->label() }} vouch @if ($vouch->skill) for <strong>{{ $vouch->skill->name }}</strong> @endif.
    </p>

    @if ($vouch->message)
        <div class="card">
            <div class="card-title">What they said</div>
            <p class="muted" style="font-size: 14px; color: #374151;">"{{ $vouch->message }}"</p>
        </div>
    @endif

    <div class="btn-row">
        <a class="btn" href="{{ route('passport', $vouch->vouchee->handle()) }}">See your passport</a>
        <a class="btn-ghost" href="{{ route('vouches') }}">Manage vouches</a>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        Vouches are weighted by the giver's own proven track record and anchored to evidence.
    </p>
</x-mail.layout>
