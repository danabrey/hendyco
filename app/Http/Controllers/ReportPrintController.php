<?php

namespace App\Http\Controllers;

use App\Reports\BusinessReport;
use App\Reports\UcReportBuilder;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReportPrintController extends Controller
{
    /**
     * A print-ready Universal Credit report for the requested range.
     */
    public function uc(Request $request): View
    {
        [$from, $until] = $this->range($request);

        return view('reports.print', [
            'title' => 'Universal Credit report',
            'body' => 'reports.uc',
            'report' => (new UcReportBuilder)->build($from, $until),
            'from' => $from->toDateString(),
            'until' => $until->toDateString(),
        ]);
    }

    /**
     * A print-ready business report for the requested range.
     */
    public function business(Request $request): View
    {
        [$from, $until] = $this->range($request);

        return view('reports.print', [
            'title' => 'Business report',
            'body' => 'reports.business',
            'report' => (new BusinessReport)->build($from, $until),
            'from' => $from->toDateString(),
            'until' => $until->toDateString(),
        ]);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function range(Request $request): array
    {
        try {
            $from = CarbonImmutable::make((string) $request->query('from'));
            $until = CarbonImmutable::make((string) $request->query('until'));
        } catch (InvalidFormatException) {
            abort(422, 'Invalid date range.');
        }

        abort_if(! $from || ! $until || $from->gt($until), 422, 'Invalid date range.');

        return [$from, $until];
    }
}
