<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Filament\Resources\Clients\ClientResource;
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

    public function test_two_dogs_from_one_client_add_the_extra_dog_price_to_their_row(): void
    {
        $walk = Service::factory()->create(['default_price_pence' => 1650, 'extra_dog_price_pence' => 300]);
        $client = Client::factory()->create();
        $dogs = Dog::factory()->for($client)->count(2)->create();

        Livewire::test(CreateBooking::class)
            ->fillForm(['service_id' => $walk->id])
            ->fillForm(['dogs' => $dogs->modelKeys()])
            ->assertFormSet(function (array $state) use ($client) {
                $row = collect($state['clientRows'])->sole();
                $this->assertSame($client->id, $row['client_id']);
                $this->assertEquals(19.5, $row['price_pence']);
            });
    }

    public function test_dogs_from_different_clients_are_each_charged_the_base_price(): void
    {
        $walk = Service::factory()->create(['default_price_pence' => 1650, 'extra_dog_price_pence' => 300]);
        $dogs = Dog::factory()->count(2)->create();

        Livewire::test(CreateBooking::class)
            ->fillForm(['service_id' => $walk->id])
            ->fillForm(['dogs' => $dogs->modelKeys()])
            ->assertFormSet(fn (array $state) => $this->assertEquals([16.5, 16.5], collect($state['clientRows'])->pluck('price_pence')->all()));
    }

    public function test_three_dogs_from_one_client_add_the_extra_dog_price_twice(): void
    {
        $walk = Service::factory()->create(['default_price_pence' => 1650, 'extra_dog_price_pence' => 300]);
        $dogs = Dog::factory()->for(Client::factory())->count(3)->create();

        Livewire::test(CreateBooking::class)
            ->fillForm(['service_id' => $walk->id])
            ->fillForm(['dogs' => $dogs->modelKeys()])
            ->assertFormSet(fn (array $state) => $this->assertEquals(22.5, collect($state['clientRows'])->sole()['price_pence']));
    }

    public function test_removing_one_of_a_clients_two_dogs_drops_their_row_back_to_the_base_price(): void
    {
        $walk = Service::factory()->create(['default_price_pence' => 1650, 'extra_dog_price_pence' => 300]);
        [$rex, $fido] = Dog::factory()->for(Client::factory())->count(2)->create();

        Livewire::test(CreateBooking::class)
            ->fillForm(['service_id' => $walk->id])
            ->fillForm(['dogs' => [$rex->id, $fido->id]])
            ->set('data.dogs', [$rex->id])
            ->assertFormSet(fn (array $state) => $this->assertEquals(16.5, collect($state['clientRows'])->sole()['price_pence']));
    }

    public function test_a_client_row_added_by_hand_is_priced_for_that_clients_dogs_on_the_booking(): void
    {
        $walk = Service::factory()->create(['default_price_pence' => 1650, 'extra_dog_price_pence' => 300]);
        $client = Client::factory()->create();
        $dogs = Dog::factory()->for($client)->count(2)->create();

        $component = Livewire::test(CreateBooking::class)
            ->fillForm(['service_id' => $walk->id])
            ->fillForm(['dogs' => $dogs->modelKeys()])
            ->set('data.clientRows', [])
            ->callFormComponentAction('clientRows', 'add');

        $rowKey = array_key_first($component->get('data.clientRows'));

        $component
            ->assertSet("data.clientRows.{$rowKey}.price_pence", 16.5)
            ->set("data.clientRows.{$rowKey}.client_id", $client->id)
            ->assertSet("data.clientRows.{$rowKey}.price_pence", 19.5);
    }

    public function test_removing_a_dog_on_an_existing_booking_drops_the_unedited_price(): void
    {
        $walk = Service::factory()->create(['default_price_pence' => 1650, 'extra_dog_price_pence' => 300]);
        $client = Client::factory()->create();
        [$rex, $fido] = Dog::factory()->for($client)->count(2)->create();
        $booking = Booking::factory()->for($walk)->create();
        $booking->dogs()->attach([$rex->id, $fido->id]);
        BookingClient::factory()->for($booking)->for($client)->create(['price_pence' => 1950]);

        Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()])
            ->set('data.dogs', [$rex->id])
            ->assertFormSet(fn (array $state) => $this->assertEquals(16.5, collect($state['clientRows'])->sole()['price_pence']));
    }

    public function test_a_hand_typed_price_is_kept_when_dogs_or_service_change(): void
    {
        $walk = Service::factory()->create(['default_price_pence' => 1650, 'extra_dog_price_pence' => 300]);
        $visit = Service::factory()->create(['default_price_pence' => 1200, 'extra_dog_price_pence' => 300]);
        [$rex, $fido] = Dog::factory()->for(Client::factory())->count(2)->create();

        $component = Livewire::test(CreateBooking::class)
            ->fillForm(['service_id' => $walk->id])
            ->fillForm(['dogs' => [$rex->id]]);

        $rowKey = array_key_first($component->get('data.clientRows'));

        $component
            ->set("data.clientRows.{$rowKey}.price_pence", 10)
            ->fillForm(['dogs' => [$rex->id, $fido->id]])
            ->assertSet("data.clientRows.{$rowKey}.price_pence", 10)
            ->fillForm(['service_id' => $visit->id])
            ->assertSet("data.clientRows.{$rowKey}.price_pence", 10);
    }

    public function test_changing_the_service_reprices_unedited_rows_with_the_current_dog_count(): void
    {
        $walk = Service::factory()->create(['default_price_pence' => 1650, 'extra_dog_price_pence' => 300]);
        $visit = Service::factory()->create(['default_price_pence' => 1200, 'extra_dog_price_pence' => 250]);
        $dogs = Dog::factory()->for(Client::factory())->count(2)->create();

        Livewire::test(CreateBooking::class)
            ->fillForm(['service_id' => $walk->id])
            ->fillForm(['dogs' => $dogs->modelKeys()])
            ->fillForm(['service_id' => $visit->id])
            ->assertFormSet(fn (array $state) => $this->assertEquals(14.5, collect($state['clientRows'])->sole()['price_pence']));
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

    public function test_choosing_a_service_prefills_its_default_duration_without_overwriting_a_typed_one(): void
    {
        $walk = Service::factory()->create(['default_duration_minutes' => 60]);
        $visit = Service::factory()->create(['default_duration_minutes' => 20]);
        $none = Service::factory()->create(['default_duration_minutes' => null]);

        Livewire::test(CreateBooking::class)
            ->fillForm(['service_id' => $walk->id])
            ->assertFormSet(['duration_minutes' => 60])
            ->fillForm(['service_id' => $visit->id])
            ->assertFormSet(['duration_minutes' => 20])
            ->fillForm(['duration_minutes' => 45])
            ->fillForm(['service_id' => $walk->id])
            ->assertFormSet(['duration_minutes' => 45])
            ->fillForm(['duration_minutes' => 20, 'service_id' => $visit->id])
            ->fillForm(['service_id' => $none->id])
            ->assertFormSet(['duration_minutes' => null]);
    }

    public function test_booking_view_links_each_client_to_their_page(): void
    {
        $booking = Booking::factory()->create();
        $row = BookingClient::factory()->for($booking)->create();

        Livewire::test(ViewBooking::class, ['record' => $booking->getKey()])
            ->assertSeeHtml('href="'.ClientResource::getUrl('view', ['record' => $row->client]).'"');
    }

    public function test_booking_form_labels_the_miles_field_mileage(): void
    {
        Livewire::test(CreateBooking::class)->assertSee('Mileage');
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

    public function test_user_can_view_a_booking_with_its_client_rows(): void
    {
        $booking = Booking::factory()->create();
        $row = BookingClient::factory()->for($booking)->create(['price_pence' => 1500]);

        Livewire::test(ViewBooking::class, ['record' => $booking->getKey()])
            ->assertSuccessful()
            ->assertSee($row->client->name)
            ->assertSee('£15.00')
            ->assertSee('Unpaid');
    }

    public function test_view_page_shows_gift_card_rows_as_paid(): void
    {
        $booking = Booking::factory()->create();
        BookingClient::factory()->for($booking)->state(['payment_method' => PaymentMethod::GiftCard])->create();

        Livewire::test(ViewBooking::class, ['record' => $booking->getKey()])
            ->assertSee('Paid by gift card')
            ->assertDontSee('Unpaid');
    }

    public function test_choosing_a_payment_method_defaults_paid_on_to_today(): void
    {
        $booking = Booking::factory()->create();
        $row = BookingClient::factory()->for($booking)->create();

        Livewire::test(EditBooking::class, ['record' => $booking->getKey()])
            ->set("data.clientRows.record-{$row->id}.payment_method", 'gift_card')
            ->assertSet("data.clientRows.record-{$row->id}.paid_at", today()->toDateString());
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

    public function test_free_rows_are_not_unpaid_and_say_no_charge(): void
    {
        $free = Booking::factory()->has(BookingClient::factory()->state(['price_pence' => 0]), 'clientRows')->create();
        $owing = Booking::factory()->has(BookingClient::factory()->state(['price_pence' => 1000]), 'clientRows')->create();

        $this->assertSame(0, BookingClient::unpaid()->where('booking_id', $free->id)->count());
        $this->assertSame(1, BookingClient::unpaid()->where('booking_id', $owing->id)->count());

        Livewire::test(ListBookings::class)
            ->filterTable('unpaid')
            ->assertCanSeeTableRecords([$owing])
            ->assertCanNotSeeTableRecords([$free]);

        Livewire::test(ViewBooking::class, ['record' => $free->getKey()])
            ->assertSee('No charge')
            ->assertDontSee('Unpaid');
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
