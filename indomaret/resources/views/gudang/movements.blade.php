@extends('layouts.app')

@section('title', 'Audit Trail Pergerakan Stok - RetailPOS')

@section('content')
<div class="app-container">

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                <a href="{{ route('gudang.index') }}" style="color: #60a5fa; text-decoration: none; font-size: 0.85rem;">← Kembali ke Master Gudang</a>
            </div>
            <h1 style="font-size: 1.5rem; font-weight: 800;">📋 Audit Trail & Log Pergerakan Stok</h1>
            <p style="color: var(--text-muted); font-size: 0.85rem;">Catatan historis seluruh aktivitas barang masuk (Inbound), penjualan kasir (Outbound), dan penyesuaian stok opname.</p>
        </div>

        <!-- Filter by Type -->
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('gudang.movements') }}" class="btn {{ empty($type) ? 'btn-primary' : 'btn-secondary' }} btn-sm">Semua Log</a>
            <a href="{{ route('gudang.movements', ['type' => 'in']) }}" class="btn {{ $type === 'in' ? 'btn-primary' : 'btn-secondary' }} btn-sm">📥 Masuk (Inbound)</a>
            <a href="{{ route('gudang.movements', ['type' => 'out']) }}" class="btn {{ $type === 'out' ? 'btn-primary' : 'btn-secondary' }} btn-sm">📤 Keluar (Kasir)</a>
            <a href="{{ route('gudang.movements', ['type' => 'correction']) }}" class="btn {{ $type === 'correction' ? 'btn-primary' : 'btn-secondary' }} btn-sm">⚖️ Koreksi Opname</a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu Kejadian</th>
                        <th>Barcode / SKU</th>
                        <th>Nama Produk</th>
                        <th>Tipe Pergerakan</th>
                        <th style="text-align: center;">Perubahan Stok</th>
                        <th>Petugas Pelaksana</th>
                        <th>Keterangan / Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                    <tr>
                        <td style="font-size: 0.85rem; color: var(--text-muted); white-space: nowrap;">
                            {{ $m->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td style="font-family: var(--font-mono); color: #60a5fa;">
                            {{ $m->product->sku ?? '-' }}
                        </td>
                        <td>
                            <strong style="color: white;">{{ $m->product->name ?? 'Produk Dihapus' }}</strong>
                        </td>
                        <td>
                            @if($m->type === 'in')
                                <span style="background: rgba(16, 185, 129, 0.2); color: #34d399; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">
                                    📥 BARANG MASUK
                                </span>
                            @elseif($m->type === 'out')
                                <span style="background: rgba(37, 99, 235, 0.2); color: #60a5fa; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">
                                    📤 PENJUALAN KASIR
                                </span>
                            @else
                                <span style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">
                                    ⚖️ KOREKSI OPNAME
                                </span>
                            @endif
                        </td>
                        <td style="text-align: center; font-weight: 800; font-size: 0.95rem; {{ $m->qty > 0 ? 'color: #34d399;' : 'color: #fb7185;' }}">
                            {{ $m->qty > 0 ? '+' . $m->qty : $m->qty }} pcs
                        </td>
                        <td style="font-size: 0.85rem; color: var(--text-secondary);">
                            {{ $m->user->name ?? 'Sistem' }} ({{ $m->user->role ?? '-' }})
                        </td>
                        <td style="font-size: 0.85rem; color: var(--text-muted);">
                            {{ $m->note ?: '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            Belum ada catatan pergerakan stok untuk filter ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem;">
            {{ $movements->links() }}
        </div>
    </div>

</div>
@endsection
