<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('pdf._styles')
    <style>
        .blurred { filter: blur(3px); -webkit-filter: blur(3px); color: #a1a1aa !important; }
        .blurred * { filter: blur(3px); -webkit-filter: blur(3px); }
        .lock-badge { display: inline-block; background: #fef3c7; color: #92400e; border: 1px solid #fde68a; border-radius: 9999px; padding: 1px 6px; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
    </style>
</head>
<body>
    <table class="header"><tr>
        <td style="vertical-align: middle;">
            <img src="{{ public_path('images/logo-black-400.png') }}" alt="ProoDev" class="brand-logo" />
            <div class="seller">proodev.com | ProoDev<br>Evidence-backed hiring — every candidate is analyzed work, not self-reported claims</div>
        </td>
        <td style="vertical-align: middle; text-align: right;">
            <div class="doc-label">Evidence search results</div>
            <div class="doc-title">{{ $engineers->count() }} engineers</div>
            <div class="doc-sub">Generated {{ now()->format('M j, Y g:i A') }} @if($isGuest) · Preview — register for unblurred export @endif</div>
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
            @if(empty($filters['q']) && empty($filters['skills']) && empty($filters['loc']) && !$filters['verified'] && !$filters['online']) No filters — full network @endif
        </div>
    @endif

    @if($isGuest)
        <div style="margin: 10px 0; padding: 10px 14px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; font-size: 11px; color: #92400e;">
            <strong>🔒 Preview — {{ $visibleCount }} of {{ $engineers->count() }} fully visible.</strong> Register as a company at proodev.com/for-companies to download the unblurred report with all evidence, scores and contact details.
        </div>
    @endif

    <table class="items">
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
                        <div style="display: flex; align-items: center; gap: 7px; @if($blur) filter: blur(2.5px); @endif">
                            <img src="{{ $eng['avatar'] }}" style="width: 26px; height: 26px; border-radius: 50%; object-fit: cover; border: 1px solid #e4e4e7; flex-shrink: 0;" />
                            <div>
                                <strong>{{ $eng['name'] }}</strong><br>
                                <span style="font-size: 9px; color: #71717a;">{{ \Illuminate\Support\Str::limit($eng['headline'] ?? 'Proven engineer', 60) }}</span>
                            </div>
                        </div>
                        @if($blur) <span class="lock-badge">Locked — register to view</span> @endif
                    </td>
                    <td @if($blur) style="filter: blur(2.5px);" @endif>{{ $eng['location'] ?? '—' }}</td>
                    <td @if($blur) style="filter: blur(2.5px);" @endif>{{ implode(', ', array_slice($eng['skills'] ?? [], 0, 3)) ?: '—' }}</td>
                    <td style="text-align: center; font-size: 13px;">@if($eng['verified']) <span style="color: #059669;">✓</span> @else <span style="color: #dc2626;">✕</span> @endif</td>
                    <td style="text-align: center; font-weight: 700;" @if($blur) class="blurred" @endif>{{ $eng['reputation'] ?? 0 }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align: center; padding: 20px; color: #71717a;">No engineers match your filters.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 14px; padding: 10px; background: #f9fafb; border: 1px solid #e4e4e7; border-radius: 6px; font-size: 9px; color: #71717a; text-align: center;">
        ProoDev — proof over claims · proodev.com/for-companies · {{ $engineers->count() }} results · @if($isGuest) Preview with blurred details — register for full export @else Full export @endif
    </div>
</body>
</html>
