@php($card = $getRecord()->loadMissing('lines.service'))

@fonts
@include('gift-cards.card-styles')
@include('gift-cards.card', ['card' => $card])
