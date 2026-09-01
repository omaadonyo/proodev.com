<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'ProoDev' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    @php
        // Inline data URI so the brand logo always renders - no remote-image
        // blocking and no dependency on the app domain being reachable.
        $logoDataUri = 'data:image/png;base64,'.base64_encode((string) file_get_contents(public_path('images/logo-black-400.png')));
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f4f5f7;
            color: #1f2430;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper { padding: 36px 16px; }
        .container {
            max-width: 620px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 32px rgba(15, 23, 42, 0.06);
        }
        /* Landscape container for long documents (e.g. candidate shortlists). */
        .container--wide { max-width: 1080px; }
        .container--wide .content { padding: 30px 34px; }
        .stats { display: flex; gap: 12px; margin: 0 0 22px; }
        .stat { flex: 1; border: 1px solid #eef0f3; background: #fafbfc; border-radius: 12px; padding: 14px 16px; text-align: center; }
        .stat-value { font-size: 18px; font-weight: 800; color: #111827; font-variant-numeric: tabular-nums; }
        .stat-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin-top: 2px; }
        table.items.landscape { font-size: 12px; }
        table.items.landscape th, table.items.landscape td { padding: 10px; }
        table.items.landscape th:last-child, table.items.landscape td:last-child { text-align: left; }
        table.items .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        table.items .candidate { min-width: 210px; vertical-align: middle; }
        table.cell { border-collapse: collapse; }
        td.avatar-td { padding: 0 !important; vertical-align: middle; }
        img.avatar { width: 42px; height: 42px; border-radius: 50%; display: block; object-fit: cover; border: 2px solid #eef0f3; background: #f3f4f6; }
        td.info-td { padding: 0 0 0 10px !important; vertical-align: middle; }
        .candidate-name { font-weight: 700; color: #111827; white-space: nowrap; }
        .candidate-headline { font-size: 11px; color: #6b7280; max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .badge.yes { background: #ecfdf5; color: #059669; }
        table.items .email { white-space: nowrap; }
        table.items .skills { color: #6b7280; }
        /* Accent hairline + light header (no dark band) */
        .accent-bar { height: 5px; background: linear-gradient(90deg, #4f46e5, #7c6cff 55%, #4f46e5); }
        .header {
            background: #ffffff;
            padding: 26px 40px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 1px solid #f1f2f4;
        }
        .brand { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; }
        .brand-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: linear-gradient(135deg, #4f46e5, #6d5ef2);
            color: #ffffff;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -0.02em;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);
        }
        .brand-name { color: #111827; font-size: 17px; font-weight: 800; letter-spacing: -0.02em; }
        .brand-name span { color: #4f46e5; }
        .doc-label {
            display: inline-block;
            color: #4f46e5;
            background: #eef2ff;
            border: 1px solid #e0e7ff;
            padding: 5px 12px;
            font-size: 10px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            font-weight: 700;
            border-radius: 999px;
            white-space: nowrap;
        }
        .content { padding: 34px 40px 30px; }
        .grid { display: flex; gap: 20px; justify-content: space-between; margin-bottom: 26px; }
        .grid .col { flex: 1; min-width: 0; }
        .label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; margin-bottom: 5px; }
        .value { font-size: 14px; color: #1f2430; }
        .value strong { font-weight: 700; }
        .muted { color: #6b7280; font-size: 13px; }
        h1 { font-size: 24px; font-weight: 800; margin-bottom: 8px; letter-spacing: -0.025em; color: #111827; }
        h2 { font-size: 16px; font-weight: 700; margin: 22px 0 10px; color: #111827; }
        p.lead { color: #4b5563; font-size: 14px; margin-bottom: 22px; }
        p + p { margin-top: 10px; }
        a { color: #4f46e5; text-decoration: none; }
        table.items { width: 100%; border-collapse: collapse; margin: 16px 0 22px; font-size: 13px; border-radius: 10px; overflow: hidden; }
        table.items th {
            text-align: left;
            padding: 10px 12px;
            background: #f9fafb;
            color: #6b7280;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 1px solid #eceef1;
        }
        table.items th:last-child, table.items td:last-child { text-align: right; }
        table.items td { padding: 12px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: top; }
        table.items tr:last-child td { border-bottom: none; }
        .totals { width: 100%; margin-bottom: 22px; }
        .totals td { padding: 6px 12px; font-size: 13px; color: #4b5563; }
        .totals td:last-child { text-align: right; font-weight: 600; color: #1f2430; }
        .totals tr.total td { border-top: 2px solid #111827; padding-top: 12px; font-size: 16px; font-weight: 800; color: #111827; }
        /* Buttons match the website: 0.45rem 1.25rem padding, 0.35rem radius,
           black fill with white text (emails are always light). */
        .btn {
            display: inline-block;
            background: #111827;
            color: #ffffff !important;
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
        }
        .btn-row { text-align: center; margin: 26px 0; }
        .btn-ghost {
            display: inline-block;
            border: 1px solid #d4d4d8;
            background: #ffffff;
            color: #111827 !important;
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge.paid { background: #ecfdf5; color: #059669; }
        .badge.pending { background: #fffbeb; color: #b45309; }
        .card {
            border: 1px solid #eef0f3;
            background: #fafbfc;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 12px;
        }
        .card-title { font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 4px; }
        .card .muted { font-size: 12px; }
        .footer {
            padding: 20px 40px 24px;
            border-top: 1px solid #f1f2f4;
            background: #fafbfc;
            font-size: 12px;
            color: #9ca3af;
            text-align: center;
        }
        .footer .brand-line { color: #6b7280; font-weight: 700; margin-bottom: 6px; }
        .footer a { color: #4f46e5; text-decoration: none; }
        .divider { height: 1px; background: #eceef1; margin: 22px 0; }
        .divider--soft { height: 1px; background: #f1f2f4; margin: 18px 0; }
        .steps { display: flex; gap: 12px; margin: 20px 0 8px; }
        .steps .step { flex: 1; border: 1px solid #eef0f3; border-radius: 12px; padding: 14px; }
        .steps .step-num { font-size: 11px; font-weight: 800; color: #4f46e5; letter-spacing: 0.06em; }
        .steps .step-title { font-size: 13px; font-weight: 700; color: #111827; margin-top: 6px; }
        .steps .step p { font-size: 12px; color: #6b7280; margin-top: 4px; }
        @media (max-width: 480px) {
            .grid { flex-direction: column; gap: 14px; }
            .steps { flex-direction: column; }
            .content, .header, .footer { padding-left: 22px; padding-right: 22px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container {{ ($wide ?? false) ? 'container--wide' : '' }}">
            <div class="accent-bar"></div>
            <div class="header">
                <span class="brand">
                    <img src="{{ $logoDataUri }}" alt="ProoDev" style="height: 32px; width: auto; display: block;" />
                </span>
                @if (! empty($docLabel))
                    <span class="doc-label">{{ $docLabel }}</span>
                @endif
            </div>

            <div class="content">
                {{ $slot }}
            </div>

            <div class="footer">
                <div class="brand-line">ProoDev | <a href="mailto:info@proodev.com">info@proodev.com</a></div>
                <div style="margin-top: 10px; font-size: 11px;">
                    Proof over claims. Every engineer on ProoDev is backed by analyzed evidence.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
