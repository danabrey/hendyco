@php
    $report = $this->getReport();
    $money = fn (int $pence): string => '£'.number_format($pence / 100, 2);
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.report-styles')

    <div class="report-no-print" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
        <label>
            <span>From</span>
            <x-filament::input.wrapper>
                <x-filament::input type="date" wire:model.live="from" />
            </x-filament::input.wrapper>
        </label>
        <label>
            <span>To</span>
            <x-filament::input.wrapper>
                <x-filament::input type="date" wire:model.live="until" />
            </x-filament::input.wrapper>
        </label>
        <x-filament::button type="button" icon="heroicon-o-printer" onclick="window.print()">Print</x-filament::button>
    </div>

    @if ($report === null)
        <p>Choose a valid date range (the start date must not be after the end date).</p>
    @else
        <div class="report">
            <p>
                Period: {{ \Illuminate\Support\Carbon::parse($from)->format('j F Y') }}
                to {{ \Illuminate\Support\Carbon::parse($until)->format('j F Y') }}
            </p>

            <h3>Income received</h3>
            <table>
                <thead><tr><th>Date paid</th><th>Description</th><th class="num">Amount</th></tr></thead>
                <tbody>
                    @forelse ($report['income'] as $row)
                        <tr><td>{{ $row['date']->format('j M Y') }}</td><td>{{ $row['label'] }}</td><td class="num">{{ $money($row['amount_pence']) }}</td></tr>
                    @empty
                        <tr><td colspan="3">No income received in this period.</td></tr>
                    @endforelse
                </tbody>
                <tfoot><tr><td colspan="2">Total income</td><td class="num">{{ $money($report['income_pence']) }}</td></tr></tfoot>
            </table>

            <h3>Expenses</h3>
            <table>
                <thead><tr><th>Date</th><th>Description</th><th>Category</th><th class="num">Amount</th></tr></thead>
                <tbody>
                    @forelse ($report['expenses'] as $expense)
                        <tr><td>{{ $expense->date->format('j M Y') }}</td><td>{{ $expense->description }}</td><td>{{ $expense->category->name }}</td><td class="num">{{ $money($expense->amount_pence) }}</td></tr>
                    @empty
                        <tr><td colspan="4">No expenses in this period.</td></tr>
                    @endforelse
                </tbody>
                <tfoot><tr><td colspan="3">Total expenses</td><td class="num">{{ $money($report['expenses_pence']) }}</td></tr></tfoot>
            </table>

            <h3>Mileage</h3>
            <table>
                <thead><tr><th>Date</th><th>Journey</th><th class="num">Miles</th><th class="num">Cost</th></tr></thead>
                <tbody>
                    @forelse ($report['mileage'] as $entry)
                        <tr><td>{{ $entry['date']->format('j M Y') }}</td><td>{{ $entry['label'] }}</td><td class="num">{{ rtrim(rtrim(number_format($entry['miles'], 2), '0'), '.') }}</td><td class="num">{{ $money($entry['cost_pence']) }}</td></tr>
                    @empty
                        <tr><td colspan="4">No mileage in this period.</td></tr>
                    @endforelse
                </tbody>
                <tfoot><tr><td colspan="2">Total mileage</td><td class="num">{{ rtrim(rtrim(number_format($report['miles'], 2), '0'), '.') }}</td><td class="num">{{ $money($report['mileage_pence']) }}</td></tr></tfoot>
            </table>

            <h3>Summary</h3>
            <table>
                <tbody>
                    <tr><td>Total income</td><td class="num">{{ $money($report['income_pence']) }}</td></tr>
                    <tr><td>Total expenditure (expenses + mileage)</td><td class="num">{{ $money($report['expenditure_pence']) }}</td></tr>
                </tbody>
                <tfoot><tr><td>Net</td><td class="num">{{ $money($report['net_pence']) }}</td></tr></tfoot>
            </table>
        </div>
    @endif
</x-filament-panels::page>
