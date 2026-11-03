<div class="hendy-card">
    <img src="{{ asset('images/logo-mark.png') }}" alt="" class="mark">
    <p class="brand">Hendy &amp; Co.</p>
    <p class="tagline">Pet care for every personality</p>

    <p class="label">A gift for</p>
    <p class="for">{{ $card->recipient_name ?: 'someone special' }}</p>

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

    <p class="contact">07895 805449 &middot; hendycopetcare@gmail.com &middot; Pendeen &amp; surrounding areas</p>
</div>
