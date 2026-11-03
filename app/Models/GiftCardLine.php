<?php

namespace App\Models;

use Database\Factories\GiftCardLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftCardLine extends Model
{
    /** @use HasFactory<GiftCardLineFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'service_id',
        'quantity',
    ];

    /**
     * @return BelongsTo<GiftCard, $this>
     */
    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
