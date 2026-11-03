<?php

namespace App\Filament\Resources\MileageLogs;

use App\Filament\Resources\MileageLogs\Pages\ManageMileageLogs;
use App\Filament\Tables\DateRangeFilter;
use App\Models\Booking;
use App\Models\MileageLog;
use App\NavigationGroup;
use BackedEnum;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                TextColumn::make('booking.date')
                    ->label('Booking')
                    ->date()
                    ->placeholder('-'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                DateRangeFilter::make(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
