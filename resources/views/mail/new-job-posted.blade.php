<x-mail.layout :subject="'New role: '.$job->title.' at '.$job->company->name" docLabel="NEW ROLE">
    <h1>A new role is open</h1>
    <p class="lead">
        <strong>{{ $job->company->name }}</strong> posted <strong>{{ $job->title }}</strong> — and your DevID looks like a strong fit.
    </p>

    <div class="card">
        <div class="card-title">{{ $job->title }}</div>
        <p class="muted" style="margin-top: 6px;">
            {{ $job->location ?: 'Remote' }}{{ $job->is_remote && $job->location ? ' · Remote' : '' }}
            @if ($job->salary_min)
                · {{ $job->currency }} {{ number_format($job->salary_min) }}{{ $job->salary_max ? '–'.number_format($job->salary_max) : '' }}
            @endif
        </p>
        @if ($job->description)
            <p class="muted" style="margin-top: 10px; font-size: 14px; color: #374151;">
                {{ \Illuminate\Support\Str::limit($job->description, 280) }}
            </p>
        @endif
    </div>

    <div class="btn-row">
        <a class="btn" href="{{ route('jobs.show', [$job->company, $job]) }}">View role &amp; apply</a>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        You opted in to new job offers. Manage your preferences in
        <a href="{{ route('profile.edit') }}" style="color: #4f46e5;">Settings → Email preferences</a>.
    </p>
</x-mail.layout>
