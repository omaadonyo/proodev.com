<x-mail.layout :subject="'Your ProoDev account has been reinstated' " docLabel="ACCOUNT REINSTATED">
    <h1>Your account has been reinstated</h1>
    <p class="lead">
        Our team reviewed your case and overturned the plagiarism ban. Your account is active
        again and the public notice has been removed from your DevID.
    </p>

    <div class="card">
        <div class="card-title">Review details</div>
        <table class="totals">
            <tr>
                <td>Repository in question</td>
                <td>{{ $repoName }}</td>
            </tr>
            <tr>
                <td>Owned by</td>
                <td>{{ $repoOwner }}</td>
            </tr>
            <tr>
                <td>URL</td>
                <td><a href="{{ $repoUrl }}">{{ $repoUrl }}</a></td>
            </tr>
        </table>
    </div>

    <h2>What now?</h2>
    <p>
        Sign back in and continue building your DevID. If the repository was genuinely yours,
        add it again — the guard verifies ownership automatically.
    </p>
    <p>
        If any other claim was removed in error, reply to this email.
    </p>

    @if (! empty($passportUrl))
        <div class="btn-row">
            <a class="btn" href="{{ $passportUrl }}">View my DevID</a>
        </div>
    @endif

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        Questions about this decision? Email <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a> and our support team will help.
    </p>
</x-mail.layout>
