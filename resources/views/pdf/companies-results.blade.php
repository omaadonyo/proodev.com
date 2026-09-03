<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('pdf._styles')
    <style>
        .blurred { filter: blur(2.5px); -webkit-filter: blur(2.5px); color: #a1a1aa !important; }
        .blurred * { filter: blur(2.5px); -webkit-filter: blur(2.5px); }
        .items th { padding: 5px 6px; font-size: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
        .items td { padding: 4px 6px; font-size: 8.5px; vertical-align: middle; }
    </style>
</head>
<body>
    <table class="header"><tr>
        <td style="vertical-align: middle;">
            <img src="{{ public_path('images/logo-black-400.png') }}" alt="ProoDev" class="brand-logo" />
            <div class="seller">proodev.com | ProoDev<br>Evidence-backed hiring every candidate is analyzed work, not self-reported claims</div>
        </td>
        <td style="vertical-align: middle; text-align: right;">
            <div class="doc-label">Evidence search results</div>
            <div class="doc-title">{{ $engineers->count() }} engineers</div>
            <div class="doc-sub">Generated {{ now()->format('M j, Y g:i A') }} @if($isGuest) · Preview register for unblurred export @endif</div>
        </td>
    </tr></table>

    @if(!empty(array_filter($filters)))
        <div style="margin: 12px 0; padding: 8px 12px; background: #f4f4f5; border: 1px solid #e4e4e7; border-radius: 6px; font-size: 10px; color: #52525b;">
            <strong>Filters:</strong>
            @if($filters['q']) <span style="margin-right: 10px;">Query: "{{ $filters['q'] }}"</span> @endif
            @if(!empty($filters['skills'])) <span style="margin-right: 10px;">Skills: {{ implode(', ', $filters['skills']) }}</span> @endif
            @if($filters['loc']) <span style="margin-right: 10px;">Location: "{{ $filters['loc'] }}"</span> @endif
            @if($filters['verified']) <span>Verified only</span> @endif
            @if($filters['online']) <span>Online now</span> @endif
            @if(empty($filters['q']) && empty($filters['skills']) && empty($filters['loc']) && !$filters['verified'] && !$filters['online']) No filters full network @endif
        </div>
    @endif

    <div style="margin: 10px 0; padding: 9px 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; font-size: 10px; color: #166534;">
        <strong>Verification as recruiter or company</strong> gives you access to over <strong>Top 10k developers</strong> around the world and all recruitment tools.
    </div>

    <table class="items" style="font-size: 9px;">
        <thead>
            <tr>
                <th style="width: 8mm;">#</th>
                <th>Engineer</th>
                <th>Location</th>
                <th>Skills</th>
                <th style="width: 18mm; text-align: center;">Verified</th>
                <th style="width: 18mm; text-align: center;">Score</th>
            </tr>
        </thead>
        <tbody>
            @forelse($engineers as $idx => $eng)
                @php $blur = $isGuest && $idx >= $visibleCount; @endphp
                <tr @class(['alt' => $loop->even]) @if($blur) class="blurred" @endif>
                    <td>{{ $idx + 1 }}</td>
                    <td>
                        <div style="display: flex; flex-direction:row; align-items: center; gap: 6px; @if($blur) filter: blur(2.5px); @endif">
                            <img src="{{ $eng['avatar'] }}" style="width: 22px; float:left; height: 22px; border-radius: 50%; object-fit: cover; border: 1px solid #e4e4e7; flex-shrink: 0;" />
                            <div style="padding-left:1rem;">
                                <strong style="font-size: 9px;">{{ $eng['name'] }}</strong><br>
                                <span style="font-size: 8px; color: #71717a;">{{ \Illuminate\Support\Str::limit($eng['headline'] ?? 'Proven engineer', 55) }}</span>
                            </div>
                        </div>
                    </td>
                    <td @if($blur) style="filter: blur(2.5px);" @endif>{{ $eng['location'] ?? '—' }}</td>
                    <td @if($blur) style="filter: blur(2.5px);" @endif>{{ implode(', ', array_slice($eng['skills'] ?? [], 0, 3)) ?: '—' }}</td>
                    <td style="text-align: center; font-size: 13px;">@if($eng['verified']) <span style="color: #059669;">✓</span> @else <span style="color: #dc2626;">✕</span> @endif</td>
                    <td style="text-align: center; font-weight: 700;" @if($blur) class="blurred" @endif>{{ $eng['reputation'] ?? 0 }}%</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align: center; padding: 20px; color: #71717a;">No engineers match your filters.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 10px; padding: 7px; background: #fafafa; border: 1px solid #e4e4e7; border-radius: 5px; font-size: 8px; color: #71717a; text-align: center;">
        ProoDev — proof over claims · proodev.com/for-companies · {{ $engineers->count() }} results · Verification unlocks 10k+ developers & top 200 worldwide
    </div>
</body>
</html>
