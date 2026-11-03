@php
    $report = $this->getReport();
    $money = fn (int $pence): string => '£'.number_format($pence / 100, 2);
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.report-styles')

    <div class="report-controls" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
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
        <x-filament::button tag="a" icon="heroicon-o-printer" target="_blank" :href="route('filament.admin.reports.uc.print', ['from' => $from, 'until' => $until])">Print</x-filament::button>
    </div>

    @if ($report === null)
        <p>Choose a valid date range (the start date must not be after the end date).</p>
    @else
        @include('reports.uc', ['report' => $report, 'from' => $from, 'until' => $until])
    @endif
</x-filament-panels::page>
