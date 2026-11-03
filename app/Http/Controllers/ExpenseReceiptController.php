<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseReceiptController extends Controller
{
    /**
     * Stream a receipt from the private disk to a logged-in user.
     */
    public function __invoke(Expense $expense, int $index): StreamedResponse
    {
        $path = ($expense->receipts ?? [])[$index] ?? null;

        abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
