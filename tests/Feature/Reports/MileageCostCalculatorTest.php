<?php

namespace Tests\Feature\Reports;

use App\Models\Booking;
use App\Models\MileageLog;
use App\Reports\MileageCostCalculator;
use App\Reports\UcAssessmentPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MileageCostCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function uc(string $from, string $until): array
    {
        $entries = MileageCostCalculator::uc()->entries(CarbonImmutable::parse($from), CarbonImmutable::parse($until));

        return ['miles' => $entries->sum('miles'), 'cost' => $entries->sum('cost_pence'), 'entries' => $entries];
    }

    public function test_uc_charges_45p_up_to_833_miles_then_25p(): void
    {
        MileageLog::factory()->create(['date' => '2026-10-20', 'miles' => 833]);
        MileageLog::factory()->create(['date' => '2026-10-25', 'miles' => 1]);

        $result = $this->uc('2026-10-16', '2026-11-15');

        $this->assertSame(833 * 45 + 25, $result['cost']);
    }

    public function test_an_entry_straddling_the_threshold_is_split(): void
    {
        MileageLog::factory()->create(['date' => '2026-10-20', 'miles' => 830]);
        MileageLog::factory()->create(['date' => '2026-10-25', 'miles' => 10]);

        $entries = $this->uc('2026-10-16', '2026-11-15')['entries'];

        $this->assertSame(3 * 45 + 7 * 25, $entries[1]['cost_pence']);
    }

    public function test_threshold_resets_on_the_16th(): void
    {
        MileageLog::factory()->create(['date' => '2026-11-15', 'miles' => 833]);
        MileageLog::factory()->create(['date' => '2026-11-16', 'miles' => 10]);

        $this->assertSame(10 * 45, $this->uc('2026-11-16', '2026-12-15')['cost']);
        $this->assertSame(833 * 45, $this->uc('2026-10-16', '2026-11-15')['cost']);
    }

    public function test_range_starting_mid_period_counts_earlier_miles_towards_the_threshold(): void
    {
        MileageLog::factory()->create(['date' => '2026-10-20', 'miles' => 833]);
        MileageLog::factory()->create(['date' => '2026-11-05', 'miles' => 10]);

        $result = $this->uc('2026-11-01', '2026-11-15');

        $this->assertSame(10, (int) $result['miles']);
        $this->assertSame(10 * 25, $result['cost']);
    }

    public function test_booking_and_log_miles_are_ordered_by_date_then_creation_and_soft_deleted_bookings_ignored(): void
    {
        Booking::factory()->create(['date' => '2026-10-20', 'miles' => 800]);
        Booking::factory()->create(['date' => '2026-10-21', 'miles' => 500])->delete();
        MileageLog::factory()->create(['date' => '2026-10-20', 'miles' => 40]);

        $entries = $this->uc('2026-10-16', '2026-11-15')['entries'];

        $this->assertCount(2, $entries);
        $this->assertSame(800 * 45, $entries[0]['cost_pence']);
        $this->assertSame(33 * 45 + 7 * 25, $entries[1]['cost_pence']);
    }

    public function test_hmrc_charges_55p_up_to_10000_miles_per_tax_year_then_25p(): void
    {
        MileageLog::factory()->create(['date' => '2026-04-05', 'miles' => 20000]);
        MileageLog::factory()->create(['date' => '2026-04-06', 'miles' => 9999]);
        MileageLog::factory()->create(['date' => '2026-12-01', 'miles' => 2]);

        $entries = MileageCostCalculator::hmrc()->entries(CarbonImmutable::parse('2026-04-06'), CarbonImmutable::parse('2027-04-05'));

        $this->assertSame(9999 * 55, $entries[0]['cost_pence']);
        $this->assertSame(55 + 25, $entries[1]['cost_pence']);
    }

    public function test_last_completed_uc_period(): void
    {
        $cases = [
            '2026-11-10' => ['2026-09-16', '2026-10-15'],
            '2026-11-16' => ['2026-10-16', '2026-11-15'],
            '2026-11-15' => ['2026-09-16', '2026-10-15'],
            '2027-01-05' => ['2026-11-16', '2026-12-15'],
            '2026-03-20' => ['2026-02-16', '2026-03-15'],
        ];

        foreach ($cases as $today => [$from, $until]) {
            [$start, $end] = UcAssessmentPeriod::lastCompleted(CarbonImmutable::parse($today));
            $this->assertSame([$from, $until], [$start->toDateString(), $end->toDateString()], $today);
        }
    }
}
