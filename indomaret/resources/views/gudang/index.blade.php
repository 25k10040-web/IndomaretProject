@extends('layouts.app')

@section('title', 'Modul Gudang & Inventaris - RetailPOS')

@section('content')
<div class="app-container">

    <!-- Header & Quick Actions -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; gap: 0.5rem;">
                <span>📦</span> Manajemen Inventaris & Master Gudang
            </h1>
            <p style="color: var(--text-muted); font-size: 0.85rem;">CRUD Master Produk, Penerimaan Barang Masuk (Inbound), & Koreksi Stok Opname</p>
        </div>

        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" onclick="openModal('addProductModal')">
                <span>➕ Tambah Master Produk</span>
            </button>
            <button type="button" class="btn btn-success" onclick="openModal('inboundModal')">
                <span>📥 Barang Masuk (Inbound)</span>
            </button>
            <button type="button" class="btn btn-secondary" style="border-color: rgba(245, 158, 11, 0.4); color: #fbbf24;" onclick="openModal('correctionModal')">
                <span>⚖️ Koreksi Stok Opname</span>
            </button>
            <a href="{{ route('gudang.movements') }}" class="btn btn-secondary">
                <span>📋 Audit Trail Log</span>
            </a>
        </div>
    </div>

    <!-- Warehouse KPI Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card" style="--stat-glow: rgba(59, 130, 246, 0.2);">
            <div class="stat-label">Total Master SKU</div>
            <div class="stat-value">{{ number_format($totalProducts) }} <span style="font-size: 1rem; color: var(--text-muted);">item</span></div>
            <div class="stat-sub">Katalog produk aktif</div>
        </div>

        <div class="stat-card" style="--stat-glow: rgba(16, 185, 129, 0.2);">
            <div class="stat-label">Total Unit Fisik</div>
            <div class="stat-value">{{ number_format($totalStockUnits) }} <span style="font-size: 1rem; color: var(--text-muted);">pcs</span></div>
            <div class="stat-sub">Stok fisik di rak & gudang</div>
        </div>

        <div class="stat-card" style="--stat-glow: rgba(245, 158, 11, 0.25);">
            <div class="stat-label">Stok Menipis (≤ 5)</div>
            <div class="stat-value" style="color: #fbbf24;">{{ $lowStockCount }} <span style="font-size: 1rem; color: var(--text-muted);">SKU</span></div>
            <div class="stat-sub" style="color: #fbbf24;">Segera ajukan PO supplier</div>
        </div>

        <div class="stat-card" style="--stat-glow: rgba(244, 63, 94, 0.25);">
            <div class="stat-label">Stok Habis (0)</div>
            <div class="stat-value" style="color: #fb7185;">{{ $outOfStockCount }} <span style="font-size: 1rem; color: var(--text-muted);">SKU</span></div>
            <div class="stat-sub" style="color: #fb7185;">Tidak bisa dijual kasir</div>
        </div>

        <div class="stat-card" style="--stat-glow: rgba(99, 102, 241, 0.2);">
            <div class="stat-label">Total Nilai Aset HPP</div>
            <div class="stat-value" style="font-size: 1.45rem; color: #a5b4fc;">Rp {{ number_format($totalAssetValue, 0, ',', '.') }}</div>
            <div class="stat-sub">Valuasi modal persediaan</div>
        </div>
    </div>

    <!-- Master Catalog Table Card -->
    <div class="card" style="margin-bottom: 2rem;">
        
        <div class="card-header" style="flex-wrap: wrap; gap: 1rem;">
            <h2 class="card-title">Katalog Master Barang ({{ $products->total() }})</h2>

            <!-- Search and Filter Form -->
            <form action="{{ route('gudang.index') }}" method="GET" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <input type="text" name="q" class="form-control" placeholder="Cari SKU atau nama..." value="{{ $search }}" style="width: 240px; padding: 0.5rem 0.75rem; font-size: 0.85rem;">
                
                <select name="filter" class="form-control" style="width: 170px; padding: 0.5rem 0.75rem; font-size: 0.85rem;" onchange="this.form.submit()">
                    <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="low_stock" {{ $filter === 'low_stock' ? 'selected' : '' }}>⚠️ Stok Menipis (≤5)</option>
                    <option value="out_of_stock" {{ $filter === 'out_of_stock' ? 'selected' : '' }}>🚨 Stok Habis (0)</option>
                </select>

                <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
                @if($search || $filter !== 'all')
                    <a href="{{ route('gudang.index') }}" class="btn btn-secondary btn-sm" style="color: var(--text-muted);">Reset</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Barcode / SKU</th>
                        <th>Nama Produk</th>
                        <th style="text-align: right;">Harga Beli (Modal)</th>
                        <th style="text-align: right;">Harga Jual</th>
                        <th style="text-align: center;">Margin</th>
                        <th style="text-align: center;">Stok</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                    <tr>
                        <td style="font-family: var(--font-mono); font-weight: 600; color: #60a5fa;">
                            {{ $p->sku }}
                        </td>
                        <td>
                            <strong style="color: white;">{{ $p->name }}</strong>
                        </td>
                        <td style="text-align: right; color: var(--text-secondary);">
                            Rp {{ number_format($p->purchase_price, 0, ',', '.') }}
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #34d399;">
                            Rp {{ number_format($p->selling_price, 0, ',', '.') }}
                        </td>
                        <td style="text-align: center;">
                            <span style="font-size: 0.75rem; padding: 2px 6px; border-radius: 4px; background: rgba(99, 102, 241, 0.15); color: #a5b4fc;">
                                +{{ $p->margin }}%
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <span class="prod-stock-pill {{ $p->stock <= 0 ? 'out' : ($p->stock <= 5 ? 'low' : '') }}" style="font-size: 0.8rem; font-weight: 700; padding: 4px 10px;">
                                {{ $p->stock }} pcs
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 0.4rem; justify-content: center;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick='openEditModal(@json($p))' title="Edit Harga/SKU">
                                    ✏️ Edit
                                </button>
                                <form action="{{ route('gudang.product.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk {{ $p->name }} dari katalog master?');" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus Produk">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            Tidak ada produk yang cocok dengan pencarian / filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem;">
            {{ $products->links() }}
        </div>

    </div>

    <!-- Recent Movements Preview -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Aktivitas Terakhir Pergerakan Stok Gudang</h3>
            <a href="{{ route('gudang.movements') }}" style="color: #60a5fa; font-size: 0.85rem; text-decoration: none;">Lihat Semua ➔</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Produk</th>
                        <th>Jenis</th>
                        <th style="text-align: center;">Perubahan Qty</th>
                        <th>Petugas</th>
                        <th>Keterangan / Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentMovements as $m)
                    <tr>
                        <td style="font-size: 0.8rem; color: var(--text-muted);">
                            {{ $m->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td>
                            <strong>{{ $m->product->name ?? 'Produk Dihapus' }}</strong>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $m->product->sku ?? '' }}</div>
                        </td>
                        <td>
                            @if($m->type === 'in')
                                <span style="background: rgba(16, 185, 129, 0.2); color: #34d399; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">INBOUND</span>
                            @elseif($m->type === 'out')
                                <span style="background: rgba(37, 99, 235, 0.2); color: #60a5fa; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">OUT (KASIR)</span>
                            @else
                                <span style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">KOREKSI</span>
                            @endif
                        </td>
                        <td style="text-align: center; font-weight: 700; {{ $m->qty > 0 ? 'color: #34d399;' : 'color: #fb7185;' }}">
                            {{ $m->qty > 0 ? '+' . $m->qty : $m->qty }} pcs
                        </td>
                        <td style="color: var(--text-secondary); font-size: 0.85rem;">
                            {{ $m->user->name ?? 'Sistem' }}
                        </td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;">
                            {{ $m->note ?: '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL 1: Tambah Master Produk -->
<div class="modal-overlay" id="addProductModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">➕ Tambah Master Produk Baru</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('addProductModal')">✕</button>
        </div>
        <form action="{{ route('gudang.product.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="add_sku">Barcode / SKU Produk *</label>
                    <input type="text" id="add_sku" name="sku" class="form-control" placeholder="Contoh: 8991234567890" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="add_name">Nama Lengkap Produk *</label>
                    <input type="text" id="add_name" name="name" class="form-control" placeholder="Contoh: Kopi Kapal Api Spesial Mix 10x24g" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <div class="form-group">
                        <label class="form-label" for="add_purchase_price">Harga Beli Modal (Rp) *</label>
                        <input type="number" id="add_purchase_price" name="purchase_price" class="form-control" placeholder="0" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_selling_price">Harga Jual Kasir (Rp) *</label>
                        <input type="number" id="add_selling_price" name="selling_price" class="form-control" placeholder="0" min="0" required>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <div class="form-group">
                        <label class="form-label" for="add_stock">Stok Awal Fisik *</label>
                        <input type="number" id="add_stock" name="stock" class="form-control" placeholder="0" min="0" value="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_note">Catatan Pengadaan</label>
                        <input type="text" id="add_note" name="note" class="form-control" placeholder="Supplier / PO #">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addProductModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Master Produk</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: Edit Master Produk -->
<div class="modal-overlay" id="editProductModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">✏️ Edit Data Master Produk</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('editProductModal')">✕</button>
        </div>
        <form id="editProductForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="edit_sku">Barcode / SKU Produk *</label>
                    <input type="text" id="edit_sku" name="sku" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_name">Nama Lengkap Produk *</label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <div class="form-group">
                        <label class="form-label" for="edit_purchase_price">Harga Beli Modal (Rp) *</label>
                        <input type="number" id="edit_purchase_price" name="purchase_price" class="form-control" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_selling_price">Harga Jual Kasir (Rp) *</label>
                        <input type="number" id="edit_selling_price" name="selling_price" class="form-control" min="0" required>
                    </div>
                </div>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">
                    ℹ️ Untuk mengubah jumlah stok barang fisik, gunakan menu <strong>Barang Masuk</strong> atau <strong>Koreksi Stok</strong> agar tercatat dalam audit trail.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editProductModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: Penerimaan Barang Masuk (Inbound) -->
<div class="modal-overlay" id="inboundModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">📥 Penerimaan Barang Masuk (Inbound)</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('inboundModal')">✕</button>
        </div>
        <form action="{{ route('gudang.inbound') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="inbound_product_id">Pilih Produk Dari Katalog *</label>
                    <select id="inbound_product_id" name="product_id" class="form-control" required>
                        <option value="">-- Pilih Produk / SKU --</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->sku }} - {{ $p->name }} (Sisa Stok: {{ $p->stock }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="inbound_qty">Jumlah Barang Masuk (Qty) *</label>
                    <input type="number" id="inbound_qty" name="qty" class="form-control" placeholder="Contoh: 24" min="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="inbound_note">Keterangan / Supplier / No. Surat Jalan *</label>
                    <input type="text" id="inbound_note" name="note" class="form-control" placeholder="Contoh: Pengiriman PO-883 dari PT Indomarco" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('inboundModal')">Batal</button>
                <button type="submit" class="btn btn-success">Konfirmasi Barang Masuk</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 4: Koreksi Stok / Stock Opname -->
<div class="modal-overlay" id="correctionModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">⚖️ Penyesuaian Stok (Stock Opname)</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('correctionModal')">✕</button>
        </div>
        <form action="{{ route('gudang.correction') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="corr_product_id">Pilih Produk *</label>
                    <select id="corr_product_id" name="product_id" class="form-control" required>
                        <option value="">-- Pilih Produk / SKU --</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->sku }} - {{ $p->name }} (Stok Sistem: {{ $p->stock }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="corr_qty">Jumlah Penyesuaian (+ atau -) *</label>
                    <input type="number" id="corr_qty" name="qty" class="form-control" placeholder="Contoh: -2 jika rusak/hilang, atau +5 jika ada kelebihan" required>
                    <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 2px;">
                        Gunakan angka minus (-) untuk mengurangi stok, angka positif (+) untuk menambah.
                    </small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="corr_reason">Alasan Penyesuaian *</label>
                    <select id="corr_reason" name="reason" class="form-control" required>
                        <option value="rusak">Barang Rusak / Kemasan Bocor</option>
                        <option value="kadaluwarsa">Barang Kadaluwarsa (Expired)</option>
                        <option value="hilang">Barang Hilang / Selisih Fisik</option>
                        <option value="selisih_hitung">Koreksi Perhitungan Opname Bulanan</option>
                        <option value="retur_supplier">Retur Pengembalian ke Supplier</option>
                        <option value="lainnya">Alasan Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="corr_note">Catatan Tambahan</label>
                    <input type="text" id="corr_note" name="note" class="form-control" placeholder="Contoh: Ditemukan kemasan penyok di lorong 3">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('correctionModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Koreksi Stok</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    function openEditModal(product) {
        document.getElementById('edit_sku').value = product.sku;
        document.getElementById('edit_name').value = product.name;
        document.getElementById('edit_purchase_price').value = parseInt(product.purchase_price);
        document.getElementById('edit_selling_price').value = parseInt(product.selling_price);

        const form = document.getElementById('editProductForm');
        form.action = `/gudang/product/${product.id}`;

        openModal('editProductModal');
    }
</script>
@endsection
