<x-mail.layout subject="Application received — {{ $application->job->title }}" docLabel="APPLICATION">
    <h1>Application received</h1>
    <p class="lead">
        Hi {{ $application->user->name }}, your application for <strong>{{ $application->job->title }}</strong>
        at <strong>{{ $application->job->company->name }}</strong> is in.
    </p>

    <div class="grid">
        <div class="col">
            <div class="label">Role</div>
            <div class="value"><strong>{{ $application->job->title }}</strong></div>
        </div>
        <div class="col">
            <div class="label">Company</div>
            <div class="value"><strong>{{ $application->job->company->name }}</strong></div>
        </div>
        <div class="col">
            <div class="label">Submitted</div>
            <div class="value">{{ $application->created_at->toFormattedDateString() }}</div>
        </div>
    </div>

    <div class="btn-row">
        <a class="btn" href="{{ route('applications.index') }}">Track your application</a>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        The hiring team reviews each application with your DevID — real evidence, not self-reported claims.
    </p>
</x-mail.layout>
