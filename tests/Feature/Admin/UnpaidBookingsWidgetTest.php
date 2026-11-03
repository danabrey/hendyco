<?php

namespace Tests\Feature\Admin;

use App\Filament\Widgets\UnpaidBookingsWidget;
use App\Models\Booking;
use App\Models\BookingClient;
use App\Models\GiftCard;
use App\Models\User;
use App\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnpaidBookingsWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_it_lists_unpaid_rows_oldest_first_with_days_outstanding(): void
    {
        $this->travelTo('2026-11-10');
        $newer = BookingClient::factory()->for(Booking::factory()->create(['date' => '2026-11-05']))->create(['price_pence' => 1500]);
        $older = BookingClient::factory()->for(Booking::factory()->create(['date' => '2026-10-01']))->create(['price_pence' => 2000]);

        Livewire::test(UnpaidBookingsWidget::class)
            ->assertCanSeeTableRecords([$older, $newer], inOrder: true)
            ->assertSee('40')
            ->assertSee('£20.00');
    }

    public function test_it_excludes_paid_card_covered_and_deleted_booking_rows(): void
    {
        $paid = BookingClient::factory()->paid()->create();
        $covered = BookingClient::factory()->create(['price_pence' => 1500, 'gift_card_id' => GiftCard::factory()->create()->id]);
        $deletedBooking = Booking::factory()->create();
        $onDeleted = BookingClient::factory()->for($deletedBooking)->create();
        $deletedBooking->delete();
        $unpaid = BookingClient::factory()->create();

        $this->assertSame(PaymentMethod::GiftCard, $covered->fresh()->payment_method);

        Livewire::test(UnpaidBookingsWidget::class)
            ->assertCanSeeTableRecords([$unpaid])
            ->assertCanNotSeeTableRecords([$paid, $covered, $onDeleted]);
    }

    public function test_a_partly_card_covered_row_shows_only_the_remainder(): void
    {
        $card = GiftCard::factory()->create(['value_pence' => 1000]);
        $row = BookingClient::factory()->create(['price_pence' => 1500, 'gift_card_id' => $card->id]);

        Livewire::test(UnpaidBookingsWidget::class)
            ->assertCanSeeTableRecords([$row])
            ->assertSee('£5.00');
    }

    public function test_it_appears_on_the_dashboard(): void
    {
        $this->get('/admin')->assertOk()->assertSeeLivewire(UnpaidBookingsWidget::class);
    }
}
