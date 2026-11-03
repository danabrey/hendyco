<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\MileageLogs\Pages\ManageMileageLogs;
use App\Models\Booking;
use App\Models\MileageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MileageLogResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        auth()->logout();

        $this->get('/admin/mileage-logs')->assertRedirect('/admin/login');
    }

    public function test_user_can_log_mileage_with_an_optional_booking(): void
    {
        $booking = Booking::factory()->create();

        Livewire::test(ManageMileageLogs::class)
            ->callAction('create', ['date' => '2026-11-02', 'miles' => 12.5, 'purpose' => 'Dog show', 'booking_id' => $booking->id])
            ->assertHasNoFormErrors();
        Livewire::test(ManageMileageLogs::class)
            ->callAction('create', ['date' => '2026-11-03', 'miles' => 3, 'purpose' => 'Leaflets'])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('mileage_logs', ['purpose' => 'Dog show', 'miles' => '12.50', 'booking_id' => $booking->id]);
        $this->assertDatabaseHas('mileage_logs', ['purpose' => 'Leaflets', 'booking_id' => null]);
    }

    public function test_mileage_requires_date_miles_and_purpose(): void
    {
        Livewire::test(ManageMileageLogs::class)
            ->callAction('create', ['date' => null, 'miles' => null, 'purpose' => null])
            ->assertHasFormErrors(['date' => 'required', 'miles' => 'required', 'purpose' => 'required']);
    }

    public function test_mileage_can_be_filtered_by_date_range(): void
    {
        $inRange = MileageLog::factory()->create(['date' => '2026-11-02']);
        $tooOld = MileageLog::factory()->create(['date' => '2026-09-01']);

        Livewire::test(ManageMileageLogs::class)
            ->filterTable('date_range', ['from' => '2026-10-01', 'until' => '2026-11-30'])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$tooOld]);
    }

    public function test_deleting_a_booking_keeps_its_mileage_log(): void
    {
        $booking = Booking::factory()->create();
        $log = MileageLog::factory()->create(['booking_id' => $booking->id]);

        $booking->forceDelete();

        $this->assertNull($log->fresh()->booking_id);
    }
}
