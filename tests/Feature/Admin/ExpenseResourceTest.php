<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ExpenseCategories\Pages\ManageExpenseCategories;
use App\Filament\Resources\Expenses\Pages\ManageExpenses;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->actingAs(User::factory()->create());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        auth()->logout();

        $this->get('/admin/expenses')->assertRedirect('/admin/login');
    }

    public function test_user_can_create_a_category_and_it_must_be_unique(): void
    {
        Livewire::test(ManageExpenseCategories::class)
            ->callAction('create', ['name' => 'Insurance'])
            ->assertHasNoFormErrors();
        $this->assertDatabaseHas('expense_categories', ['name' => 'Insurance']);

        Livewire::test(ManageExpenseCategories::class)
            ->callAction('create', ['name' => 'Insurance'])
            ->assertHasFormErrors(['name' => 'unique']);
    }

    public function test_user_can_create_an_expense_with_several_private_receipts(): void
    {
        $category = ExpenseCategory::factory()->create();

        Livewire::test(ManageExpenses::class)
            ->callAction('create', [
                'date' => '2026-11-02',
                'description' => 'Poo bags',
                'amount_pence' => 12.99,
                'expense_category_id' => $category->id,
                'receipts' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->create('b.pdf', 50, 'application/pdf')],
            ])
            ->assertHasNoFormErrors();

        $expense = Expense::firstWhere('description', 'Poo bags');
        $this->assertSame(1299, $expense->amount_pence);
        $this->assertCount(2, $expense->receipts);
        foreach ($expense->receipts as $path) {
            Storage::disk('local')->assertExists($path);
            $this->assertStringNotContainsString('public', $path);
        }
    }

    public function test_receipts_must_be_images_or_pdfs(): void
    {
        $category = ExpenseCategory::factory()->create();

        Livewire::test(ManageExpenses::class)
            ->callAction('create', [
                'date' => '2026-11-02',
                'description' => 'Bad file',
                'amount_pence' => 5,
                'expense_category_id' => $category->id,
                'receipts' => [UploadedFile::fake()->create('x.exe', 10, 'application/x-msdownload')],
            ])
            ->assertHasFormErrors();

        $this->assertDatabaseMissing('expenses', ['description' => 'Bad file']);
    }

    public function test_receipt_of_a_trashed_expense_is_still_served(): void
    {
        Storage::disk('local')->put('receipts/t.txt', 'x');
        $expense = Expense::factory()->create(['receipts' => ['receipts/t.txt']]);
        $expense->delete();

        $this->get(route('filament.admin.expenses.receipt', ['expense' => $expense, 'index' => 0]))->assertOk();
    }

    public function test_expense_requires_date_description_amount_and_category(): void
    {
        Livewire::test(ManageExpenses::class)
            ->callAction('create', ['date' => null, 'description' => null, 'amount_pence' => null, 'expense_category_id' => null])
            ->assertHasFormErrors(['date' => 'required', 'description' => 'required', 'amount_pence' => 'required', 'expense_category_id' => 'required']);
    }

    public function test_receipt_is_served_to_a_logged_in_user_only(): void
    {
        Storage::disk('local')->put('receipts/r.txt', 'receipt body');
        $expense = Expense::factory()->create(['receipts' => ['receipts/r.txt']]);
        $url = route('filament.admin.expenses.receipt', ['expense' => $expense, 'index' => 0]);

        $this->get($url)->assertOk();
        $this->get(route('filament.admin.expenses.receipt', ['expense' => $expense, 'index' => 5]))->assertNotFound();

        auth()->logout();
        $this->get($url)->assertRedirect('/admin/login');
    }

    public function test_expenses_can_be_filtered_by_category_and_date_range(): void
    {
        $supplies = ExpenseCategory::factory()->create();
        $mine = Expense::factory()->for($supplies, 'category')->create(['date' => '2026-11-02']);
        $otherCategory = Expense::factory()->create(['date' => '2026-11-02']);
        $tooOld = Expense::factory()->for($supplies, 'category')->create(['date' => '2026-09-01']);

        Livewire::test(ManageExpenses::class)
            ->filterTable('expense_category_id', $supplies->id)
            ->filterTable('date_range', ['from' => '2026-10-01', 'until' => '2026-11-30'])
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$otherCategory, $tooOld]);
    }

    public function test_deleting_an_expense_soft_deletes_it(): void
    {
        $expense = Expense::factory()->create();

        Livewire::test(ManageExpenses::class)->callAction(TestAction::make('delete')->table($expense));

        $this->assertSoftDeleted($expense);
    }

    public function test_category_in_use_cannot_be_deleted_from_the_list(): void
    {
        $used = Expense::factory()->create()->category;
        $unused = ExpenseCategory::factory()->create();

        Livewire::test(ManageExpenseCategories::class)
            ->assertActionHidden(TestAction::make('delete')->table($used))
            ->assertActionVisible(TestAction::make('delete')->table($unused));
    }
}
