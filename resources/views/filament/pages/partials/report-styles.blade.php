<style>
    .report-controls { display: flex; gap: 1rem; align-items: end; flex-wrap: wrap; }
    .report-controls label span { display: block; margin-bottom: 0.25rem; font-size: 0.875rem; font-weight: 500; }
    .report { display: grid; gap: 1.25rem; }
    .report > p { margin: 0; opacity: 0.7; }
    .report section { border: 1px solid rgb(128 128 128 / 0.25); border-radius: 0.75rem; padding: 1rem 1.25rem; overflow-x: auto; }
    .report h3 { margin: 0 0 0.75rem; font-size: 1rem; font-weight: 600; }
    .report table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    .report th { text-align: left; font-weight: 600; opacity: 0.7; }
    .report th, .report td { padding: 0.45rem 0.5rem; border-bottom: 1px solid rgb(128 128 128 / 0.2); }
    .report tbody tr:last-child td { border-bottom: 0; }
    .report .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .report tfoot td { font-weight: 600; border-top: 1px solid rgb(128 128 128 / 0.4); border-bottom: 0; }
    .report .summary tfoot td { font-size: 1.05rem; }
</style>
