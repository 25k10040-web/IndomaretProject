<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Product;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function dashboard()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        // 1. Daily Metrics
        $todaySales = Transaction::whereDate('created_at', $today)->sum('total_amount');
        $todayCount = Transaction::whereDate('created_at', $today)->count();
        $todayProfit = TransactionDetail::whereHas('transaction', function ($q) use ($today) {
            $q->whereDate('created_at', $today);
        })->sum('profit');

        // 2. Monthly Metrics
        $monthSales = Transaction::where('created_at', '>=', $startOfMonth)->sum('total_amount');
        $monthCount = Transaction::where('created_at', '>=', $startOfMonth)->count();
        $monthProfit = TransactionDetail::whereHas('transaction', function ($q) use ($startOfMonth) {
            $q->where('created_at', '>=', $startOfMonth);
        })->sum('profit');

        // 3. Low stock alert count
        $lowStockCount = Product::where('stock', '<=', 5)->count();

        // 4. Last 7 Days Trend (Sales & Profit)
        $chartLabels = [];
        $chartSales = [];
        $chartProfits = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $label = $date->locale('id')->isoFormat('D MMM');

            $daySales = Transaction::whereDate('created_at', $dateStr)->sum('total_amount');
            $dayProfit = TransactionDetail::whereHas('transaction', function ($q) use ($dateStr) {
                $q->whereDate('created_at', $dateStr);
            })->sum('profit');

            $chartLabels[] = $label;
            $chartSales[] = (float) $daySales;
            $chartProfits[] = (float) $dayProfit;
        }

        // 5. Top 5 Best Selling Products (All time / Month)
        $topProducts = TransactionDetail::select(
                'product_id',
                DB::raw('SUM(qty) as total_qty'),
                DB::raw('SUM(subtotal) as total_revenue'),
                DB::raw('SUM(profit) as total_profit')
            )
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // 6. Recent 5 Transactions
        $recentTransactions = Transaction::with(['user', 'details.product'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'todaySales',
            'todayCount',
            'todayProfit',
            'monthSales',
            'monthCount',
            'monthProfit',
            'lowStockCount',
            'chartLabels',
            'chartSales',
            'chartProfits',
            'topProducts',
            'recentTransactions'
        ));
    }

    public function salesReport(Request $request)
    {
        $preset = $request->input('preset', 'today');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        [$start, $end] = $this->resolveDateRange($preset, $startDate, $endDate);

        $query = Transaction::with(['user', 'details.product'])
            ->whereBetween('created_at', [$start, $end]);

        $summaryQuery = clone $query;
        $totalAmount = $summaryQuery->sum('total_amount');
        $totalCount = $summaryQuery->count();
        $cashTotal = (clone $summaryQuery)->where('payment_method', 'cash')->sum('total_amount');
        $nonCashTotal = (clone $summaryQuery)->where('payment_method', '!=', 'cash')->sum('total_amount');

        $transactions = $query->latest()->paginate(15)->withQueryString();

        return view('admin.sales_report', compact(
            'transactions',
            'totalAmount',
            'totalCount',
            'cashTotal',
            'nonCashTotal',
            'preset',
            'start',
            'end'
        ));
    }

    public function profitReport(Request $request)
    {
        $preset = $request->input('preset', 'this_month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        [$start, $end] = $this->resolveDateRange($preset, $startDate, $endDate);

        // Product level profit breakdown for the period
        $productProfits = TransactionDetail::select(
                'product_id',
                DB::raw('SUM(qty) as total_qty'),
                DB::raw('SUM(subtotal) as total_revenue'),
                DB::raw('SUM(purchase_price * qty) as total_cost'),
                DB::raw('SUM(profit) as total_profit')
            )
            ->whereBetween('created_at', [$start, $end])
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_profit')
            ->paginate(15)
            ->withQueryString();

        // Overall summary for the period
        $overallStats = TransactionDetail::whereBetween('created_at', [$start, $end])
            ->selectRaw('
                SUM(subtotal) as gross_revenue,
                SUM(purchase_price * qty) as total_cogs,
                SUM(profit) as net_profit,
                SUM(qty) as total_items_sold
            ')->first();

        $grossRevenue = $overallStats->gross_revenue ?? 0;
        $totalCogs = $overallStats->total_cogs ?? 0;
        $netProfit = $overallStats->net_profit ?? 0;
        $marginPercent = $grossRevenue > 0 ? round(($netProfit / $grossRevenue) * 100, 1) : 0;

        return view('admin.profit_report', compact(
            'productProfits',
            'grossRevenue',
            'totalCogs',
            'netProfit',
            'marginPercent',
            'preset',
            'start',
            'end'
        ));
    }

    public function exportCsv(Request $request)
    {
        $preset = $request->input('preset', 'this_month');
        [$start, $end] = $this->resolveDateRange($preset, $request->input('start_date'), $request->input('end_date'));

        $transactions = Transaction::with(['user', 'details.product'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'asc')
            ->get();

        $csvFileName = 'Laporan_Penjualan_' . $start->format('Ymd') . '_sd_' . $end->format('Ymd') . '.csv';

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$csvFileName}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            // Header Row
            fputcsv($file, [
                'No. Invoice',
                'Tanggal Transaksi',
                'Waktu',
                'Kasir',
                'Metode Pembayaran',
                'Jumlah Item',
                'Total Belanja (Rp)',
                'Total Profit (Rp)'
            ]);

            foreach ($transactions as $tx) {
                fputcsv($file, [
                    $tx->invoice_number,
                    $tx->created_at->format('Y-m-d'),
                    $tx->created_at->format('H:i:s'),
                    $tx->user->name ?? 'Kasir',
                    strtoupper($tx->payment_method),
                    $tx->details->sum('qty'),
                    $tx->total_amount,
                    $tx->details->sum('profit')
                ]);
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    private function resolveDateRange($preset, $startDate = null, $endDate = null): array
    {
        $now = Carbon::now();

        return match ($preset) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth()
            ],
            'custom' => [
                $startDate ? Carbon::parse($startDate)->startOfDay() : $now->copy()->startOfMonth(),
                $endDate ? Carbon::parse($endDate)->endOfDay() : $now->copy()->endOfDay()
            ],
            default => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
        };
    }
}
