<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RetailPOS - Sistem Toko Retail')</title>
    
    <!-- Design System CSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    
    @yield('styles')
</head>
<body class="@yield('body-class')">

    @auth
    <!-- Global Header & Navigation -->
    <header class="top-navbar no-print">
        <div class="navbar-inner">
            <a href="/" class="brand-wrapper">
                <div class="brand-logo">🛒</div>
                <div class="brand-text">
                    <h1>RetailPOS <span class="nav-role-badge role-{{ Auth::user()->role }}">{{ strtoupper(Auth::user()->role) }}</span></h1>
                    <p>Sistem POS & Inventaris Terintegrasi</p>
                </div>
            </a>

            <div class="nav-actions">
                <!-- Role Switcher for Developer / Evaluator convenience -->
                <div class="role-switcher-dropdown">
                    <span>Ganti Modul:</span>
                    <a href="{{ route('quick.login', 'ukasir') }}" class="role-link {{ Auth::user()->isKasir() ? 'active' : '' }}" title="Modul Kasir">Kasir</a>
                    <a href="{{ route('quick.login', 'ugudang') }}" class="role-link {{ Auth::user()->isGudang() ? 'active' : '' }}" title="Modul Gudang">Gudang</a>
                    <a href="{{ route('quick.login', 'uadmin') }}" class="role-link {{ Auth::user()->isAdmin() ? 'active' : '' }}" title="Modul Admin">Admin</a>
                </div>

                <!-- User Profile & Logout -->
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="text-align: right; line-height: 1.2;">
                        <div style="font-weight: 700; font-size: 0.85rem;">{{ Auth::user()->name }}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">{{ '@' . Auth::user()->username }}</div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm" title="Keluar">
                            <span>🚪</span> Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>
    @endauth

    <!-- Flash Messages Container -->
    <main class="flash-container no-print" style="max-width: 1600px; margin: 1rem auto 0; padding: 0 1.5rem; width: 100%;">
        @if(session('success'))
            <div class="flash-message flash-success">
                <span>✅ {{ session('success') }}</span>
                <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:inherit; cursor:pointer;">✕</button>
            </div>
        @endif

        @if(session('error'))
            <div class="flash-message flash-error">
                <span>❌ {{ session('error') }}</span>
                <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:inherit; cursor:pointer;">✕</button>
            </div>
        @endif

        @if(session('warning'))
            <div class="flash-message flash-warning">
                <span>⚠️ {{ session('warning') }}</span>
                <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:inherit; cursor:pointer;">✕</button>
            </div>
        @endif
    </main>

    <!-- Main Content -->
    @yield('content')

    <!-- Beep Sound Synthesizer via Web Audio API -->
    <script>
        window.playBeep = function() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const audioCtx = new AudioCtx();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(1850, audioCtx.currentTime); // Realistic barcode scanner beep
                gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.1);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.1);
            } catch (e) {
                console.warn('Audio feedback failed:', e);
            }
        };

        window.formatRupiah = function(number) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
        };
    </script>

    @yield('scripts')
</body>
</html>
