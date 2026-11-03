<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'description',
        'amount_pence',
        'expense_category_id',
        'receipts',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'receipts' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * Links to each receipt, served through the login-protected receipt route.
     */
    public function receiptLinks(): HtmlString
    {
        return new HtmlString(
            collect($this->receipts ?? [])
                ->keys()
                ->map(fn (int $index): string => '<a class="text-primary-600 underline" target="_blank" href="'.e(route('filament.admin.expenses.receipt', ['expense' => $this, 'index' => $index])).'">Receipt '.($index + 1).'</a>')
                ->implode(' · ')
        );
    }

    public function receiptPath(int $index): ?string
    {
        return ($this->receipts ?? [])[$index] ?? null;
    }
}
