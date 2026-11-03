<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\UcReport;
use App\Models\Booking;
use App\Models\BookingClient;
use App\Models\Expense;
use App\Models\GiftCard;
use App\Models\MileageLog;
use App\Models\User;
use App\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UcReportTest extends TestCase
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

        $this->get('/admin/uc-report')->assertRedirect('/admin/login');
    }

    public function test_it_defaults_to_the_last_completed_assessment_period(): void
    {
        $this->travelTo('2026-11-10');

        Livewire::test(UcReport::class)
            ->assertSet('from', '2026-09-16')
            ->assertSet('until', '2026-10-15');
    }

    public function test_it_totals_income_by_paid_date_expenses_and_mileage(): void
    {
        $booking = Booking::factory()->create(['date' => '2026-09-01', 'miles' => 10]);
        $paid = BookingClient::factory()->for($booking)->create(['price_pence' => 2000, 'paid_at' => '2026-10-01', 'payment_method' => PaymentMethod::Cash]);
        BookingClient::factory()->for($booking)->create(['price_pence' => 950, 'paid_at' => null]);
        BookingClient::factory()->for($booking)->create(['price_pence' => 800, 'paid_at' => '2026-10-02', 'payment_method' => PaymentMethod::GiftCard]);
        BookingClient::factory()->for(Booking::factory()->create(['miles' => null]))->create(['price_pence' => 700, 'paid_at' => '2026-08-01']);
        $deleted = Booking::factory()->create();
        BookingClient::factory()->for($deleted)->create(['price_pence' => 650, 'paid_at' => '2026-10-03']);
        $deleted->delete();
        Expense::factory()->create(['date' => '2026-10-05', 'amount_pence' => 500]);
        Expense::factory()->create(['date' => '2026-11-05', 'amount_pence' => 9999]);
        MileageLog::factory()->create(['date' => '2026-10-10', 'miles' => 20]);

        $report = Livewire::test(UcReport::class)
            ->set('from', '2026-09-16')
            ->set('until', '2026-10-15')
            ->assertSee('£20.00')
            ->assertDontSee('£9.50')
            ->assertDontSee('£8.00')
            ->assertDontSee('£7.00')
            ->assertDontSee('£6.50')
            ->assertDontSee('£99.99')
            ->instance()
            ->getReport();

        $this->assertSame(2000, $report['income_pence']);
        $this->assertSame(500, $report['expenses_pence']);
        $this->assertSame(20 * 45, $report['mileage_pence']);
        $this->assertSame(500 + 900, $report['expenditure_pence']);
        $this->assertSame(2000 - 1400, $report['net_pence']);
        $this->assertTrue($paid->exists);
    }

    public function test_rows_dated_on_the_first_and_last_day_of_the_range_are_included(): void
    {
        BookingClient::factory()->create(['price_pence' => 1111, 'paid_at' => '2026-09-16']);
        BookingClient::factory()->create(['price_pence' => 2222, 'paid_at' => '2026-10-15']);
        Expense::factory()->create(['date' => '2026-10-15', 'amount_pence' => 333]);

        $report = Livewire::test(UcReport::class)
            ->set('from', '2026-09-16')
            ->set('until', '2026-10-15')
            ->instance()
            ->getReport();

        $this->assertSame(3333, $report['income_pence']);
        $this->assertSame(333, $report['expenses_pence']);
    }

    public function test_income_from_a_deleted_client_still_appears(): void
    {
        $row = BookingClient::factory()->create(['price_pence' => 1500, 'paid_at' => '2026-10-01']);
        $row->client->delete();

        Livewire::test(UcReport::class)
            ->set('from', '2026-09-16')
            ->set('until', '2026-10-15')
            ->assertSee($row->client->name)
            ->assertSee('£15.00');
    }

    public function test_a_malformed_date_shows_the_range_message_instead_of_failing(): void
    {
        Livewire::test(UcReport::class)
            ->set('from', 'not-a-date')
            ->assertSee('must not be after');
    }

    public function test_gift_card_sales_count_on_the_sale_date_and_card_covered_amounts_do_not(): void
    {
        GiftCard::factory()->create(['price_paid_pence' => 4000, 'value_pence' => 4000, 'sold_at' => '2026-10-01']);
        GiftCard::factory()->create(['price_paid_pence' => 9999, 'sold_at' => '2026-11-01']);
        $card = GiftCard::factory()->create(['price_paid_pence' => 0, 'value_pence' => 1000, 'sold_at' => '2026-01-01']);
        BookingClient::factory()->create(['price_pence' => 1500, 'gift_card_id' => $card->id, 'paid_at' => '2026-10-05', 'payment_method' => PaymentMethod::Cash]);

        $report = Livewire::test(UcReport::class)
            ->set('from', '2026-09-16')
            ->set('until', '2026-10-15')
            ->instance()
            ->getReport();

        $this->assertSame(4000 + 500, $report['income_pence']);
    }

    public function test_an_invalid_range_shows_a_message_instead_of_a_report(): void
    {
        Livewire::test(UcReport::class)
            ->set('from', '2026-10-15')
            ->set('until', '2026-09-16')
            ->assertSee('must not be after');
    }
}
