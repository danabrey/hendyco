<?php

namespace App\Filament\Resources\Expenses;

use App\Filament\Forms\PenceInput;
use App\Filament\Resources\Expenses\Pages\ManageExpenses;
use App\Filament\Tables\DateRangeFilter;
use App\Models\Expense;
use App\NavigationGroup;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use UnitEnum;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Money;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->required()
                    ->default(today()),
                TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                PenceInput::make('amount_pence')
                    ->label('Amount')
                    ->required(),
                Select::make('expense_category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->unique('expense_categories', 'name')
                            ->maxLength(255),
                    ]),
                FileUpload::make('receipts')
                    ->multiple()
                    ->disk('local')
                    ->directory('receipts')
                    ->visibility('private')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'application/pdf'])
                    ->maxSize(10240)
                    ->openable(false)
                    ->downloadable(false)
                    ->previewable(false)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->sortable(),
                TextColumn::make('amount_pence')
                    ->label('Amount')
                    ->money('GBP', divideBy: 100)
                    ->sortable(),
                TextColumn::make('receipts')
                    ->state(fn (Expense $record): HtmlString => new HtmlString(
                        collect($record->receipts ?? [])
                            ->keys()
                            ->map(fn (int $index): string => '<a class="text-primary-600 underline" target="_blank" href="'.e(route('filament.admin.expenses.receipt', ['expense' => $record, 'index' => $index])).'">Receipt '.($index + 1).'</a>')
                            ->implode(' · ')
                    ))
                    ->html()
                    ->placeholder('-'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('expense_category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),
                DateRangeFilter::make(),
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
            'index' => ManageExpenses::route('/'),
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
