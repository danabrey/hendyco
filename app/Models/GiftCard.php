<?php

namespace App\Models;

use App\GiftCardType;
use Database\Factories\GiftCardFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GiftCard extends Model
{
    /** @use HasFactory<GiftCardFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Letters and digits that are hard to misread when a code is handwritten or printed.
     */
    protected const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'value_pence',
        'price_paid_pence',
        'sold_at',
        'expires_at',
        'purchaser_name',
        'recipient_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => GiftCardType::class,
            'sold_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GiftCard $card): void {
            $card->code ??= static::generateCode();
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = 'HENDY-'.static::randomGroup().'-'.static::randomGroup();
        } while (static::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    protected static function randomGroup(): string
    {
        return collect(range(1, 4))
            ->map(fn (): string => Str::substr(static::CODE_ALPHABET, random_int(0, strlen(static::CODE_ALPHABET) - 1), 1))
            ->implode('');
    }

    /**
     * @return HasMany<GiftCardLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(GiftCardLine::class);
    }

    /**
     * @return HasMany<GiftCardRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(GiftCardRedemption::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lt(today());
    }

    /**
     * What is left on a money card, ignoring the redemption held by the given client row.
     */
    public function balancePence(?int $exceptBookingClientId = null): int
    {
        return max(0, (int) $this->value_pence - (int) $this->redemptions()
            ->when($exceptBookingClientId, fn (Builder $query) => $query->where('booking_client_id', '!=', $exceptBookingClientId))
            ->sum('amount_pence'));
    }

    /**
     * How many uses of a service are left on a service card, ignoring the redemption held by the given client row.
     */
    public function remainingQuantityFor(int $serviceId, ?int $exceptBookingClientId = null): int
    {
        $included = (int) $this->lines()->where('service_id', $serviceId)->sum('quantity');
        $used = $this->redemptions()
            ->where('service_id', $serviceId)
            ->when($exceptBookingClientId, fn (Builder $query) => $query->where('booking_client_id', '!=', $exceptBookingClientId))
            ->count();

        return max(0, $included - $used);
    }

    /**
     * How much of a price this card would cover for a booking of the given service.
     * Service cards cover the whole price of an included service; money cards cover up to their balance.
     */
    public function coverageFor(int $serviceId, int $pricePence, ?int $exceptBookingClientId = null): int
    {
        if ($this->trashed() || $this->isExpired()) {
            return 0;
        }

        return match ($this->type) {
            GiftCardType::Money => min($this->balancePence($exceptBookingClientId), $pricePence),
            GiftCardType::Service => $this->remainingQuantityFor($serviceId, $exceptBookingClientId) > 0 ? $pricePence : 0,
        };
    }

    public function isRedeemableFor(int $serviceId, ?int $exceptBookingClientId = null): bool
    {
        return match ($this->type) {
            GiftCardType::Money => ! $this->trashed() && ! $this->isExpired() && $this->balancePence($exceptBookingClientId) > 0,
            GiftCardType::Service => ! $this->trashed() && ! $this->isExpired() && $this->remainingQuantityFor($serviceId, $exceptBookingClientId) > 0,
        };
    }

    /**
     * A short description of what is left on the card.
     */
    public function remainingSummary(): string
    {
        if ($this->type === GiftCardType::Money) {
            return '£'.number_format($this->balancePence() / 100, 2).' of £'.number_format($this->value_pence / 100, 2);
        }

        return $this->lines()->with('service')->get()
            ->map(fn (GiftCardLine $line): string => $this->remainingQuantityFor($line->service_id).' of '.$line->quantity.' × '.$line->service->name)
            ->implode(', ');
    }
}
