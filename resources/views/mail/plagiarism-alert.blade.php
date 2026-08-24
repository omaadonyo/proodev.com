<x-mail.layout :subject="'Alert: someone tried to claim your repository as their own' " docLabel="REPO PROTECTED">
    <h1>Your repository was protected</h1>
    <p class="lead">
        Someone tried to add your repository to their evidence library as their own proof.
        Our plagiarism guard removed the claim @if (! empty($offenderName)) and issued a warning to <strong>{{ $offenderName }}</strong> @endif.
    </p>

    <div class="card">
        <div class="card-title">Protected repository</div>
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

    <p>
        No action needed — the copied claim never appeared publicly. Repeat attempts are banned.
    </p>

    @if (! empty($passportUrl))
        <div class="btn-row">
            <a class="btn" href="{{ $passportUrl }}">View my DevID</a>
        </div>
    @endif
</x-mail.layout>
