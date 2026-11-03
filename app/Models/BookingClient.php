<?php

namespace App\Models;

use App\GiftCards\GiftCardRedeemer;
use App\PaymentMethod;
use Database\Factories\BookingClientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BookingClient extends Model
{
    /** @use HasFactory<BookingClientFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'price_pence',
        'paid_at',
        'payment_method',
        'gift_card_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'paid_at' => 'date',
            'payment_method' => PaymentMethod::class,
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (BookingClient $row): void {
            app(GiftCardRedeemer::class)->sync($row);
        });
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<GiftCard, $this>
     */
    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class)->withTrashed();
    }

    /**
     * @return HasOne<GiftCardRedemption, $this>
     */
    public function giftCardRedemption(): HasOne
    {
        return $this->hasOne(GiftCardRedemption::class);
    }

    /**
     * What this row contributes to income once it has been paid. The part covered by a
     * gift card counts as £0 because the card sale was the income; only the remainder,
     * paid by another method, is income.
     */
    public function incomePence(): int
    {
        if ($this->isCoveredByGiftCard()) {
            return 0;
        }

        return max(0, $this->price_pence - (int) $this->giftCardRedemption?->amount_pence);
    }

    public function isCoveredByGiftCard(): bool
    {
        return $this->payment_method === PaymentMethod::GiftCard;
    }

    /**
     * @param  Builder<BookingClient>  $query
     */
    public function scopeUnpaid(Builder $query): void
    {
        $query->whereNull('paid_at')
            ->where(fn (Builder $query) => $query
                ->whereNull('payment_method')
                ->orWhere('payment_method', '!=', PaymentMethod::GiftCard));
    }
}
