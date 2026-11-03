<?php

namespace App\Filament\Resources\MileageLogs;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\MileageLogs\Pages\ManageMileageLogs;
use App\Filament\Tables\DateRangeFilter;
use App\Models\Booking;
use App\Models\MileageLog;
use App\NavigationGroup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MileageLogResource extends Resource
{
    protected static ?string $model = MileageLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Expenses;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'mileage log';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->required()
                    ->default(today()),
                TextInput::make('miles')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01),
                TextInput::make('purpose')
                    ->required()
                    ->maxLength(255),
                Select::make('booking_id')
                    ->label('Booking')
                    ->relationship('booking', 'date', fn (Builder $query) => $query->with('service')->latest('date'))
                    ->getOptionLabelFromRecordUsing(fn (Booking $record): string => $record->date->format('j M Y').' – '.$record->service->name)
                    ->searchable(false)
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('miles')
                    ->sortable(),
                TextColumn::make('purpose')
                    ->searchable(),
                TextColumn::make('source')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Booking' ? 'gray' : 'primary')
                    ->state(fn (MileageLog $record): string => $record->booking_mileage ? 'Booking' : 'Logged'),
                TextColumn::make('booking.date')
                    ->label('Booking')
                    ->date()
                    ->placeholder('-'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('source')
                    ->options(['logged' => 'Logged by hand', 'booking' => 'From bookings'])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'logged' => $query->where('booking_mileage', false),
                        'booking' => $query->where('booking_mileage', true),
                        default => $query,
                    }),
                DateRangeFilter::make(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('openBooking')
                    ->label('Booking')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->visible(fn (MileageLog $record): bool => $record->booking_mileage && $record->booking_id !== null)
                    ->url(fn (MileageLog $record): string => BookingResource::getUrl('view', ['record' => $record->booking_id])),
                ViewAction::make(),
                EditAction::make()
                    ->hidden(fn (MileageLog $record): bool => $record->booking_mileage),
                DeleteAction::make()
                    ->hidden(fn (MileageLog $record): bool => $record->booking_mileage),
                RestoreAction::make()
                    ->hidden(fn (MileageLog $record): bool => $record->booking_mileage),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Mileage that comes from a booking is changed on the booking, so it is read-only here.
     */
    public static function canEdit(Model $record): bool
    {
        return ! $record->booking_mileage;
    }

    public static function canDelete(Model $record): bool
    {
        return ! $record->booking_mileage;
    }

    public static function canRestore(Model $record): bool
    {
        return ! $record->booking_mileage;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMileageLogs::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
