<?php

namespace App\Mileage;

use App\Models\Booking;
use App\Models\MileageLog;

class BookingMileageSync
{
    /**
     * Keep a booking's mileage log in step with the booking.
     *
     * A live booking with miles has exactly one read-only log (so Mileage Logs lists every
     * trip in one place); no miles, or a deleted booking, soft-deletes it. Restoring the
     * booking or adding miles again brings it back.
     */
    public function sync(Booking $booking): void
    {
        $log = MileageLog::withTrashed()
            ->where('booking_id', $booking->id)
            ->where('booking_mileage', true)
            ->first();

        if ($booking->trashed() || (float) $booking->miles <= 0) {
            $log?->delete();

            return;
        }

        $log ??= new MileageLog;

        if ($log->trashed()) {
            $log->restore();
        }

        $log->forceFill([
            'date' => $booking->date,
            'miles' => $booking->miles,
            'purpose' => 'Booking: '.$booking->service()->value('name'),
            'booking_id' => $booking->id,
            'booking_mileage' => true,
        ])->save();
    }

    /**
     * Remove a booking's mileage log for good when the booking is permanently deleted.
     */
    public function forget(Booking $booking): void
    {
        MileageLog::withTrashed()
            ->where('booking_id', $booking->id)
            ->where('booking_mileage', true)
            ->forceDelete();
    }
}
