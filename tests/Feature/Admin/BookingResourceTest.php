<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Clients\Pages\ViewClient;
use App\Filament\Resources\Clients\RelationManagers\BookingRowsRelationManager;
use App\Models\Booking;
use App\Models\BookingClient;
use App\Models\Client;
use App\Models\Dog;
use App\Models\Service;
use App\Models\User;
use App\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingResourceTest extends TestCase
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

        $this->get('/admin/bookings')->assertRedirect('/admin/login');
    }

    public function test_user_can_create_a_group_booking_with_a_price_row_per_client(): void
    {
        $service = Service::factory()->create(['default_price_pence' => 1500]);
        $dogA = Dog::factory()->create();
        $dogB = Dog::factory()->create();

        Livewire::test(CreateBooking::class)
            ->fillForm([
                'date' => '2026-11-10',
                'start_time' => '09:30',
                'duration_minutes' => 60,
                'service_id' => $service->id,
                'dogs' => [$dogA->id, $dogB->id],
                'miles' => 3.5,
                'notes' => 'Gate code 1234',
                'clientRows' => [
                    ['client_id' => $dogA->client_id, 'price_pence' => 15, 'paid_at' => '2026-11-10', 'payment_method' => 'cash'],
                    ['client_id' => $dogB->client_id, 'price_pence' => 12.5],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $booking = Booking::sole();
        $this->assertSame('2026-11-10', $booking->date->toDateString());
        $this->assertEqualsCanonicalizing([$dogA->id, $dogB->id], $booking->dogs->modelKeys());
        $this->assertSame('3.50', $booking->miles);
        $this->assertCount(2, $booking->clientRows);

        $this->assertDatabaseHas('booking_clients', [
            'booking_id' => $booking->id,
            'client_id' => $dogA->client_id,
            'price_pence' => 1500,
            'payment_method' => 'cash',
        ]);
        $this->assertDatabaseHas('booking_clients', [
            'booking_id' => $booking->id,
            'client_id' => $dogB->client_id,
            'price_pence' => 1250,
            'paid_at' => null,
            'payment_method' => null,
        ]);
    }

    public function test_new_client_row_price_defaults_to_the_service_price(): void
    {
        $service = Service::factory()->create(['default_price_pence' => 2000]);
        $client = Client::factory()->create();

        Livewire::test(CreateBooking::class)
            ->fillForm(['service_id' => $service->id])
            ->callFormComponentAction('clientRows', 'add')
            ->assertFormSet(fn (array $state) => $this->assertEquals(20, collect($state['clientRows'])->first()['price_pence']));
    }

    public function test_selecting_dogs_adds_one_price_row_per_client_at_the_service_price(): void
    {
        $service = Service::factory()->create(['default_price_pence' => 1800]);
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $dogs = [Dog::factory()->for($client)->create(), Dog::factory()->for($client)->create(), Dog::factory()->for($otherClient)->create()];

        Livewire::test(CreateBooking::class)
            ->fillForm(['service_id' => $service->id])
            ->fillForm(['dogs' => collect($dogs)->pluck('id')->all()])
            ->assertFormSet(function (array $state) use ($client, $otherClient) {
                $rows = collect($state['clientRows']);
                $this->assertEqualsCanonicalizing([$client->id, $otherClient->id], $rows->pluck('client_id')->all());
                $this->assertEquals([18, 18], $rows->pluck('price_pence')->all());
            });
    }

    public function test_changing_the_service_reprices_rows_still_at_the_old_default(): void
    {
        $old = Service::factory()->create(['default_price_pence' => 1000]);
        $new = Service::factory()->create(['default_price_pence' => 2500]);
        $dog = Dog::factory()->create();

        Livewire::test(CreateBooking::class)
            ->fillForm(['service_id' => $old->id])
            ->fillForm(['dogs' => [$dog->id]])
            ->fillForm(['service_id' => $new->id])
            ->assertFormSet(fn (array $state) => $this->assertEquals(25, collect($state['clientRows'])->first()['price_pence']));
    }

    public function test_create_page_ignores_a_malformed_date_in_the_query_string(): void
    {
        Livewire::withQueryParams(['date' => 'not-a-date'])
            ->test(CreateBooking::class)
            ->assertFormSet(['date' => today()->toDateString()]);
    }

    public function test_create_page_prefills_the_date_from_the_query_string(): void
    {
        Livewire::withQueryParams(['date' => '2026-12-25'])
            ->test(CreateBooking::class)
            ->assertFormSet(['date' => '2026-12-25']);
    }

    public function test_booking_requires_date_time_and_service(): void
    {
        Livewire::test(CreateBooking::class)
            ->fillForm(['date' => null, 'start_time' => null, 'service_id' => null])
            ->call('create')
            ->assertHasFormErrors(['date' => 'required', 'start_time' => 'required', 'service_id' => 'required']);
    }

    public function test_user_can_edit_a_client_row(): void
    {
        $booking = Booking::factory()->create();
        $row = BookingClient::factory()->for($booking)->create(['price_pence' => 1500]);

        Livewire::test(EditBooking::class, ['record' => $booking->getKey()])
            ->fillForm(['clientRows' => ["record-{$row->id}" => ['client_id' => $row->client_id, 'price_pence' => 10, 'paid_at' => '2026-11-11', 'payment_method' => 'bank_transfer']]])
            ->call('save')
            ->assertHasNoFormErrors();

        $row->refresh();
        $this->assertSame(1000, $row->price_pence);
        $this->assertSame(PaymentMethod::BankTransfer, $row->payment_method);
    }

    public function test_deleting_a_booking_soft_deletes_it(): void
    {
        $booking = Booking::factory()->create();

        Livewire::test(EditBooking::class, ['record' => $booking->getKey()])
            ->callAction('delete');

        $this->assertSoftDeleted($booking);
    }

    public function test_unpaid_filter_lists_only_bookings_with_an_unpaid_client_row(): void
    {
        $unpaid = Booking::factory()->has(BookingClient::factory(), 'clientRows')->create();
        $paid = Booking::factory()->has(BookingClient::factory()->paid(), 'clientRows')->create();
        $cardCovered = Booking::factory()->has(BookingClient::factory()->state(['payment_method' => PaymentMethod::GiftCard]), 'clientRows')->create();

        Livewire::test(ListBookings::class)
            ->assertCanSeeTableRecords([$unpaid, $paid, $cardCovered])
            ->filterTable('unpaid')
            ->assertCanSeeTableRecords([$unpaid])
            ->assertCanNotSeeTableRecords([$paid, $cardCovered]);
    }

    public function test_client_page_lists_that_clients_bookings(): void
    {
        $client = Client::factory()->create();
        $mine = BookingClient::factory()->for($client)->create();
        $other = BookingClient::factory()->create();

        Livewire::test(BookingRowsRelationManager::class, [
            'ownerRecord' => $client,
            'pageClass' => ViewClient::class,
        ])
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);
    }
}
