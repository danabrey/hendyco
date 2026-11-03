<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\MileageLog;
use App\Models\Service;
use App\Reports\MileageCostCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingMileageSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function logFor(Booking $booking): ?MileageLog
    {
        return MileageLog::where('booking_id', $booking->id)->where('booking_mileage', true)->first();
    }

    public function test_a_booking_with_miles_gets_one_read_only_log_describing_it(): void
    {
        $service = Service::factory()->create(['name' => 'Group walk']);
        $booking = Booking::factory()->create(['service_id' => $service->id, 'date' => '2026-11-02', 'miles' => 12.5]);

        $log = $this->logFor($booking);
        $this->assertSame('2026-11-02', $log->date->toDateString());
        $this->assertSame('12.50', $log->miles);
        $this->assertSame('Booking: Group walk', $log->purpose);
        $this->assertSame(1, MileageLog::where('booking_id', $booking->id)->count());
    }

    public function test_editing_the_booking_updates_the_same_log_and_removing_miles_removes_it(): void
    {
        $booking = Booking::factory()->create(['date' => '2026-11-02', 'miles' => 10]);
        $logId = $this->logFor($booking)->id;

        $booking->update(['date' => '2026-11-05', 'miles' => 20, 'service_id' => Service::factory()->create(['name' => 'Solo walk'])->id]);

        $log = $this->logFor($booking);
        $this->assertSame($logId, $log->id);
        $this->assertSame('2026-11-05', $log->date->toDateString());
        $this->assertSame('20.00', $log->miles);
        $this->assertSame('Booking: Solo walk', $log->purpose);

        $booking->update(['miles' => null]);
        $this->assertNull($this->logFor($booking));

        $booking->update(['miles' => 8]);
        $this->assertSame($logId, $this->logFor($booking)->id);
    }

    public function test_deleting_a_booking_removes_its_log_restoring_brings_it_back_and_force_delete_removes_it_for_good(): void
    {
        $booking = Booking::factory()->create(['miles' => 10]);

        $booking->delete();
        $this->assertNull($this->logFor($booking));
        $this->assertSame(1, MileageLog::onlyTrashed()->where('booking_id', $booking->id)->count());

        $booking->restore();
        $this->assertNotNull($this->logFor($booking));

        $booking->forceDelete();
        $this->assertSame(0, MileageLog::withTrashed()->where('booking_mileage', true)->count());
    }

    public function test_hand_logged_mileage_linked_to_a_booking_is_left_alone(): void
    {
        $booking = Booking::factory()->create(['miles' => null]);
        $manual = MileageLog::factory()->create(['booking_id' => $booking->id]);

        $booking->update(['miles' => 5]);
        $booking->forceDelete();

        $this->assertNotNull($manual->fresh());
        $this->assertNull($manual->fresh()->booking_id);
    }

    public function test_booking_miles_are_counted_once_by_the_mileage_calculator(): void
    {
        Booking::factory()->create(['date' => '2026-10-20', 'miles' => 100]);
        MileageLog::factory()->create(['date' => '2026-10-21', 'miles' => 50]);

        $entries = MileageCostCalculator::uc()->entries(CarbonImmutable::parse('2026-10-16'), CarbonImmutable::parse('2026-11-15'));

        $this->assertSame(150.0, (float) $entries->sum('miles'));
        $this->assertSame(150 * 45, $entries->sum('cost_pence'));
    }
}
