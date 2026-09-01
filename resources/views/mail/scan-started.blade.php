<x-mail.layout :subject="$context.' started, '.$headline" docLabel="SCOUT">
    <h1>{{ $context }} started</h1>
    <p class="lead">{{ $headline }}</p>

    @if ($scanned !== [])
        <div class="card">
            <div class="card-title">Scanning now</div>
            <ul class="muted" style="margin: 6px 0 0; padding-left: 18px;">
                @foreach (array_slice($scanned, 0, 10) as $name)
                    <li style="margin-top: 4px;">{{ $name }}</li>
                @endforeach
            </ul>
            @if (count($scanned) > 10)
                <p class="muted" style="margin-top: 8px;">…and {{ count($scanned) - 10 }} more.</p>
            @endif
        </div>
    @endif

    @if ($queued !== [])
        <div class="card">
            <div class="card-title">Queued for background scanning</div>
            <ul class="muted" style="margin: 6px 0 0; padding-left: 18px;">
                @foreach (array_slice($queued, 0, 10) as $item)
                    <li style="margin-top: 4px;">{{ $item }}</li>
                @endforeach
            </ul>
            @if (count($queued) > 10)
                <p class="muted" style="margin-top: 8px;">…and {{ count($queued) - 10 }} more.</p>
            @endif
        </div>
    @endif

    <p class="lead" style="margin-top: 16px;">
        You'll get one more email when everything is scanned.
    </p>

    <div class="btn-row">
        <a class="btn" href="{{ route('devid', $user->handle()) }}">View my DevID</a>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        You opted in to scan &amp; evidence emails. Manage your preferences in
        <a href="{{ route('profile.edit') }}" style="color: #4f46e5;">Settings → Email preferences</a>.
    </p>
</x-mail.layout>
