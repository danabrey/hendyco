<?php

namespace Tests\Feature\Admin;

use App\Filament\Widgets\BusinessStatsWidget;
use App\Filament\Widgets\UpcomingBookingsWidget;
use App\Models\Booking;
use App\Models\BookingClient;
use App\Models\Dog;
use App\Models\Expense;
use App\Models\MileageLog;
use App\Models\User;
use App\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->travelTo('2026-11-10 09:00');
    }

    protected function row(int $pence, ?string $paidAt, string $date = '2026-09-01'): BookingClient
    {
        return BookingClient::factory()
            ->for(Booking::factory()->create(['date' => $date, 'miles' => null]))
            ->create(['price_pence' => $pence, 'paid_at' => $paidAt, 'payment_method' => $paidAt ? PaymentMethod::Cash : null]);
    }

    public function test_stats_summarise_the_month_what_is_owed_and_the_uc_period_net(): void
    {
        $this->row(2000, '2026-11-02');
        $this->row(1500, '2026-10-05');
        $this->row(1200, null);
        Expense::factory()->create(['date' => '2026-11-03', 'amount_pence' => 500]);
        Expense::factory()->create(['date' => '2026-10-03', 'amount_pence' => 9999]);
        MileageLog::factory()->create(['date' => '2026-11-01', 'miles' => 100]);
        Expense::factory()->create(['date' => '2026-10-20', 'amount_pence' => 300]);

        Livewire::test(BusinessStatsWidget::class)
            ->assertSee('£20.00')
            ->assertSee('Last month: £15.00')
            ->assertSee('£12.00')
            ->assertSee('1 unpaid booking')
            ->assertSee('£5.00')
            ->assertSee('Net this month: £15.00')
            ->assertSee('Net income this UC period')
            ->assertSee('-£33.00')
            ->assertSee('Income £20.00 − costs £53.00')
            ->assertSee('ends 15 Nov')
            ->assertDontSee('£99.99');
    }

    public function test_owed_stat_ignores_free_rows(): void
    {
        $this->row(0, null);
        $this->row(1200, null);

        Livewire::test(BusinessStatsWidget::class)
            ->assertSee('1 unpaid booking')
            ->assertDontSee('2 unpaid');
    }

    public function test_upcoming_bookings_shows_the_next_seven_days_in_order_with_dogs(): void
    {
        $today = Booking::factory()->create(['date' => '2026-11-10', 'start_time' => '14:00']);
        $today->dogs()->attach(Dog::factory()->create(['name' => 'Biscuit']));
        $tomorrow = Booking::factory()->create(['date' => '2026-11-11', 'start_time' => '09:00']);
        $inAWeek = Booking::factory()->create(['date' => '2026-11-17']);
        $tooFar = Booking::factory()->create(['date' => '2026-11-18']);
        $past = Booking::factory()->create(['date' => '2026-11-09']);
        $deleted = Booking::factory()->create(['date' => '2026-11-12']);
        $deleted->delete();

        Livewire::test(UpcomingBookingsWidget::class)
            ->assertCanSeeTableRecords([$today, $tomorrow, $inAWeek], inOrder: true)
            ->assertCanNotSeeTableRecords([$tooFar, $past, $deleted])
            ->assertSee('Today')
            ->assertSee('Tomorrow')
            ->assertSee('Biscuit');
    }

    public function test_dashboard_shows_the_new_widgets_and_not_the_filament_defaults(): void
    {
        $this->get('/admin')->assertOk()
            ->assertSeeLivewire(BusinessStatsWidget::class)
            ->assertSeeLivewire(UpcomingBookingsWidget::class)
            ->assertDontSee('Documentation');
    }
}
