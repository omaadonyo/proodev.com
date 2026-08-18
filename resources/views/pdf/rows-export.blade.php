<?php
$seller = config('billing.seller');
$count = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('pdf._styles')
</head>
<body>
    <table class="header"><tr>
        <td style="vertical-align: middle;">
            <img src="{{ public_path('images/logo-black-400.png') }}" alt="ProoDev" class="brand-logo" />
            <div class="seller">{{ str_replace(['https://', 'http://'], '', $seller['website']) }} | {{ $seller['name'] }}<br>{{ $seller['address'] }}, {{ $seller['city'] }} - {{ $seller['country'] }}<br>Tel: {{ $seller['phone'] }} | {{ $seller['email'] }} | Tax ID {{ $seller['tax_id'] }}</div>
        </td>
        <td style="vertical-align: middle;">
            <div class="doc-label">Export</div>
            <div class="doc-title">{{ $title }}</div>
            <div class="doc-sub">{{ $count }} row{{ $count !== 1 ? 's' : '' }} · Generated {{ now()->format('M j, Y g:i A') }}</div>
        </td>
    </tr></table>

    @if ($count === 0)
        <div class="empty">No rows selected.</div>
    @else
        <table class="items">
            <thead>
                <tr>
                    @foreach ($headings as $heading)
                        <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $index => $row)
                    <tr @class(['alt' => $index % 2 === 1])>
                        @foreach ($headings as $i => $heading)
                            <td>{{ $row[$i] ?? '' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="page-footer">
        <span class="brand-line">{{ $seller['name'] }}</span> · {{ $seller['email'] }} · {{ $seller['phone'] }}<br>
        <span class="page-num"></span>
    </div>
</body>
</html>
