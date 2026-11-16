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
                    ->afterStateUpdated(function (Set $set, Get $get, $state, $old): void {
                        static::repriceRowsForService($set, $get, $old, $state);
                        static::applyDefaultDuration($set, $get, $old, $state);
                    }),
                Select::make('dogs')
                    ->relationship('dogs', 'name', fn (Builder $query) => $query->with('client'))
                    ->live()
                    ->afterStateHydrated(fn (Set $set, ?array $state) => $set('priced_dog_ids', $state ?? []))
                    ->afterStateUpdated(fn (Set $set, Get $get) => static::syncRowsWithDogs($set, $get))
                    ->getOptionLabelFromRecordUsing(fn (Dog $record): string => "{$record->name} ({$record->client->name})")
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),
                Hidden::make('priced_dog_ids')
                    ->dehydrated(false),
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
                    ->schema([
                        Select::make('client_id')
                            ->relationship('client', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get, mixed $state, mixed $old) => static::repriceRowForClient($set, $get, $old, $state)),
                        PenceInput::make('price_pence')
                            ->label('Price')
                            ->required()
                            ->default(fn (Get $get): int => static::suggestedPricePence($get('../../service_id'), $get('../../dogs') ?? [], $get('client_id'))),
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
     * The price a client is charged before any hand edits: the service's default price,
     * plus its extra dog price for each of that client's dogs on the booking beyond the first.
     *
     * @param  array<int, int|string>  $dogIds
     */
    protected static function suggestedPricePence(mixed $serviceId, array $dogIds, mixed $clientId): int
    {
        if (! $serviceId) {
            return 0;
        }

        $service = Service::find($serviceId, ['default_price_pence', 'extra_dog_price_pence']);

        if (! $service) {
            return 0;
        }

        $clientDogCount = $clientId && $dogIds ? Dog::whereIn('id', $dogIds)->where('client_id', $clientId)->count() : 0;

        return $service->default_price_pence + $service->extra_dog_price_pence * max(0, $clientDogCount - 1);
    }

    /**
     * When the dogs change, re-price rows still at their old suggested price, then add a
     * price row for every client whose dog is on the booking and who has no row yet.
     *
     * The dogs the rows were last priced for are kept in `priced_dog_ids`, because the select's
     * own old state is not reliable when its array is updated one element at a time.
     */
    protected static function syncRowsWithDogs(Set $set, Get $get): void
    {
        $serviceId = $get('service_id');
        $dogIds = $get('dogs') ?? [];
        $pricedDogIds = $get('priced_dog_ids') ?? [];

        static::repriceUnchangedRows(
            $set,
            $get,
            fn (mixed $clientId): int => static::suggestedPricePence($serviceId, $pricedDogIds, $clientId),
            fn (mixed $clientId): int => static::suggestedPricePence($serviceId, $dogIds, $clientId),
        );

        static::addRowsForDogClients($set, $get, $serviceId, $dogIds);

        $set('priced_dog_ids', $dogIds);
    }

    /**
     * Add a price row for every client whose dog is on the booking and who has no row yet.
     *
     * @param  array<int, int|string>  $dogIds
     */
    protected static function addRowsForDogClients(Set $set, Get $get, mixed $serviceId, array $dogIds): void
    {
        $rows = $get('clientRows') ?? [];
        $haveClientIds = collect($rows)->pluck('client_id')->filter();

        Dog::whereIn('id', $dogIds)
            ->pluck('client_id')
            ->unique()
            ->diff($haveClientIds)
            ->each(function (int $clientId) use (&$rows, $serviceId, $dogIds): void {
                $price = static::suggestedPricePence($serviceId, $dogIds, $clientId) / 100;
                $rows[(string) Str::uuid()] = ['client_id' => $clientId, 'price_pence' => $price, 'paid_at' => null, 'payment_method' => null];
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

    /**
     * When the service changes, move rows still at the old service's suggested price to the new one's.
     */
    protected static function repriceRowsForService(Set $set, Get $get, mixed $oldServiceId, mixed $newServiceId): void
    {
        $dogIds = $get('dogs') ?? [];

        static::repriceUnchangedRows(
            $set,
            $get,
            fn (mixed $clientId): int => static::suggestedPricePence($oldServiceId, $dogIds, $clientId),
            fn (mixed $clientId): int => static::suggestedPricePence($newServiceId, $dogIds, $clientId),
        );
    }

    /**
     * When a row's client changes, move its price from the old client's suggested price to the new one's.
     */
    protected static function repriceRowForClient(Set $set, Get $get, mixed $oldClientId, mixed $newClientId): void
    {
        $serviceId = $get('../../service_id');
        $dogIds = $get('../../dogs') ?? [];
        $price = $get('price_pence');

        if (is_numeric($price) && static::poundsToPence($price) === static::suggestedPricePence($serviceId, $dogIds, $oldClientId)) {
            $set('price_pence', static::suggestedPricePence($serviceId, $dogIds, $newClientId) / 100);
        }
    }

    protected static function poundsToPence(mixed $pounds): int
    {
        return (int) round(((float) $pounds) * 100);
    }

    /**
     * Move every row whose price still equals its old suggested price to its new suggested price,
     * leaving prices typed in by hand alone.
     *
     * @param  Closure(mixed): int  $oldSuggestedPence
     * @param  Closure(mixed): int  $newSuggestedPence
     */
    protected static function repriceUnchangedRows(Set $set, Get $get, Closure $oldSuggestedPence, Closure $newSuggestedPence): void
    {
        foreach ($get('clientRows') ?? [] as $key => $row) {
            $clientId = $row['client_id'] ?? null;
            $price = $row['price_pence'] ?? null;

            if (is_numeric($price) && static::poundsToPence($price) === $oldSuggestedPence($clientId)) {
                $set("clientRows.{$key}.price_pence", $newSuggestedPence($clientId) / 100);
            }
        }
    }
}
