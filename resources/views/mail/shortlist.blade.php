<x-mail.layout :subject="$title.' — '.$recruiter->name" docLabel="SHORTLIST" :wide="true">
    <h1>{{ $title }}</h1>
    <p class="lead">
        Prepared by <strong>{{ $recruiter->name }}</strong> on {{ now()->format('F j, Y') }}. Verified engineers rank first, then reputation.
    </p>

    @if ($rows === [])
        <div class="card">
            <div class="card-title">No matches yet</div>
            <p class="muted">Match a job description in Recruiter Intelligence to populate this shortlist.</p>
        </div>
    @else
        @php
            $total = count($rows);
            $verifiedCount = collect($rows)->where('verified', 'Yes')->count();
        @endphp
        <div class="stats">
            <div class="stat">
                <div class="stat-value">{{ $total }}</div>
                <div class="stat-label">Candidates</div>
            </div>
            <div class="stat">
                <div class="stat-value">{{ $verifiedCount }}</div>
                <div class="stat-label">Verified</div>
            </div>
            <div class="stat">
                <div class="stat-value">{{ now()->format('M j, Y') }}</div>
                <div class="stat-label">Prepared</div>
            </div>
        </div>

        <table class="items landscape" role="presentation">
            <thead>
                <tr>
                    <th class="num">#</th>
                    <th>Candidate</th>
                    <th>Location</th>
                    <th>Level</th>
                    <th class="num">XP</th>
                    <th class="num">Reputation</th>
                    <th>Verified</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td class="num">{{ $row['rank'] ?? $loop->iteration }}</td>
                        <td class="candidate">
                            <table class="cell" role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td class="avatar-td">
                                        <img class="avatar" src="{{ $row['avatar'] ?? '' }}" alt="{{ $row['name'] }}" width="42" height="42" />
                                    </td>
                                    <td class="info-td">
                                        <div class="candidate-name">{{ $row['name'] }}</div>
                                        <div class="candidate-headline">{{ $row['headline'] }}</div>
                                        <div class="candidate-headline">{{ $row['skills'] ?: '—' }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td>{{ $row['location'] ?: '—' }}</td>
                        <td>{{ $row['level'] ?? '' }}</td>
                        <td class="num">{{ number_format((int) ($row['xp'] ?? 0)) }}</td>
                        <td class="num">{{ number_format((int) ($row['reputation'] ?? 0)) }}</td>
                        <td>
                            @if (($row['verified'] ?? 'No') === 'Yes')
                                <span class="badge yes">Verified</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        
                        <td class="email"><a href="mailto:{{ $row['email'] }}">{{ $row['email'] }}</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="muted" style="font-size: 12px;">
            DevIDs: @foreach ($rows as $row) <a href="{{ $row['passport_url'] ?? '#' }}">{{ $row['username'] ?? $row['name'] }}</a>@if (! $loop->last) · @endif @endforeach
        </p>
    @endif

    <div class="btn-row">
        <a class="btn" href="{{ route('recruiter.search') }}">Open in Recruiter Intelligence</a>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        Candidate emails are shared only with verified recruiters and hiring companies.
    </p>
</x-mail.layout>
