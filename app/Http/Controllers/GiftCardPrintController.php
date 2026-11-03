<?php

namespace App\Http\Controllers;

use App\Models\GiftCard;
use Illuminate\Contracts\View\View;

class GiftCardPrintController extends Controller
{
    /**
     * A print-friendly gift card for a logged-in user to hand over.
     */
    public function __invoke(GiftCard $giftCard): View
    {
        return view('gift-cards.print', [
            'card' => $giftCard->load('lines.service'),
        ]);
    }
}
