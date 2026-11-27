<?php

namespace App\Http\Controllers;

use App\Models\DetailPesanan;
use App\Models\Pesanan;
use App\Services\ReportExportService;
use App\Support\Fmt;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OwnerReportController extends Controller
{
    public function index(Request $r): View
    {
        $periods = $this->availablePeriods();
        $active = (string) $r->query('periode', $periods[0]);
        $report = $this->buildReport($active);

        return view('owner.report', compact('report', 'periods', 'active'));
    }

    public function export(Request $r, string $format, ReportExportService $exporter): Response
    {
        $periods = $this->availablePeriods();
        $active = (string) $r->query('periode', $periods[0]);
        $report = $this->buildReport($active);

        return $exporter->download($report, $format);
    }

    /**
     * @return array<int, string>
     */
    private function availablePeriods(): array
    {
        $now = now();
        $prev = $now->copy()->subMonthNoOverflow();

        return [
            '7 hari terakhir',
            '30 hari terakhir',
            Fmt::monthYear($now, true),
            Fmt::monthYear($prev, true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReport(string $periode): array
    {
        [$start, $end, $label] = $this->resolveRange($periode);

        $countedStatuses = [
            Pesanan::STATUS_DIBAYAR,
            Pesanan::STATUS_DIKIRIM,
            Pesanan::STATUS_SELESAI,
        ];

        $orders = Pesanan::query()
            ->whereIn('status', $countedStatuses)
            ->whereBetween(DB::raw('COALESCE(dibayar_at, created_at)'), [$start, $end])
            ->get();

        $orderIds = $orders->pluck('id');
        $totalSales = (float) $orders->sum('total_produk');

        $details = $orderIds->isEmpty()
            ? collect()
            : DetailPesanan::query()->whereIn('pesanan_id', $orderIds)->get();

        $totalUnits = (int) $details->sum('jumlah');

        $dailyBuckets = [];
        $periodDays = CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay());
        $daysArray = iterator_to_array($periodDays);
        if (count($daysArray) > 14) {
            $daysArray = array_slice($daysArray, -7);
        }

        foreach ($daysArray as $d) {
            $dailyBuckets[$d->toDateString()] = [
                'label' => $d->day . ' ' . Fmt::MONTHS_SHORT[$d->month - 1],
                'total' => 0.0,
            ];
        }

        foreach ($orders as $o) {
            $dt = ($o->dibayar_at ?? $o->created_at)?->toDateString();
            if ($dt && isset($dailyBuckets[$dt])) {
                $dailyBuckets[$dt]['total'] += (float) $o->total_produk;
            }
        }

        $daily = array_map(
            fn (array $b) => [$b['label'], (int) round($b['total'] / 1000)],
            array_values($dailyBuckets)
        );

        $top = $details
            ->groupBy('nama_produk')
            ->map(function ($rows, string $name): array {
                $units = (int) $rows->sum('jumlah');
                $sales = (float) $rows->sum(fn (DetailPesanan $d) => $d->subtotal ?? ($d->harga_satuan * $d->jumlah));

                return [$name, $units, $sales];
            })
            ->sortByDesc(fn (array $row) => $row[2])
            ->values()
            ->all();

        return [
            'label' => $label,
            'orders' => $orders->count(),
            'units' => $totalUnits,
            'total_sales' => $totalSales,
            'daily' => $daily,
            'top' => $top,
        ];
    }

    /**
     * @return array{0:Carbon,1:Carbon,2:string}
     */
    private function resolveRange(string $periode): array
    {
        $now = now();

        if ($periode === '30 hari terakhir') {
            $start = $now->copy()->subDays(29)->startOfDay();
            $end = $now->copy()->endOfDay();

            return [$start, $end, mb_strtoupper(Fmt::date($start) . ' – ' . Fmt::dateLong($end))];
        }

        foreach (array_reverse(Fmt::MONTHS_LONG, true) as $idx => $monthName) {
            if (preg_match('/^' . preg_quote($monthName, '/') . '\s+(\d{4})$/i', trim($periode), $m)) {
                $start = Carbon::create((int) $m[1], $idx + 1, 1)->startOfDay();
                $end = $start->copy()->endOfMonth()->endOfDay();

                return [$start, $end, mb_strtoupper($periode)];
            }
        }

        // Default: 7 hari terakhir
        $start = $now->copy()->subDays(6)->startOfDay();
        $end = $now->copy()->endOfDay();

        return [$start, $end, mb_strtoupper(Fmt::date($start) . ' – ' . Fmt::dateLong($end))];
    }
}
