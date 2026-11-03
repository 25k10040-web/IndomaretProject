@extends('layouts.app')

@section('title', 'Laporan Penjualan - RetailPOS Admin')

@section('content')
<div class="mobile-container" style="max-width: 900px;">

    <!-- Header & Title -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Modul Analitik & Audit</div>
            <h1 style="font-size: 1.45rem; font-weight: 800; color: white;">📈 Laporan Transaksi Penjualan</h1>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('admin.export.csv', ['preset' => $preset, 'start_date' => request('start_date'), 'end_date' => request('end_date')]) }}" class="btn btn-secondary btn-sm" style="border-color: rgba(16, 185, 129, 0.4); color: #34d399;">
                📥 Export CSV
            </a>
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                🖨️ Cetak
            </button>
        </div>
    </div>

    <!-- Date Range Filter Form -->
    <div class="card" style="margin-bottom: 1rem; padding: 1rem;">
        <form action="{{ route('admin.sales') }}" method="GET">
            <div style="display: flex; gap: 0.4rem; overflow-x: auto; padding-bottom: 0.5rem; margin-bottom: 0.75rem;">
                <a href="{{ route('admin.sales', ['preset' => 'today']) }}" class="preset-chip {{ $preset === 'today' ? 'active' : '' }}">Hari Ini</a>
                <a href="{{ route('admin.sales', ['preset' => 'yesterday']) }}" class="preset-chip {{ $preset === 'yesterday' ? 'active' : '' }}">Kemarin</a>
                <a href="{{ route('admin.sales', ['preset' => 'last_7_days']) }}" class="preset-chip {{ $preset === 'last_7_days' ? 'active' : '' }}">7 Hari Terakhir</a>
                <a href="{{ route('admin.sales', ['preset' => 'this_month']) }}" class="preset-chip {{ $preset === 'this_month' ? 'active' : '' }}">Bulan Ini</a>
                <a href="{{ route('admin.sales', ['preset' => 'last_month']) }}" class="preset-chip {{ $preset === 'last_month' ? 'active' : '' }}">Bulan Lalu</a>
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

    <!-- Summary KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem;">
        <div class="card" style="padding: 1rem;">
            <div class="stat-label">Total Omzet Periode Ini</div>
            <div class="stat-value" style="font-size: 1.35rem; color: #34d399;">
                Rp {{ number_format($totalAmount, 0, ',', '.') }}
            </div>
            <div class="stat-sub">{{ $totalCount }} transaksi tercatat</div>
        </div>

        <div class="card" style="padding: 1rem;">
            <div class="stat-label">Rata-rata Transaksi (AOV)</div>
            <div class="stat-value" style="font-size: 1.35rem; color: #60a5fa;">
                Rp {{ number_format($totalCount > 0 ? $totalAmount / $totalCount : 0, 0, ',', '.') }}
            </div>
            <div class="stat-sub">Rata-rata keranjang belanja</div>
        </div>

        <div class="card" style="padding: 1rem;">
            <div class="stat-label">Pembayaran Tunai vs Non-Tunai</div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-top: 0.35rem;">
                <span style="color: var(--text-muted);">💵 Tunai:</span>
                <strong style="color: white;">Rp {{ number_format($cashTotal, 0, ',', '.') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-top: 0.2rem;">
                <span style="color: var(--text-muted);">📱 Non-Tunai:</span>
                <strong style="color: #60a5fa;">Rp {{ number_format($nonCashTotal, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    <!-- Transactions Table Card -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title" style="font-size: 1rem;">
                Daftar Struk Penjualan ({{ $transactions->total() }})
            </h2>
            <span style="font-size: 0.75rem; color: var(--text-muted);">
                Periode: {{ $start->format('d/m/Y') }} - {{ $end->format('d/m/Y') }}
            </span>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Invoice / Tanggal</th>
                        <th>Kasir</th>
                        <th>Metode</th>
                        <th>Item Terjual</th>
                        <th style="text-align: right;">Total Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                    <tr>
                        <td>
                            <strong style="font-family: var(--font-mono); color: #60a5fa;">{{ $tx->invoice_number }}</strong>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                {{ $tx->created_at->format('d/m/Y H:i') }}
                            </div>
                        </td>
                        <td style="font-size: 0.85rem; color: var(--text-secondary);">
                            {{ $tx->user->name ?? 'Kasir' }}
                        </td>
                        <td>
                            <span style="font-size: 0.75rem; padding: 2px 7px; border-radius: 4px; font-weight: 700;
                                {{ $tx->payment_method === 'cash' ? 'background: rgba(16, 185, 129, 0.15); color: #34d399;' : 'background: rgba(59, 130, 246, 0.15); color: #60a5fa;' }}">
                                {{ strtoupper($tx->payment_method) }}
                            </span>
                        </td>
                        <td style="font-size: 0.85rem;">
                            <span style="color: white; font-weight: 600;">{{ $tx->details->sum('qty') }} pcs</span>
                            <div style="font-size: 0.7rem; color: var(--text-muted); max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                @foreach($tx->details as $d)
                                    {{ $d->product->name ?? 'Item' }} ({{ $d->qty }}x),
                                @endforeach
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <strong style="color: white; font-size: 0.95rem;">
                                Rp {{ number_format($tx->total_amount, 0, ',', '.') }}
                            </strong>
                            <div style="font-size: 0.7rem; color: #34d399;">
                                Profit: +Rp {{ number_format($tx->total_profit, 0, ',', '.') }}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            Tidak ada data penjualan pada periode tanggal ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem;">
            {{ $transactions->links() }}
        </div>
    </div>

</div>

<!-- Mobile Bottom Navigation Bar -->
<nav class="mobile-bottom-nav no-print">
    <a href="{{ route('admin.dashboard') }}" class="mobile-nav-item">
        <span class="mobile-nav-icon">📊</span>
        <span>Dashboard</span>
    </a>
    <a href="{{ route('admin.sales') }}" class="mobile-nav-item active">
        <span class="mobile-nav-icon">📈</span>
        <span>Penjualan</span>
    </a>
    <a href="{{ route('admin.profit') }}" class="mobile-nav-item">
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
