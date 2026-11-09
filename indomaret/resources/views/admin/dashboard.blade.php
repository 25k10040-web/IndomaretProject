@extends('layouts.app')

@section('title', 'Dashboard Eksekutif - RetailPOS Admin')

@section('content')
<div class="mobile-container">

    <!-- Executive Greeting & Read-Only Badge -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
                {{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
            </div>
            <h1 style="font-size: 1.45rem; font-weight: 800; color: white;">Executive Dashboard</h1>
        </div>
        <span class="nav-role-badge role-uadmin" style="font-size: 0.75rem; padding: 4px 10px;">
            🔒 READ ONLY
        </span>
    </div>

    <!-- Daily Metrics Cards -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
        
        <!-- Penjualan Hari Ini -->
        <div class="card" style="padding: 1rem; border-color: rgba(59, 130, 246, 0.3);">
            <div class="stat-label" style="font-size: 0.7rem;">Penjualan Hari BESOK</div>
            <div class="stat-value" style="font-size: 1.35rem; color: #60a5fa;">
                Rp {{ number_format($todaySales, 0, ',', '.') }}
            </div>
            <div class="stat-sub" style="font-size: 0.7rem;">
                <span>🛒</span> {{ $todayCount }} transaksi
            </div>
        </div>

        <!-- Profit Hari Ini -->
        <div class="card" style="padding: 1rem; border-color: rgba(16, 185, 129, 0.3);">
            <div class="stat-label" style="font-size: 0.7rem;">Profit Bersih Hari Ini</div>
            <div class="stat-value" style="font-size: 1.35rem; color: #34d399;">
                Rp {{ number_format($todayProfit, 0, ',', '.') }}
            </div>
            <div class="stat-sub" style="font-size: 0.7rem; color: #34d399;">
                Margin: {{ $todaySales > 0 ? round(($todayProfit / $todaySales) * 100, 1) : 0 }}%
            </div>
        </div>

    </div>

    <!-- Monthly Overview Card -->
    <div class="card" style="margin-bottom: 1rem; background: linear-gradient(135deg, rgba(26, 34, 52, 0.9), rgba(17, 24, 39, 0.9));">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <div style="font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>📅</span> Akumulasi Bulan Ini
            </div>
            <span style="font-size: 0.75rem; color: var(--text-muted);">{{ \Carbon\Carbon::now()->locale('id')->isoFormat('MMMM Y') }}</span>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; padding-top: 0.5rem; border-top: 1px solid var(--border-subtle);">
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Total Omzet Bulan Ini</div>
                <div style="font-size: 1.2rem; font-weight: 800; color: white;">
                    Rp {{ number_format($monthSales, 0, ',', '.') }}
                </div>
                <div style="font-size: 0.7rem; color: var(--text-secondary); margin-top: 2px;">{{ $monthCount }} transaksi</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Total Keuntungan Bersih</div>
                <div style="font-size: 1.2rem; font-weight: 800; color: #34d399;">
                    Rp {{ number_format($monthProfit, 0, ',', '.') }}
                </div>
                <div style="font-size: 0.7rem; color: #34d399; margin-top: 2px;">
                    Margin Rata-rata: {{ $monthSales > 0 ? round(($monthProfit / $monthSales) * 100, 1) : 0 }}%
                </div>
            </div>
        </div>

        @if($lowStockCount > 0)
        <div style="margin-top: 0.85rem; padding: 0.5rem 0.75rem; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: space-between; font-size: 0.8rem; color: #fbbf24;">
            <span>⚠️ Terdapat <strong>{{ $lowStockCount }} produk</strong> dengan stok menipis (≤5)</span>
        </div>
        @endif
    </div>

    <!-- Trend Chart Card (Sales & Profit 7 Hari Terakhir) -->
    <div class="card" style="margin-bottom: 1rem;">
        <div class="card-header" style="margin-bottom: 0.75rem;">
            <h2 class="card-title" style="font-size: 0.95rem;">📈 Tren Penjualan & Profit (7 Hari)</h2>
        </div>
        
        <div style="height: 220px; position: relative;">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    <!-- Top 5 Best Selling Products -->
    <div class="card" style="margin-bottom: 1rem;">
        <div class="card-header" style="margin-bottom: 0.5rem;">
            <h2 class="card-title" style="font-size: 0.95rem;">🏆 5 Produk Paling Laris</h2>
            <a href="{{ route('admin.profit') }}" style="font-size: 0.75rem; color: #60a5fa; text-decoration: none;">Rincian ➔</a>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.65rem;">
            @forelse($topProducts as $idx => $item)
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.6rem 0.75rem; background: var(--bg-surface-elevated); border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                <div style="display: flex; align-items: center; gap: 0.65rem; min-width: 0;">
                    <div style="width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 800;
                        background: {{ $idx === 0 ? 'linear-gradient(135deg, #f59e0b, #d97706)' : ($idx === 1 ? 'linear-gradient(135deg, #94a3b8, #64748b)' : ($idx === 2 ? 'linear-gradient(135deg, #b45309, #78350f)' : 'rgba(255,255,255,0.1)')) }};
                        color: white;">
                        {{ $idx + 1 }}
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-size: 0.85rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: white;">
                            {{ $item->product->name ?? 'Produk' }}
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">
                            Terjual: <strong style="color: #60a5fa;">{{ $item->total_qty }} pcs</strong> • Omzet: Rp {{ number_format($item->total_revenue, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                <div style="text-align: right; min-width: 70px;">
                    <div style="font-size: 0.8rem; font-weight: 700; color: #34d399;">
                        +Rp {{ number_format($item->total_profit, 0, ',', '.') }}
                    </div>
                    <div style="font-size: 0.65rem; color: var(--text-muted);">Profit</div>
                </div>
            </div>
            @empty
            <div style="text-align: center; color: var(--text-muted); font-size: 0.8rem; padding: 1.5rem;">
                Belum ada data penjualan tercatat.
            </div>
            @endforelse
        </div>
    </div>

    <!-- Recent Transactions Card -->
    <div class="card" style="margin-bottom: 1rem;">
        <div class="card-header" style="margin-bottom: 0.5rem;">
            <h2 class="card-title" style="font-size: 0.95rem;">🧾 Transaksi Terbaru</h2>
            <a href="{{ route('admin.sales') }}" style="font-size: 0.75rem; color: #60a5fa; text-decoration: none;">Semua ➔</a>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            @forelse($recentTransactions as $tx)
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.65rem; background: var(--bg-surface-elevated); border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); font-size: 0.8rem;">
                <div>
                    <div style="font-family: var(--font-mono); font-weight: 700; color: #60a5fa;">{{ $tx->invoice_number }}</div>
                    <div style="font-size: 0.7rem; color: var(--text-muted);">
                        {{ $tx->created_at->format('d/m/Y H:i') }} • Kasir: {{ $tx->user->name ?? 'Kasir' }} • {{ strtoupper($tx->payment_method) }}
                    </div>
                </div>
                <div style="text-align: right;">
                    <strong style="color: white; font-size: 0.9rem;">Rp {{ number_format($tx->total_amount, 0, ',', '.') }}</strong>
                    <div style="font-size: 0.7rem; color: #34d399;">Profit: Rp {{ number_format($tx->total_profit, 0, ',', '.') }}</div>
                </div>
            </div>
            @empty
            <div style="text-align: center; color: var(--text-muted); font-size: 0.8rem; padding: 1.5rem;">
                Belum ada transaksi.
            </div>
            @endforelse
        </div>
    </div>

</div>

<!-- Mobile Bottom Navigation Bar (Target: Smartphone Web) -->
<nav class="mobile-bottom-nav no-print">
    <a href="{{ route('admin.dashboard') }}" class="mobile-nav-item active">
        <span class="mobile-nav-icon">📊</span>
        <span>Dashboard</span>
    </a>
    <a href="{{ route('admin.sales') }}" class="mobile-nav-item">
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

@section('scripts')
<!-- Load Chart.js via reliable CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const labels = @json($chartLabels);
    const salesData = @json($chartSales);
    const profitData = @json($chartProfits);

    const ctx = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Omzet (Penjualan)',
                    data: salesData,
                    backgroundColor: 'rgba(59, 130, 246, 0.75)',
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: 'Profit Bersih',
                    data: profitData,
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderRadius: 6,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#94a3b8',
                        font: { size: 11, family: 'Plus Jakarta Sans' }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b', font: { size: 10 } }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: {
                        color: '#64748b',
                        font: { size: 10 },
                        callback: function(value) {
                            if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                            if (value >= 1000) return (value / 1000).toFixed(0) + 'k';
                            return value;
                        }
                    }
                }
            }
        }
    });
</script>
@endsection
