<?php

namespace App\Filament\Tables;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class DateRangeFilter
{
    /**
     * A from/until filter on a date column.
     */
    public static function make(string $column = 'date'): Filter
    {
        return Filter::make('date_range')
            ->schema([
                DatePicker::make('from'),
                DatePicker::make('until'),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate($column, '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate($column, '<=', $date)));
    }
}
