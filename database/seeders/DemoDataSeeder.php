<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingClient;
use App\Models\Client;
use App\Models\Dog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MileageLog;
use App\Models\Service;
use App\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed about four months of realistic data so the reports have something to show.
     */
    public function run(): void
    {
        $this->call([ServiceSeeder::class, ExpenseCategorySeeder::class]);

        $services = collect([
            ['Group walk', 1200],
            ['Solo walk', 1800],
            ['Drop-in visit', 1000],
        ])->map(fn (array $service): Service => Service::firstOrCreate(['name' => $service[0]], ['default_price_pence' => $service[1]]));

        $dogs = Client::factory(14)->create()->flatMap(
            fn (Client $client) => Dog::factory(fake()->numberBetween(1, 2))->for($client)->create()
        );

        for ($daysAgo = 120; $daysAgo >= -14; $daysAgo--) {
            $date = today()->subDays($daysAgo);

            if ($date->isSunday()) {
                continue;
            }

            foreach (range(1, fake()->numberBetween(0, 3)) as $ignored) {
                $this->createBooking($date, $services->random(), $dogs->random(fake()->numberBetween(1, 3)));
            }
        }

        $this->createExpenses();
        $this->createMileageLogs();
    }

    /**
     * @param  Collection<int, Dog>  $dogs
     */
    protected function createBooking($date, Service $service, $dogs): void
    {
        $booking = Booking::factory()->create([
            'date' => $date->toDateString(),
            'service_id' => $service->id,
            'miles' => fake()->randomElement([null, 3.5, 6, 8.2, 12, 15.5]),
            'start_time' => fake()->randomElement(['09:00', '10:30', '12:00', '14:30', '16:00']),
        ]);
        $booking->dogs()->attach($dogs->pluck('id'));

        $dogs->pluck('client_id')->unique()->each(function (int $clientId) use ($booking, $service, $date): void {
            $isPast = $date->isPast();
            $paid = $isPast && fake()->boolean(80);
            $method = fake()->randomElement([PaymentMethod::Cash, PaymentMethod::BankTransfer, PaymentMethod::BankTransfer, PaymentMethod::GiftCard]);

            BookingClient::factory()->for($booking)->create([
                'client_id' => $clientId,
                'price_pence' => $service->default_price_pence,
                'paid_at' => $paid && $method !== PaymentMethod::GiftCard ? $date->copy()->addDays(fake()->numberBetween(0, 5))->min(today())->toDateString() : null,
                'payment_method' => $paid ? $method : null,
            ]);
        });
    }

    protected function createExpenses(): void
    {
        $categories = ExpenseCategory::all();

        foreach (range(1, 25) as $ignored) {
            Expense::factory()->for($categories->random(), 'category')->create([
                'date' => today()->subDays(fake()->numberBetween(0, 120))->toDateString(),
                'description' => fake()->randomElement(['Poo bags', 'Treats', 'Lead replacement', 'Van insurance', 'Flyers', 'First aid course', 'Towels', 'Fuel']),
                'amount_pence' => fake()->numberBetween(350, 6500),
            ]);
        }
    }

    protected function createMileageLogs(): void
    {
        foreach (range(1, 15) as $ignored) {
            MileageLog::factory()->create([
                'date' => today()->subDays(fake()->numberBetween(0, 120))->toDateString(),
                'miles' => fake()->randomFloat(1, 5, 90),
                'purpose' => fake()->randomElement(['Dog show', 'Delivering flyers', 'Supplies run', 'Training venue']),
            ]);
        }
    }
}
