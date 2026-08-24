<x-mail.layout :subject="'New application — '.$application->user->name.' for '.$application->job->title.($copy ? ' (Admin copy)' : '')" docLabel="NEW APPLICATION">
    <h1>New application for {{ $application->job->title }}</h1>
    <p class="lead">
        {{ $application->user->name }} just applied at <strong>{{ $application->job->company->name }}</strong>.
    </p>

    <div class="grid">
        <div class="col">
            <div class="label">Candidate</div>
            <div class="value"><strong>{{ $application->user->name }}</strong><br>
                <span class="muted">{{ $application->user->handle() }}</span>
            </div>
        </div>
        <div class="col">
            <div class="label">Role</div>
            <div class="value"><strong>{{ $application->job->title }}</strong><br>
                <span class="muted">{{ $application->job->location ?: 'Remote' }}</span>
            </div>
        </div>
        <div class="col">
            <div class="label">Received</div>
            <div class="value">{{ $application->created_at->diffForHumans() }}</div>
        </div>
    </div>

    @if ($application->resume_path)
        <p class="muted" style="margin-bottom: 14px;">A PDF resume is attached.</p>
    @endif

    <div class="btn-row">
        @if ($copy)
            <a class="btn" href="{{ route('admin.index') }}">Open admin dashboard</a>
        @else
            <a class="btn" href="{{ route('companies.applicants', $application->job->company) }}">Review applicants</a>
        @endif
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        @if ($copy)
            This is an admin copy of the application alert sent to the hiring company.
        @else
            Open the applicant's DevID to review evidence before reaching out.
        @endif
    </p>
</x-mail.layout>
