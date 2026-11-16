<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'default_price_pence',
        'extra_dog_price_pence',
        'default_duration_minutes',
    ];

    /**
     * The price for one client bringing this many dogs: the default price,
     * plus the extra dog price for each dog beyond the first.
     */
    public function priceForDogCount(int $dogCount): int
    {
        return $this->default_price_pence + $this->extra_dog_price_pence * max(0, $dogCount - 1);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
