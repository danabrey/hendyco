<?php

namespace App\Filament\Resources\MileageLogs;

use App\Filament\Resources\MileageLogs\Pages\ManageMileageLogs;
use App\Models\Booking;
use App\Models\MileageLog;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MileageLogResource extends Resource
{
    protected static ?string $model = MileageLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $modelLabel = 'mileage log';

    /**
     * @var list<string>
     */
    protected static array $suggestedCategories = ['Dog show', 'Advertising', 'Supplies', 'Training'];

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
                TextInput::make('category')
                    ->datalist(static::$suggestedCategories)
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
                TextColumn::make('category')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('booking.date')
                    ->label('Booking')
                    ->date()
                    ->placeholder('-'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->options(fn (): array => MileageLog::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category', 'category')->all()),
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('date', '<=', $date))),
                TrashedFilter::make(),
            ])
            ->recordActions([
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
