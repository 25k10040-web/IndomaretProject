@extends('layouts.app')

@section('title', 'Laporan Keuntungan & Profitabilitas - RetailPOS Admin')

@section('content')
<div class="mobile-container" style="max-width: 950px;">

    <!-- Header & Title -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Analisis Profitabilitas Retail</div>
            <h1 style="font-size: 1.45rem; font-weight: 800; color: white;">💰 Laporan Keuntungan (Profit)</h1>
        </div>

        <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
            🖨️ Cetak Laporan
        </button>
    </div>

    <!-- Date Range Filter Form -->
    <div class="card" style="margin-bottom: 1rem; padding: 1rem;">
        <form action="{{ route('admin.profit') }}" method="GET">
            <div style="display: flex; gap: 0.4rem; overflow-x: auto; padding-bottom: 0.5rem; margin-bottom: 0.75rem;">
                <a href="{{ route('admin.profit', ['preset' => 'today']) }}" class="preset-chip {{ $preset === 'today' ? 'active' : '' }}">Hari Ini</a>
                <a href="{{ route('admin.profit', ['preset' => 'last_7_days']) }}" class="preset-chip {{ $preset === 'last_7_days' ? 'active' : '' }}">7 Hari Terakhir</a>
                <a href="{{ route('admin.profit', ['preset' => 'this_month']) }}" class="preset-chip {{ $preset === 'this_month' ? 'active' : '' }}">Bulan Ini</a>
                <a href="{{ route('admin.profit', ['preset' => 'last_month']) }}" class="preset-chip {{ $preset === 'last_month' ? 'active' : '' }}">Bulan Lalu</a>
            </div>

            <!-- Custom Date Range -->
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; padding-top: 0.5rem; border-top: 1px solid var(--border-subtle);">
                <span style="font-size: 0.8rem; color: var(--text-muted);">Kustom Tanggal:</span>
                <input type="date" name="start_date" class="form-control" style="width: auto; padding: 0.35rem 0.6rem; font-size: 0.8rem;" value="{{ request('start_date', $start->format('Y-m-d')) }}">
                <span style="font-size: 0.8rem; color: var(--text-muted);">s/d</span>
                <input type="date" name="end_date" class="form-control" style="width: auto; padding: 0.35rem 0.6rem; font-size: 0.8rem;" value="{{ request('end_date', $end->format('Y-m-d')) }}">
                <input type="hidden" name="preset" value="custom">
                <button type="submit" class="btn btn-primary btn-sm">Terapkan Filter</button>
            </div>
        </form>
    </div>

    <!-- KPI Profitability Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem;">
        
        <div class="card" style="padding: 1.1rem; border-color: rgba(59, 130, 246, 0.3);">
            <div class="stat-label">Total Omzet (Revenue)</div>
            <div class="stat-value" style="font-size: 1.4rem; color: #60a5fa;">
                Rp {{ number_format($grossRevenue, 0, ',', '.') }}
            </div>
            <div class="stat-sub">Nilai kotor penjualan barang</div>
        </div>

        <div class="card" style="padding: 1.1rem; border-color: rgba(244, 63, 94, 0.3);">
            <div class="stat-label">Harga Pokok (HPP / Modal)</div>
            <div class="stat-value" style="font-size: 1.4rem; color: #fb7185;">
                Rp {{ number_format($totalCogs, 0, ',', '.') }}
            </div>
            <div class="stat-sub">Modal pembelian dari supplier</div>
        </div>

        <div class="card" style="padding: 1.1rem; border-color: rgba(16, 185, 129, 0.4); background: rgba(16, 185, 129, 0.05);">
            <div class="stat-label" style="color: #34d399;">Keuntungan Bersih (Profit)</div>
            <div class="stat-value" style="font-size: 1.5rem; color: #34d399;">
                Rp {{ number_format($netProfit, 0, ',', '.') }}
            </div>
            <div class="stat-sub" style="color: #34d399; font-weight: 700;">
                Rata-rata Margin: {{ $marginPercent }}%
            </div>
        </div>

    </div>

    <!-- Product Breakdown Table Card -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title" style="font-size: 1rem;">
                Rincian Keuntungan per Produk ({{ $productProfits->total() }})
            </h2>
            <span style="font-size: 0.75rem; color: var(--text-muted);">
                Diurutkan berdasarkan profit tertinggi
            </span>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th style="text-align: center;">Qty Terjual</th>
                        <th style="text-align: right;">Total Omzet</th>
                        <th style="text-align: right;">Total Modal (HPP)</th>
                        <th style="text-align: right;">Laba Bersih</th>
                        <th style="text-align: center;">Margin %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productProfits as $item)
                    @php
                        $itemMargin = $item->total_revenue > 0 ? round(($item->total_profit / $item->total_revenue) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td>
                            <strong style="color: white;">{{ $item->product->name ?? 'Produk Dihapus' }}</strong>
                            <div style="font-family: var(--font-mono); font-size: 0.75rem; color: #60a5fa;">
                                {{ $item->product->sku ?? '-' }}
                            </div>
                        </td>
                        <td style="text-align: center; font-weight: 700;">
                            {{ $item->total_qty }} pcs
                        </td>
                        <td style="text-align: right; color: var(--text-secondary);">
                            Rp {{ number_format($item->total_revenue, 0, ',', '.') }}
                        </td>
                        <td style="text-align: right; color: #fb7185;">
                            Rp {{ number_format($item->total_cost, 0, ',', '.') }}
                        </td>
                        <td style="text-align: right; font-weight: 800; color: #34d399;">
                            Rp {{ number_format($item->total_profit, 0, ',', '.') }}
                        </td>
                        <td style="text-align: center;">
                            <span style="font-size: 0.75rem; padding: 2px 7px; border-radius: 4px; font-weight: 700; background: rgba(16, 185, 129, 0.15); color: #34d399;">
                                +{{ $itemMargin }}%
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            Tidak ada data penjualan pada rentang tanggal ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem;">
            {{ $productProfits->links() }}
        </div>
    </div>

</div>

<!-- Mobile Bottom Navigation Bar -->
<nav class="mobile-bottom-nav no-print">
    <a href="{{ route('admin.dashboard') }}" class="mobile-nav-item">
        <span class="mobile-nav-icon">📊</span>
        <span>Dashboard</span>
    </a>
    <a href="{{ route('admin.sales') }}" class="mobile-nav-item">
        <span class="mobile-nav-icon">📈</span>
        <span>Penjualan</span>
    </a>
    <a href="{{ route('admin.profit') }}" class="mobile-nav-item active">
        <span class="mobile-nav-icon">💰</span>
        <span>Keuntungan</span>
    </a>
    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
        @csrf
        <button type="submit" class="mobile-nav-item" style="background: none; border: none; cursor: pointer; color: inherit;">
            <span class="mobile-nav-icon">🚪</span>
            <span>Keluar</span>
        </button>
    </form>
</nav>
@endsection
