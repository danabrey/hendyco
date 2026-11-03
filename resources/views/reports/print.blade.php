<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} – {{ \Illuminate\Support\Carbon::parse($from)->format('j M Y') }} to {{ \Illuminate\Support\Carbon::parse($until)->format('j M Y') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; color: #111; background: #fff; margin: 0; padding: 1.5rem; font-size: 14px; }
        .sheet { max-width: 56rem; margin: 0 auto; }
        h1 { margin: 0 0 0.25rem; font-size: 1.5rem; }
        .report { display: block; }
        .report > p { margin: 0 0 1rem; color: #444; }
        .report section { margin: 0 0 1.25rem; }
        .report h3 { margin: 0 0 0.35rem; font-size: 1.05rem; break-after: avoid; }
        .report thead { display: table-header-group; }
        .report tr { break-inside: avoid; }
        .report table { width: 100%; border-collapse: collapse; }
        .report th { text-align: left; font-weight: 600; border-bottom: 2px solid #111; }
        .report th, .report td { padding: 0.3rem 0.4rem; border-bottom: 1px solid #ccc; }
        .report .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .report tfoot td { font-weight: 700; border-top: 2px solid #111; border-bottom: 0; }
        .actions { margin-bottom: 1rem; }
        button { padding: 0.4rem 1rem; font-size: 1rem; }
        @media print {
            body { padding: 0; font-size: 12px; }
            .actions { display: none; }
            @page { margin: 15mm; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="actions"><button type="button" onclick="window.print()">Print</button></div>
        <h1>{{ $title }}</h1>
        @include($body, ['report' => $report, 'from' => $from, 'until' => $until])
    </div>
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
