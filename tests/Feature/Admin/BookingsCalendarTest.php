<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\BookingsCalendar;
use App\Filament\Widgets\BookingsCalendarWidget;
use App\Models\Booking;
use App\Models\Dog;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingsCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/bookings-calendar')->assertRedirect('/admin/login');
    }

    public function test_calendar_page_renders_for_a_user(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(BookingsCalendar::class)->assertOk();
        $this->get('/admin/bookings-calendar')->assertOk();
    }

    public function test_bookings_are_shown_as_events_with_service_and_dog_names(): void
    {
        $this->actingAs(User::factory()->create());
        $booking = Booking::factory()
            ->for(Service::factory()->state(['name' => 'Solo walk']))
            ->hasAttached(Dog::factory()->state(['name' => 'Biscuit']))
            ->create(['date' => '2026-11-10', 'start_time' => '09:30', 'duration_minutes' => 45]);

        $event = $booking->load(['service', 'dogs'])->toCalendarEvent();

        $this->assertSame('Solo walk Biscuit', (string) $event->getTitle());
        $this->assertSame('2026-11-10 09:30', $event->getStart()->format('Y-m-d H:i'));
        $this->assertSame('2026-11-10 10:15', $event->getEnd()->format('Y-m-d H:i'));
    }

    public function test_calendar_events_fall_back_to_the_services_default_duration(): void
    {
        $this->actingAs(User::factory()->create());
        $booking = Booking::factory()
            ->for(Service::factory()->state(['default_duration_minutes' => 90]))
            ->create(['date' => '2026-11-10', 'start_time' => '09:00', 'duration_minutes' => null]);

        $event = $booking->load(['service', 'dogs'])->toCalendarEvent();

        $this->assertSame('2026-11-10 10:30', $event->getEnd()->format('Y-m-d H:i'));
    }

    public function test_clicking_an_empty_day_opens_a_new_booking_for_that_date(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(BookingsCalendarWidget::class)
            ->call('onDateClickJs', ['date' => '2026-11-12T00:00:00.000Z', 'allDay' => true, 'view' => ['type' => 'dayGridMonth', 'title' => '', 'activeStart' => '2026-11-01T00:00:00.000Z', 'activeEnd' => '2026-12-01T00:00:00.000Z', 'currentStart' => '2026-11-01T00:00:00.000Z', 'currentEnd' => '2026-12-01T00:00:00.000Z'], 'tzOffset' => 0])
            ->assertRedirect('/admin/bookings/create?date=2026-11-12');
    }
}
