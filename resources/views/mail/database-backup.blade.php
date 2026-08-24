<x-mail.layout subject="Database backup ready" docLabel="DATABASE BACKUP">
    <h1>Database backup ready</h1>
    <p class="lead">A fresh snapshot of the ProoDev database is attached to this email.</p>

    <div class="grid">
        <div class="col">
            <div class="value"><strong>{{ $backupRun->humanSize() }}</strong></div>
            <p class="muted" style="font-size: 13px;">Backup size</p>
        </div>
        <div class="col">
            <div class="value"><strong>{{ $backupRun->file_name }}</strong></div>
            <p class="muted" style="font-size: 13px;">SQL dump file</p>
        </div>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        Generated automatically on {{ $backupRun->completed_at?->format('F j, Y g:i A') }}.
        The attached .sql file can be imported into MySQL in one command:
    </p>
    <p class="muted" style="font-size: 12px; margin-bottom: 8px;">
        <strong style="color:#1a202c">mysql -u USER -p DATABASE < {{ $backupRun->file_name }}</strong>
    </p>
</x-mail.layout>