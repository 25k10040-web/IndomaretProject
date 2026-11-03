@extends('layouts.app')

@section('title', 'Login - RetailPOS Toko Retail')

@section('content')
<div style="min-height: calc(100vh - 120px); display: flex; align-items: center; justify-content: center; padding: 2rem 1rem;">
    <div style="width: 100%; max-width: 480px;">
        
        <!-- App Title Header -->
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #2563eb, #3b82f6); border-radius: 18px; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; font-size: 2rem; box-shadow: var(--shadow-glow-blue);">
                🛒
            </div>
            <h1 style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 0.25rem;">RetailPOS System</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Sistem Point of Sales & Inventaris Terintegrasi</p>
        </div>

        <!-- Login Card -->
        <div class="card" style="padding: 2rem; margin-bottom: 1.5rem; border-color: rgba(59, 130, 246, 0.3);">
            <div class="card-header" style="padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
                <h2 class="card-title" style="font-size: 1.15rem;">Masuk ke Akun Anda</h2>
            </div>

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Contoh: kasir / gudang / admin" value="{{ old('username') }}" required autofocus>
                    @error('username')
                        <span style="color: #fb7185; font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; font-size: 0.85rem; color: var(--text-secondary);">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="remember" style="accent-color: var(--brand-primary);">
                        Ingat Saya
                    </label>
                    <span style="color: var(--text-muted); font-size: 0.75rem;">Default: password</span>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 0.5rem;">
                    <span>Masuk ke Sistem</span> ➔
                </button>
            </form>
        </div>

        <!-- Quick 1-Click Role Login for Quick Testing / Evaluation -->
        <div class="card" style="background: rgba(17, 24, 39, 0.6); padding: 1.5rem;">
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.85rem; text-align: center;">
                ⚡ Demo 1-Click Login (Pilih Peran)
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <!-- Kasir -->
                <a href="{{ route('quick.login', 'ukasir') }}" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.85rem 1rem; border-color: rgba(59, 130, 246, 0.3);">
                    <div style="width: 34px; height: 34px; background: rgba(37, 99, 235, 0.2); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; margin-right: 0.5rem;">
                        🛒
                    </div>
                    <div style="text-align: left; line-height: 1.2; flex: 1;">
                        <div style="font-weight: 700; color: white;">UKasir (Kasir Toko)</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">POS Scan Barcode, Keranjang, Cetak Struk</div>
                    </div>
                    <span style="color: #60a5fa; font-weight: 700;">Masuk ➔</span>
                </a>

                <!-- Gudang -->
                <a href="{{ route('quick.login', 'ugudang') }}" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.85rem 1rem; border-color: rgba(245, 158, 11, 0.3);">
                    <div style="width: 34px; height: 34px; background: rgba(245, 158, 11, 0.2); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; margin-right: 0.5rem;">
                        📦
                    </div>
                    <div style="text-align: left; line-height: 1.2; flex: 1;">
                        <div style="font-weight: 700; color: white;">UGudang (Staff Gudang)</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Master Barang, Barang Masuk, Koreksi Stok</div>
                    </div>
                    <span style="color: #fbbf24; font-weight: 700;">Masuk ➔</span>
                </a>

                <!-- Admin -->
                <a href="{{ route('quick.login', 'uadmin') }}" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.85rem 1rem; border-color: rgba(16, 185, 129, 0.3);">
                    <div style="width: 34px; height: 34px; background: rgba(16, 185, 129, 0.2); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; margin-right: 0.5rem;">
                        📊
                    </div>
                    <div style="text-align: left; line-height: 1.2; flex: 1;">
                        <div style="font-weight: 700; color: white;">UAdmin (Manajemen / Owner)</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Dashboard Analitik, Laporan Profit (Read-Only)</div>
                    </div>
                    <span style="color: #34d399; font-weight: 700;">Masuk ➔</span>
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
