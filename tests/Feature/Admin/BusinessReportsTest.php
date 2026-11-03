<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\BusinessReports;
use App\Models\Booking;
use App\Models\BookingClient;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\GiftCard;
use App\Models\MileageLog;
use App\Models\Service;
use App\Models\User;
use App\PaymentMethod;
use App\Reports\TaxYear;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    protected function report(string $from, string $until): array
    {
        return Livewire::test(BusinessReports::class)
            ->set('from', $from)
            ->set('until', $until)
            ->instance()
            ->getReport();
    }

    protected function paidRow(Service $service, Client $client, int $pence, string $paidAt): BookingClient
    {
        return BookingClient::factory()
            ->for(Booking::factory()->create(['service_id' => $service->id, 'miles' => null]))
            ->create(['client_id' => $client->id, 'price_pence' => $pence, 'paid_at' => $paidAt, 'payment_method' => PaymentMethod::Cash]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        auth()->logout();

        $this->get('/admin/business-reports')->assertRedirect('/admin/login');
    }

    public function test_print_page_is_login_protected_and_shows_the_report(): void
    {
        $this->paidRow(Service::factory()->create(['name' => 'Walk']), Client::factory()->create(), 2000, '2026-10-05');
        $url = route('filament.admin.reports.business.print', ['from' => '2026-10-01', 'until' => '2026-10-31']);

        $this->get($url)->assertOk()->assertSee('Business report')->assertSee('Income by service')->assertSee('£20.00');

        auth()->logout();
        $this->get($url)->assertRedirect('/admin/login');
    }

    public function test_it_defaults_to_the_current_tax_year_to_date(): void
    {
        $this->travelTo('2026-11-10');

        Livewire::test(BusinessReports::class)
            ->assertSet('from', '2026-04-06')
            ->assertSet('until', '2026-11-10');
    }

    public function test_tax_year_labels_and_boundaries(): void
    {
        $this->assertSame('2025/26', TaxYear::labelFor(CarbonImmutable::parse('2026-04-05')));
        $this->assertSame('2026/27', TaxYear::labelFor(CarbonImmutable::parse('2026-04-06')));
        $this->assertSame('2026-04-06', TaxYear::startOf(CarbonImmutable::parse('2027-04-05'))->toDateString());
    }

    public function test_income_and_expenses_are_broken_down_and_add_up(): void
    {
        $walk = Service::factory()->create(['name' => 'Walk']);
        $visit = Service::factory()->create(['name' => 'Visit']);
        $ann = Client::factory()->create(['name' => 'Ann']);
        $bob = Client::factory()->create(['name' => 'Bob']);
        $this->paidRow($walk, $ann, 2000, '2026-10-05');
        $this->paidRow($walk, $bob, 1500, '2026-11-02');
        $this->paidRow($visit, $ann, 1000, '2026-11-03');
        BookingClient::factory()->create(['price_pence' => 9999, 'paid_at' => null]);
        GiftCard::factory()->create(['price_paid_pence' => 4000, 'sold_at' => '2026-11-04']);
        $supplies = ExpenseCategory::factory()->create(['name' => 'Supplies']);
        $insurance = ExpenseCategory::factory()->create(['name' => 'Insurance']);
        Expense::factory()->for($supplies, 'category')->create(['date' => '2026-10-10', 'amount_pence' => 500]);
        Expense::factory()->for($insurance, 'category')->create(['date' => '2026-11-10', 'amount_pence' => 1200]);
        Expense::factory()->for($supplies, 'category')->create(['date' => '2026-11-11', 'amount_pence' => 300]);

        $report = $this->report('2026-10-01', '2026-11-30');

        $this->assertSame(8500, $report['income']['total']);
        $this->assertSame([['label' => 'October 2026', 'amount_pence' => 2000], ['label' => 'November 2026', 'amount_pence' => 6500]], $report['income']['by_month']);
        $this->assertSame([['Gift card sales', 4000], ['Walk', 3500], ['Visit', 1000]], array_map(fn ($r) => [$r['label'], $r['amount_pence']], $report['income']['by_service']));
        $this->assertSame([['Gift card sales', 4000], ['Ann', 3000], ['Bob', 1500]], array_map(fn ($r) => [$r['label'], $r['amount_pence']], $report['income']['by_client']));
        $this->assertSame(2000, $report['expenses']['total']);
        $this->assertSame([['label' => 'October 2026', 'amount_pence' => 500], ['label' => 'November 2026', 'amount_pence' => 1500]], $report['expenses']['by_month']);
        $this->assertSame([['Insurance', 1200], ['Supplies', 800]], array_map(fn ($r) => [$r['label'], $r['amount_pence']], $report['expenses']['by_category']));
    }

    public function test_hmrc_mileage_uses_the_10000_mile_threshold_per_tax_year(): void
    {
        MileageLog::factory()->create(['date' => '2026-04-05', 'miles' => 20000]);
        MileageLog::factory()->create(['date' => '2026-04-06', 'miles' => 5000]);
        MileageLog::factory()->create(['date' => '2026-05-01', 'miles' => 6000]);

        $report = $this->report('2026-04-06', '2027-04-05');

        $this->assertSame(11000.0, (float) $report['mileage']['miles']);
        $this->assertSame(10000 * 55 + 1000 * 25, $report['mileage']['cost_pence']);
        $this->assertSame('2026/27', $report['mileage']['by_tax_year'][0]['label']);
    }

    public function test_a_range_starting_mid_tax_year_still_counts_earlier_miles_towards_the_threshold(): void
    {
        MileageLog::factory()->create(['date' => '2026-05-01', 'miles' => 10000]);
        MileageLog::factory()->create(['date' => '2026-09-01', 'miles' => 10]);

        $report = $this->report('2026-08-01', '2026-09-30');

        $this->assertSame(10 * 25, $report['mileage']['cost_pence']);
    }

    public function test_net_profit_is_calculated_per_tax_year_across_a_boundary(): void
    {
        $service = Service::factory()->create();
        $client = Client::factory()->create();
        $this->paidRow($service, $client, 10000, '2026-03-01');
        $this->paidRow($service, $client, 20000, '2026-05-01');
        Expense::factory()->create(['date' => '2026-03-02', 'amount_pence' => 1000]);
        Expense::factory()->create(['date' => '2026-05-02', 'amount_pence' => 2500]);
        MileageLog::factory()->create(['date' => '2026-05-03', 'miles' => 100]);

        $report = $this->report('2026-02-01', '2026-06-30');

        $this->assertSame([
            ['label' => '2025/26', 'income_pence' => 10000, 'expenses_pence' => 1000, 'mileage_pence' => 0, 'net_pence' => 9000],
            ['label' => '2026/27', 'income_pence' => 20000, 'expenses_pence' => 2500, 'mileage_pence' => 5500, 'net_pence' => 12000],
        ], $report['net']['by_tax_year']);
        $this->assertSame(21000, $report['net']['total']);
    }

    public function test_an_invalid_range_shows_a_message(): void
    {
        Livewire::test(BusinessReports::class)
            ->set('from', '2026-10-15')
            ->set('until', '2026-09-16')
            ->assertSee('must not be after');
    }
}
