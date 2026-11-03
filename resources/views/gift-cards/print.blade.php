<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Gift card {{ $card->code }}</title>
    <style>
        body { font-family: Georgia, serif; margin: 0; padding: 2rem; color: #222; }
        .card { max-width: 32rem; margin: 0 auto; border: 3px double #444; border-radius: 1rem; padding: 2rem; text-align: center; }
        h1 { margin: 0 0 0.25rem; font-size: 1.75rem; }
        .for { font-size: 1.1rem; margin: 1rem 0; }
        .value { font-size: 2.25rem; font-weight: bold; margin: 1rem 0; }
        .code { font-family: monospace; font-size: 1.6rem; letter-spacing: 0.1em; border: 1px dashed #444; padding: 0.5rem; margin: 1.25rem 0; }
        .small { font-size: 0.9rem; color: #555; }
        ul { list-style: none; padding: 0; margin: 1rem 0; font-size: 1.2rem; }
        button { margin: 1.5rem auto 0; display: block; padding: 0.5rem 1.25rem; font-size: 1rem; }
        @media print { button { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="card">
        <h1>Hendy Co Gift Card</h1>

        @if ($card->recipient_name)
            <p class="for">For {{ $card->recipient_name }}</p>
        @endif

        @if ($card->type === \App\GiftCardType::Money)
            <p class="value">£{{ number_format($card->value_pence / 100, 2) }}</p>
        @else
            <ul>
                @foreach ($card->lines as $line)
                    <li>{{ $line->quantity }} × {{ $line->service->name }}</li>
                @endforeach
            </ul>
        @endif

        <p class="code">{{ $card->code }}</p>

        @if ($card->purchaser_name)
            <p class="small">From {{ $card->purchaser_name }}</p>
        @endif
        @if ($card->expires_at)
            <p class="small">Valid until {{ $card->expires_at->format('j F Y') }}</p>
        @endif
    </div>

    <button type="button" onclick="window.print()">Print</button>
</body>
</html>
