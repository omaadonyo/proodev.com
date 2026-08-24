@php
    $stage = $event->stage();
    $job = $application->job;
    $company = $job->company;

    $headline = match ($stage) {
        \App\Enums\HiringStage::ApplicationReceived => 'Your application has been received',
        \App\Enums\HiringStage::Reviewing => 'Your application is under review',
        \App\Enums\HiringStage::Reviewed => 'Your application has been reviewed',
        \App\Enums\HiringStage::Shortlisted => "You've been shortlisted 🎉",
        \App\Enums\HiringStage::Assessment => 'A technical assessment has been requested',
        \App\Enums\HiringStage::Interview => "You've been invited to an interview",
        \App\Enums\HiringStage::Offer => 'Congratulations — you have an offer',
        \App\Enums\HiringStage::NotSelected => 'Application closed',
        \App\Enums\HiringStage::RolePaused => 'Hiring is currently paused',
        \App\Enums\HiringStage::RoleClosed => 'Position closed',
        default => 'Your application has been updated',
    };
@endphp

<x-mail.layout subject="Application update — {{ $job->title }}" docLabel="APPLICATION UPDATE">
    <h1>{{ $headline }}</h1>
    <p class="lead">
        @if ($stage === \App\Enums\HiringStage::RolePaused)
            {{ $company?->name }} has temporarily paused this position. You have not been rejected.
        @elseif ($stage === \App\Enums\HiringStage::RoleClosed)
            This position is no longer accepting applications. Your application was closed because the role
            is no longer active — this is not a rejection of you or your work.
        @elseif ($stage === \App\Enums\HiringStage::NotSelected)
            The company has decided to proceed with other candidates.
        @else
            {{ $company?->name ?? 'The hiring team' }} moved your application for
            <strong style="color:#1a202c">{{ $job->title }}</strong> to:
            <strong style="color:#1a202c">{{ $stage->label() }}</strong>.
        @endif
    </p>

    @if ($event->feedback_category && $stage === \App\Enums\HiringStage::NotSelected)
        <div class="grid">
            <div class="col">
                <div class="value"><strong>Employer feedback</strong></div>
                <p class="muted" style="font-size: 13px;">
                    {{ \App\Enums\FeedbackCategory::from($event->feedback_category)->label() }}
                </p>
            </div>
            @if (filled($event->feedback_note))
                <div class="col" style="margin-top: 8px;">
                    <div class="value"><strong>Potential development areas</strong></div>
                    <p class="muted" style="font-size: 13px;">{{ nl2br(e($event->feedback_note)) }}</p>
                </div>
            @endif
        </div>
        <p class="muted" style="font-size: 11px; margin-top: 10px;">
            Employer feedback reflects the hiring team's own view of this role's requirements.
        </p>
    @endif

    <div class="btn-row">
        <a class="btn" href="{{ route('applications.index') }}">View application</a>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        ProoDev keeps you informed about meaningful hiring milestones. Application outcomes depend on each
        company's own process.
    </p>
</x-mail.layout>