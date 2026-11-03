<?php

namespace App\Models;

use Database\Factories\GiftCardRedemptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftCardRedemption extends Model
{
    /** @use HasFactory<GiftCardRedemptionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'gift_card_id',
        'service_id',
        'amount_pence',
    ];

    /**
     * @return BelongsTo<GiftCard, $this>
     */
    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class)->withTrashed();
    }

    /**
     * @return BelongsTo<BookingClient, $this>
     */
    public function bookingClient(): BelongsTo
    {
        return $this->belongsTo(BookingClient::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
