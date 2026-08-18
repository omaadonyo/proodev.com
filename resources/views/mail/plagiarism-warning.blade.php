<x-mail.layout :subject="'Warning: repository removed for plagiarism — '.$repoName" docLabel="PLAGIARISM WARNING">
    <h1>Plagiarism warning</h1>
    <p class="lead">
        A repository you added to your evidence library isn't your work, so we removed it before it reached your passport.
    </p>

    <div class="card">
        <div class="card-title">Removed repository</div>
        <table class="totals">
            <tr>
                <td>Repository</td>
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

    <h2>What this means for you</h2>
    <p>
        Claiming someone else's work violates our community guidelines. This is strike #{{ $strikeNumber }} on your account.
    </p>
    <p>
        Contributed to it? Add it through your linked GitHub account, or reply to this email to appeal.
    </p>

    <div class="card">
        <div class="card-title">Next steps</div>
        <p class="muted">
            Add only repositories you own or genuinely contribute to. A second violation results in a ban.
        </p>
    </div>

    @if (! empty($passportUrl))
        <div class="btn-row">
            <a class="btn" href="{{ $passportUrl }}">View my passport</a>
        </div>
    @endif
</x-mail.layout>
