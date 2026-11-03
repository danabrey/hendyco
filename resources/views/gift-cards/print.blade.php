<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Gift card {{ $card->code }}</title>
    @fonts
    @include('gift-cards.card-styles')
    <style>
        body { margin: 0; padding: 2rem 1rem; background: #fff; font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        .actions { text-align: center; margin-top: 1.5rem; }
        button { padding: 0.5rem 1.25rem; font-size: 1rem; cursor: pointer; }
        @media print { body { padding: 0; } .actions { display: none; } @page { margin: 15mm; } }
    </style>
</head>
<body>
    @include('gift-cards.card', ['card' => $card])

    <div class="actions"><button type="button" onclick="window.print()">Print</button></div>
</body>
</html>
