@php
    $report = $this->getReport();
    $money = fn (int $pence): string => '£'.number_format($pence / 100, 2);
    $miles = fn (float $value): string => rtrim(rtrim(number_format($value, 2), '0'), '.');
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

            @foreach ([
                'Income by month' => $report['income']['by_month'],
                'Income by service' => $report['income']['by_service'],
                'Income by client' => $report['income']['by_client'],
            ] as $heading => $rows)
                <h3>{{ $heading }}</h3>
                <table>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr><td>{{ $row['label'] }}</td><td class="num">{{ $money($row['amount_pence']) }}</td></tr>
                        @empty
                            <tr><td colspan="2">No income in this period.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr><td>Total income</td><td class="num">{{ $money($report['income']['total']) }}</td></tr></tfoot>
                </table>
            @endforeach

            @foreach ([
                'Expenses by month' => $report['expenses']['by_month'],
                'Expenses by category' => $report['expenses']['by_category'],
            ] as $heading => $rows)
                <h3>{{ $heading }}</h3>
                <table>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr><td>{{ $row['label'] }}</td><td class="num">{{ $money($row['amount_pence']) }}</td></tr>
                        @empty
                            <tr><td colspan="2">No expenses in this period.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr><td>Total expenses</td><td class="num">{{ $money($report['expenses']['total']) }}</td></tr></tfoot>
                </table>
            @endforeach

            <h3>Mileage by tax year (HMRC rates)</h3>
            <table>
                <thead><tr><th>Tax year</th><th class="num">Miles</th><th class="num">Cost</th></tr></thead>
                <tbody>
                    @forelse ($report['mileage']['by_tax_year'] as $row)
                        <tr><td>{{ $row['label'] }}</td><td class="num">{{ $miles($row['miles']) }}</td><td class="num">{{ $money($row['cost_pence']) }}</td></tr>
                    @empty
                        <tr><td colspan="3">No mileage in this period.</td></tr>
                    @endforelse
                </tbody>
                <tfoot><tr><td>Total mileage</td><td class="num">{{ $miles($report['mileage']['miles']) }}</td><td class="num">{{ $money($report['mileage']['cost_pence']) }}</td></tr></tfoot>
            </table>

            <h3>Net profit by tax year</h3>
            <table>
                <thead><tr><th>Tax year</th><th class="num">Income</th><th class="num">Expenses</th><th class="num">Mileage</th><th class="num">Net profit</th></tr></thead>
                <tbody>
                    @forelse ($report['net']['by_tax_year'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="num">{{ $money($row['income_pence']) }}</td>
                            <td class="num">{{ $money($row['expenses_pence']) }}</td>
                            <td class="num">{{ $money($row['mileage_pence']) }}</td>
                            <td class="num">{{ $money($row['net_pence']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Nothing in this period.</td></tr>
                    @endforelse
                </tbody>
                <tfoot><tr><td colspan="4">Net profit</td><td class="num">{{ $money($report['net']['total']) }}</td></tr></tfoot>
            </table>
        </div>
    @endif
</x-filament-panels::page>
