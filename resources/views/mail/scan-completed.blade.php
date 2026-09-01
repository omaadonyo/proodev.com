<x-mail.layout :subject="$context.' complete, '.count($items).' projects scanned'" docLabel="SCOUT">
    <h1>{{ $context }} complete</h1>
    <p class="lead">
        {{ count($items) }} item{{ count($items) === 1 ? ' was' : 's were' }} scanned and added to your evidence library.
    </p>

    <div class="card">
        <div class="card-title">Everything we scanned</div>
        @foreach ($items as $item)
            <div style="margin-top: 12px;">
                <div><strong>{{ $item['title'] }}</strong></div>
                <p class="muted" style="margin: 2px 0 0;">
                    {{ $item['type'] }}
                    @if ($item['score'] !== null)
                        · score {{ $item['score'] }}/100
                    @endif
                    · {{ $item['url'] }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="btn-row">
        <a class="btn" href="{{ route('devid', $user->handle()) }}">View my DevID</a>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        You opted in to scan &amp; evidence emails. Manage your preferences in
        <a href="{{ route('profile.edit') }}" style="color: #4f46e5;">Settings → Email preferences</a>.
    </p>
</x-mail.layout>
