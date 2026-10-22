<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\AnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    private function period(Request $request): array
    {
        $data = $request->validate(['period' => 'nullable|in:7,30,90,365,all,custom', 'from' => 'required_if:period,custom|nullable|date_format:Y-m-d|before_or_equal:today', 'to' => 'required_if:period,custom|nullable|date_format:Y-m-d|after_or_equal:from|before_or_equal:today']);
        $today = CarbonImmutable::today();
        $period = $data['period'] ?? '30';
        $to = $period === 'custom' ? CarbonImmutable::parse($data['to']) : $today;
        $from = match ($period) {
            'custom' => CarbonImmutable::parse($data['from']),
            'all' => CarbonImmutable::parse(Order::withTrashed()->min('created_at') ?? $today)->startOfDay(),
            default => $today->subDays((int) $period - 1),
        };

        return ['period' => $period, 'from' => $from, 'to' => $to];
    }

    public function index(Request $request, AnalyticsService $analytics)
    {
        $range = $this->period($request);
        $report = $analytics->report($range['from'], $range['to']->addDay());
        $data = [...$range, ...$report];
        if ($request->expectsJson()) {
            return response()->json(['html' => view('admin.analytics.report', $data)->render(), 'updated_at' => now()->format('H:i:s')])->header('Cache-Control', 'no-store, private');
        }

        return view('admin.analytics.index', $data);
    }

    public function export(Request $request, AnalyticsService $analytics)
    {
        $range = $this->period($request);
        $report = $analytics->report($range['from'], $range['to']->addDay());

        return response()->streamDownload(function () use ($range, $report) {
            $stream = fopen('php://output', 'w');
            $write = fn (array $row) => fputcsv($stream, $row, ',', '"', '');
            $write(['MESH sales report', $range['from']->toDateString(), $range['to']->toDateString(), 'USD']);
            $write(['Metric', 'Value']);
            foreach (['orders', 'completed', 'open', 'deleted'] as $key) {
                $write([$key, $report['summary'][$key]]);
            }
            foreach (['sales', 'collected', 'outstanding', 'cancelled_value', 'average'] as $key) {
                $write([$key.' (USD)', number_format($report['summary'][$key] / 100, 2, '.', '')]);
            }
            $write(['Status', 'Orders', 'Order value (USD)']);
            foreach ($report['statuses'] as $status => $value) {
                $write([$status, $value['count'], number_format($value['value'] / 100, 2, '.', '')]);
            }
            $write(['Period', 'Orders', 'Delivered product sales (USD)']);
            foreach ($report['chart'] as $point) {
                $write([$point['label'], $point['orders'], number_format($point['sales'] / 100, 2, '.', '')]);
            }
            fclose($stream);
        }, 'sales-'.$range['from']->toDateString().'-'.$range['to']->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store, private']);
    }
}
