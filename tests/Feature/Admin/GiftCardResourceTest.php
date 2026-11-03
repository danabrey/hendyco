<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\GiftCards\Pages\CreateGiftCard;
use App\Filament\Resources\GiftCards\Pages\EditGiftCard;
use App\Filament\Resources\GiftCards\Pages\ListGiftCards;
use App\Filament\Resources\GiftCards\Pages\ViewGiftCard;
use App\Filament\Resources\GiftCards\RelationManagers\RedemptionsRelationManager;
use App\GiftCardType;
use App\Models\Booking;
use App\Models\BookingClient;
use App\Models\GiftCard;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GiftCardResourceTest extends TestCase
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

        $this->get('/admin/gift-cards')->assertRedirect('/admin/login');
        $this->get(route('filament.admin.gift-cards.print', GiftCard::factory()->create()))->assertRedirect('/admin/login');
    }

    public function test_user_can_create_a_money_card_with_a_generated_code(): void
    {
        Livewire::test(CreateGiftCard::class)
            ->fillForm([
                'type' => GiftCardType::Money,
                'value_pence' => 25,
                'price_paid_pence' => 25,
                'sold_at' => '2026-11-01',
                'purchaser_name' => 'Pat',
                'recipient_name' => 'Sam',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $card = GiftCard::firstWhere('purchaser_name', 'Pat');
        $this->assertSame(2500, $card->value_pence);
        $this->assertSame(2500, $card->price_paid_pence);
        $this->assertMatchesRegularExpression('/^HENDY-/', $card->code);
    }

    public function test_user_can_create_a_service_card_with_several_lines(): void
    {
        $walk = Service::factory()->create();
        $visit = Service::factory()->create();

        Livewire::test(CreateGiftCard::class)
            ->fillForm([
                'type' => GiftCardType::Service,
                'price_paid_pence' => 60,
                'sold_at' => '2026-11-01',
                'purchaser_name' => 'Pat',
                'lines' => [
                    ['service_id' => $walk->id, 'quantity' => 3],
                    ['service_id' => $visit->id, 'quantity' => 2],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $card = GiftCard::firstWhere('purchaser_name', 'Pat');
        $this->assertSame(GiftCardType::Service, $card->type);
        $this->assertSame([3, 2], $card->lines()->orderBy('id')->pluck('quantity')->all());
    }

    public function test_a_service_card_needs_at_least_one_line(): void
    {
        Livewire::test(CreateGiftCard::class)
            ->fillForm(['type' => GiftCardType::Service, 'price_paid_pence' => 10, 'sold_at' => '2026-11-01', 'purchaser_name' => 'Pat', 'lines' => []])
            ->call('create')
            ->assertHasFormErrors(['lines']);
    }

    public function test_list_shows_what_is_left_on_a_card(): void
    {
        $card = GiftCard::factory()->create(['value_pence' => 5000]);
        BookingClient::factory()->create(['price_pence' => 1500, 'gift_card_id' => $card->id]);

        Livewire::test(ListGiftCards::class)
            ->assertCanSeeTableRecords([$card])
            ->assertSee('£35.00 of £50.00')
            ->assertSee('Cash balance');
    }

    public function test_list_shows_status_and_can_filter_used_cards(): void
    {
        $used = GiftCard::factory()->create(['value_pence' => 1500]);
        BookingClient::factory()->create(['price_pence' => 1500, 'gift_card_id' => $used->id]);
        $available = GiftCard::factory()->create();
        $expired = GiftCard::factory()->create(['expires_at' => today()->subDay()]);

        Livewire::test(ListGiftCards::class)
            ->assertCanSeeTableRecords([$used, $available, $expired])
            ->filterTable('status', 'used')
            ->assertCanSeeTableRecords([$used])
            ->assertCanNotSeeTableRecords([$available, $expired])
            ->filterTable('status', 'available')
            ->assertCanSeeTableRecords([$available])
            ->assertCanNotSeeTableRecords([$used, $expired])
            ->filterTable('status', 'expired')
            ->assertCanSeeTableRecords([$expired])
            ->assertCanNotSeeTableRecords([$used, $available]);
    }

    public function test_card_page_lists_its_redemptions(): void
    {
        $card = GiftCard::factory()->create();
        $mine = BookingClient::factory()->create(['price_pence' => 1500, 'gift_card_id' => $card->id])->giftCardRedemption;

        Livewire::test(RedemptionsRelationManager::class, ['ownerRecord' => $card, 'pageClass' => EditGiftCard::class])
            ->assertCanSeeTableRecords([$mine]);
    }

    public function test_view_page_shows_the_card_details_and_a_preview_of_the_printed_card(): void
    {
        $card = GiftCard::factory()->create(['recipient_name' => 'Sam', 'purchaser_name' => 'Pat', 'value_pence' => 5000]);

        Livewire::test(ViewGiftCard::class, ['record' => $card->getKey()])
            ->assertSuccessful()
            ->assertSee($card->code)
            ->assertSee('Pat')
            ->assertSee('£50.00 of £50.00')
            ->assertSeeHtml('class="hendy-card"')
            ->assertSee('A gift for');
    }

    public function test_view_page_previews_service_cards_with_their_lines(): void
    {
        $card = GiftCard::factory()->service()->create();

        Livewire::test(ViewGiftCard::class, ['record' => $card->getKey()])
            ->assertSee('1 × '.$card->lines()->first()->service->name);
    }

    public function test_print_page_shows_the_card_to_a_logged_in_user(): void
    {
        $card = GiftCard::factory()->create(['recipient_name' => 'Sam']);

        $this->get(route('filament.admin.gift-cards.print', $card))
            ->assertOk()
            ->assertSee($card->code)
            ->assertSee('Sam')
            ->assertSee('£50.00');
    }

    public function test_booking_form_redeems_a_card_picked_on_a_client_row(): void
    {
        $card = GiftCard::factory()->create(['value_pence' => 5000]);
        $booking = Booking::factory()->create();
        $row = BookingClient::factory()->for($booking)->create(['price_pence' => 1500]);

        Livewire::test(EditBooking::class, ['record' => $booking->getKey()])
            ->fillForm(['clientRows' => ["record-{$row->id}" => ['client_id' => $row->client_id, 'price_pence' => 15, 'gift_card_id' => $card->id]]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(3500, $card->balancePence());
    }

    public function test_booking_form_rejects_a_card_that_cannot_be_used(): void
    {
        $card = GiftCard::factory()->service()->create();
        $booking = Booking::factory()->create();
        $row = BookingClient::factory()->for($booking)->create();

        Livewire::test(EditBooking::class, ['record' => $booking->getKey()])
            ->fillForm(['clientRows' => ["record-{$row->id}" => ['client_id' => $row->client_id, 'price_pence' => 15, 'gift_card_id' => $card->id]]])
            ->call('save')
            ->assertHasFormErrors();

        $this->assertDatabaseCount('gift_card_redemptions', 0);
    }

    public function test_choosing_gift_card_as_the_payment_method_requires_a_card(): void
    {
        $booking = Booking::factory()->create();
        $row = BookingClient::factory()->for($booking)->create();

        Livewire::test(EditBooking::class, ['record' => $booking->getKey()])
            ->fillForm(['clientRows' => ["record-{$row->id}" => ['client_id' => $row->client_id, 'price_pence' => 15, 'payment_method' => 'gift_card', 'gift_card_id' => null]]])
            ->call('save')
            ->assertHasFormErrors();
    }
}
