<?php

namespace App\Models;

use App\GiftCards\GiftCardRedeemer;
use App\Mileage\BookingMileageSync;
use Database\Factories\BookingFactory;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model implements Eventable
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'start_time',
        'duration_minutes',
        'service_id',
        'miles',
        'notes',
    ];

    protected static function booted(): void
    {
        static::forceDeleting(function (Booking $booking): void {
            app(BookingMileageSync::class)->forget($booking);
        });
        static::deleted(function (Booking $booking): void {
            if (! $booking->isForceDeleting()) {
                app(GiftCardRedeemer::class)->release($booking->id);
                app(BookingMileageSync::class)->sync($booking);
            }
        });
        static::restored(function (Booking $booking): void {
            app(GiftCardRedeemer::class)->syncBooking($booking->id);
            app(BookingMileageSync::class)->sync($booking);
        });
        static::saved(function (Booking $booking): void {
            app(BookingMileageSync::class)->sync($booking);

            if ($booking->wasChanged('service_id')) {
                app(GiftCardRedeemer::class)->syncBooking($booking->id);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'miles' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsToMany<Dog, $this>
     */
    public function dogs(): BelongsToMany
    {
        return $this->belongsToMany(Dog::class);
    }

    /**
     * @return HasMany<BookingClient, $this>
     */
    public function clientRows(): HasMany
    {
        return $this->hasMany(BookingClient::class);
    }

    public function toCalendarEvent(): CalendarEvent
    {
        $start = $this->date->copy()->setTimeFromTimeString($this->start_time);
        $dogs = $this->dogs->pluck('name')->implode(', ');

        return CalendarEvent::make($this)
            ->title(trim("{$this->service->name} {$dogs}"))
            ->start($start)
            ->end($start->copy()->addMinutes($this->duration_minutes ?? $this->service->default_duration_minutes ?? 60));
    }
}
