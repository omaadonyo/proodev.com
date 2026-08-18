<x-mail.layout :subject="'Your ProoDev account has been banned for plagiarism' " docLabel="ACCOUNT BANNED">
    <h1>Your account has been banned</h1>
    <p class="lead">
        After a second plagiarism violation, your ProoDev account has been banned. A public notice now appears on your passport.
    </p>

    <div class="card">
        <div class="card-title">Violation</div>
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

    <h2>Can I appeal?</h2>
    <p>
        If you genuinely contributed to the repository, reply to this email with the details
        and our team will review your case.
    </p>

    <div class="btn-row">
        <a class="btn" href="mailto:{{ $supportEmail }}">Contact support</a>
    </div>
</x-mail.layout>
