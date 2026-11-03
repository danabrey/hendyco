<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ExpenseCategories\Pages\ManageExpenseCategories;
use App\Filament\Resources\Expenses\Pages\ManageExpenses;
use App\Filament\Resources\GiftCards\Pages\ListGiftCards;
use App\Filament\Resources\MileageLogs\Pages\ManageMileageLogs;
use App\Filament\Resources\Services\Pages\ManageServices;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\GiftCard;
use App\Models\MileageLog;
use App\Models\Service;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_every_list_offers_a_view_action(): void
    {
        $cases = [
            [ManageServices::class, Service::factory()->create()],
            [ManageExpenseCategories::class, ExpenseCategory::factory()->create()],
            [ManageMileageLogs::class, MileageLog::factory()->create()],
            [ManageExpenses::class, Expense::factory()->create()],
            [ListGiftCards::class, GiftCard::factory()->create()],
        ];

        foreach ($cases as [$page, $record]) {
            Livewire::test($page)->assertActionVisible(TestAction::make('view')->table($record));
        }
    }

    public function test_expense_view_shows_its_details_and_receipt_links(): void
    {
        $expense = Expense::factory()->create(['description' => 'Poo bags', 'amount_pence' => 1299, 'receipts' => ['receipts/a.jpg', 'receipts/b.pdf']]);

        Livewire::test(ManageExpenses::class)
            ->mountAction(TestAction::make('view')->table($expense))
            ->assertSee('Poo bags')
            ->assertSee('£12.99')
            ->assertSee('Receipt 1')
            ->assertSee('Receipt 2');
    }
}
