<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Filament\Forms\PenceInput;
use App\Models\BookingClient;
use App\Models\Dog;
use App\Models\GiftCard;
use App\Models\Service;
use App\PaymentMethod;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->required()
                    ->default(fn (): string => static::dateFromQuery()),
                TimePicker::make('start_time')
                    ->required()
                    ->seconds(false),
                TextInput::make('duration_minutes')
                    ->label('Duration (minutes)')
                    ->numeric()
                    ->minValue(1),
                Select::make('service_id')
                    ->relationship('service', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, mixed $state, mixed $old): void {
                        static::refreshPrices($get, $set);
                        static::applyDefaultDuration($set, $get, $old, $state);
                    }),
                Select::make('dogs')
                    ->relationship('dogs', 'name', fn (Builder $query) => $query->with('client'))
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set): void {
                        static::addRowsForDogClients($get, $set);
                        static::refreshPrices($get, $set);
                    })
                    ->getOptionLabelFromRecordUsing(fn (Dog $record): string => "{$record->name} ({$record->client->name})")
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),
                TextInput::make('miles')
                    ->label('Mileage')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Repeater::make('clientRows')
                    ->label('Clients')
                    ->relationship()
                    ->columnSpanFull()
                    ->columns(4)
                    ->defaultItems(0)
                    ->addActionLabel('Add client')
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::refreshPrices($get, $set))
                    ->schema([
                        Select::make('client_id')
                            ->relationship('client', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->live(),
                        PenceInput::make('price_pence')
                            ->label('Price')
                            ->required(),
                        DatePicker::make('paid_at')
                            ->label('Paid on'),
                        Select::make('payment_method')
                            ->options(PaymentMethod::class)
                            ->live()
                            ->afterStateUpdated(function (PaymentMethod|string|null $state, Get $get, Set $set): void {
                                if ($state !== null && blank($get('paid_at'))) {
                                    $set('paid_at', today()->toDateString());
                                }
                            }),
                        Select::make('gift_card_id')
                            ->label('Gift card')
                            ->options(fn (Get $get, ?BookingClient $record): array => static::redeemableGiftCardOptions($get('../../service_id'), $record))
                            ->searchable()
                            ->required(fn (Get $get): bool => static::isGiftCardMethod($get('payment_method')))
                            ->rule(fn (Get $get, ?BookingClient $record): Closure => static::giftCardRule($get, $record)),
                    ]),
                Hidden::make('suggested_prices')
                    ->dehydrated(false)
                    ->afterStateHydrated(fn (Get $get, Set $set) => $set('suggested_prices', ClientRowPrices::suggestionsFor($get('clientRows') ?? [], static::suggestedPrices($get)))),
            ]);
    }

    protected static function isGiftCardMethod(mixed $method): bool
    {
        return ($method instanceof PaymentMethod ? $method : PaymentMethod::tryFrom((string) $method)) === PaymentMethod::GiftCard;
    }

    /**
     * Cards that can still be used for a booking of the given service, by label.
     *
     * @return array<int, string>
     */
    protected static function redeemableGiftCardOptions(mixed $serviceId, ?BookingClient $record): array
    {
        if (! $serviceId) {
            return [];
        }

        return GiftCard::query()
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', today()))
            ->orWhere('id', $record?->gift_card_id)
            ->get()
            ->filter(fn (GiftCard $card): bool => $card->id === $record?->gift_card_id || $card->isRedeemableFor((int) $serviceId, $record?->id))
            ->mapWithKeys(fn (GiftCard $card): array => [$card->id => $card->code.' – '.$card->remainingSummary()])
            ->all();
    }

    protected static function giftCardRule(Get $get, ?BookingClient $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
            $card = $value ? GiftCard::find($value) : null;
            $serviceId = (int) $get('../../service_id');

            $alreadyRedeemed = $card && $record?->gift_card_id === $card->id && $record->giftCardRedemption !== null;

            if ($card && ! $alreadyRedeemed && ! $card->isRedeemableFor($serviceId, $record?->id)) {
                $fail('This gift card cannot be used for this service, or has nothing left.');
            }
        };
    }

    protected static function dateFromQuery(): string
    {
        try {
            return Carbon::parse(request()->query('date', 'today'))->toDateString();
        } catch (InvalidFormatException) {
            return today()->toDateString();
        }
    }

    /**
     * The price each client would be charged for the booking's current service and dogs.
     */
    protected static function suggestedPrices(Get $get): SuggestedPrices
    {
        return SuggestedPrices::for($get('service_id'), $get('dogs') ?? []);
    }

    /**
     * Give every client row its suggested price, leaving prices typed in by hand alone.
     */
    protected static function refreshPrices(Get $get, Set $set): void
    {
        $refreshed = ClientRowPrices::refresh($get('clientRows') ?? [], $get('suggested_prices') ?? [], static::suggestedPrices($get));

        $set('clientRows', $refreshed['rows']);
        $set('suggested_prices', $refreshed['suggested']);
    }

    /**
     * Add an unpriced row for every client whose dog is on the booking and who has no row yet.
     */
    protected static function addRowsForDogClients(Get $get, Set $set): void
    {
        $rows = $get('clientRows') ?? [];
        $haveClientIds = collect($rows)->pluck('client_id')->filter();

        collect(static::suggestedPrices($get)->clientIds())
            ->diff($haveClientIds)
            ->each(function (int $clientId) use (&$rows): void {
                $rows[(string) Str::uuid()] = ['client_id' => $clientId, 'price_pence' => null, 'paid_at' => null, 'payment_method' => null];
            });

        $set('clientRows', $rows);
    }

    /**
     * When the service changes, fill in its default duration unless the duration was typed in by hand.
     */
    protected static function applyDefaultDuration(Set $set, Get $get, mixed $oldServiceId, mixed $newServiceId): void
    {
        $current = $get('duration_minutes');
        $oldDefault = $oldServiceId ? Service::whereKey($oldServiceId)->value('default_duration_minutes') : null;
        $newDefault = $newServiceId ? Service::whereKey($newServiceId)->value('default_duration_minutes') : null;

        if (blank($current) || (int) $current === (int) $oldDefault) {
            $set('duration_minutes', $newDefault);
        }
    }
}
