@extends('layouts.app')

@section('title', 'POS Kasir - RetailPOS')

@section('content')
<div class="app-container" style="padding-top: 1rem;">

    <!-- Top Summary Pills -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div style="font-size: 1.25rem; font-weight: 800; display: flex; align-items: center; gap: 0.5rem;">
                <span>🛒</span> Kasir Terminal #01
            </div>
            <span class="nav-role-badge role-ukasir">ONLINE</span>
            <span style="font-size: 0.8rem; color: var(--text-muted);">
                Pintasan: <kbd style="background:#222; padding:2px 6px; border-radius:4px;">F2</kbd> Scan | <kbd style="background:#222; padding:2px 6px; border-radius:4px;">F4</kbd> Reset | <kbd style="background:#222; padding:2px 6px; border-radius:4px;">F8</kbd> Bayar
            </span>
        </div>

        <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.85rem;">
            <div style="background: var(--bg-surface-elevated); padding: 0.4rem 0.85rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                <span style="color: var(--text-muted);">Transaksi Hari Ini:</span>
                <strong style="color: white; margin-left: 0.35rem;" id="todayTxCount">{{ $todayCount }}</strong>
            </div>
            <div style="background: var(--bg-surface-elevated); padding: 0.4rem 0.85rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                <span style="color: var(--text-muted);">Omzet Anda Hari Ini:</span>
                <strong style="color: #34d399; margin-left: 0.35rem;" id="todayTxTotal">Rp {{ number_format($todayTotal, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    <!-- Main POS Grid Layout -->
    <div class="pos-layout">

        <!-- Left Column: Barcode Scanner & Product Catalog -->
        <div class="pos-catalog-panel">
            
            <!-- Barcode / QR Scanner Input Box -->
            <div class="pos-scanner-box">
                <span class="barcode-icon">║▌║█║▌│</span>
                <input type="text" id="barcodeInput" class="scanner-input" placeholder="Scan Barcode / QR Code atau ketik nama produk lalu tekan Enter..." autocomplete="off" autofocus>
                <button type="button" class="btn btn-secondary btn-sm" id="btnSearchFilter" title="Cari produk">
                    🔍 Cari
                </button>
            </div>

            <!-- Category / Fast Filter Chips -->
            <div style="display: flex; gap: 0.5rem; overflow-x: auto; padding-bottom: 0.25rem;">
                <button type="button" class="preset-chip active" onclick="filterCatalog('all', this)">Semua Barang ({{ $products->count() }})</button>
                <button type="button" class="preset-chip" onclick="filterCatalog('makanan', this)">Makanan & Mie</button>
                <button type="button" class="preset-chip" onclick="filterCatalog('minuman', this)">Minuman</button>
                <button type="button" class="preset-chip" onclick="filterCatalog('perawatan', this)">Perawatan & Sabun</button>
            </div>

            <!-- Product Items Grid -->
            <div class="pos-product-grid" id="posProductGrid">
                @foreach($products as $product)
                <div class="product-pos-item" 
                     data-id="{{ $product->id }}"
                     data-sku="{{ $product->sku }}"
                     data-name="{{ $product->name }}"
                     data-price="{{ $product->selling_price }}"
                     data-stock="{{ $product->stock }}"
                     onclick="handleProductClick({{ $product->id }})">
                    
                    <div>
                        <div class="prod-sku">SKU: {{ $product->sku }}</div>
                        <div class="prod-name">{{ $product->name }}</div>
                    </div>

                    <div class="prod-footer">
                        <div class="prod-price">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</div>
                        <span class="prod-stock-pill {{ $product->stock <= 0 ? 'out' : ($product->stock <= 5 ? 'low' : '') }}">
                            {{ $product->stock <= 0 ? 'Habis' : 'Stok: ' . $product->stock }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>

        </div>

        <!-- Right Column: Live Cart & Instant Checkout -->
        <div class="pos-cart-panel">
            
            <!-- Cart Header -->
            <div class="cart-header">
                <div style="font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>🧾</span> Keranjang Belanja
                    <span id="cartBadgeCount" style="font-size: 0.75rem; background: var(--brand-primary); color: white; padding: 2px 7px; border-radius: var(--radius-full);">0</span>
                </div>
                <button type="button" class="btn btn-danger btn-sm" onclick="clearCart()" title="Kosongkan (F4)">
                    🗑️ Reset (F4)
                </button>
            </div>

            <!-- Cart Items List Container -->
            <div class="cart-items-wrapper" id="cartItemsList">
                <div class="cart-empty-state" id="cartEmptyState">
                    <span style="font-size: 2.5rem;">🛒</span>
                    <strong style="color: var(--text-main);">Keranjang Masih Kosong</strong>
                    <p style="font-size: 0.8rem;">Silakan scan barcode barang atau klik produk di sisi kiri untuk memulai.</p>
                </div>
            </div>

            <!-- Cart Summary & Payment -->
            <div class="cart-summary-section">
                
                <div class="summary-row">
                    <span>Total Item:</span>
                    <strong id="summaryTotalItems" style="color: white;">0 pcs</strong>
                </div>

                <div class="summary-row grand-total">
                    <span>TOTAL BAYAR:</span>
                    <span class="total-glow" id="summaryGrandTotal">Rp 0</span>
                </div>

                <!-- Payment Method Selector -->
                <div style="margin-top: 0.25rem;">
                    <label class="form-label" style="font-size: 0.75rem;">Metode Pembayaran</label>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.35rem; margin-top: 0.25rem;">
                        <button type="button" class="preset-chip active" id="btnMethodCash" onclick="selectPaymentMethod('cash')">💵 Tunai</button>
                        <button type="button" class="preset-chip" id="btnMethodQris" onclick="selectPaymentMethod('qris')">📱 QRIS</button>
                        <button type="button" class="preset-chip" id="btnMethodDebit" onclick="selectPaymentMethod('debit')">💳 Debit</button>
                        <button type="button" class="preset-chip" id="btnMethodTransfer" onclick="selectPaymentMethod('transfer')">🏦 Transfer</button>
                    </div>
                </div>

                <!-- Cash Payment Preset Chips -->
                <div id="cashPresetsSection">
                    <div class="preset-chips-grid">
                        <button type="button" class="preset-chip" onclick="setCashPreset('exact')">Uang Pas</button>
                        <button type="button" class="preset-chip" onclick="setCashPreset(10000)">10.000</button>
                        <button type="button" class="preset-chip" onclick="setCashPreset(20000)">20.000</button>
                        <button type="button" class="preset-chip" onclick="setCashPreset(50000)">50.000</button>
                        <button type="button" class="preset-chip" onclick="setCashPreset(100000)">100.000</button>
                        <button type="button" class="preset-chip" onclick="setCashPreset(200000)">200.000</button>
                    </div>
                </div>

                <!-- Paid Amount Input & Change Display -->
                <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 0.75rem; align-items: end;">
                    <div>
                        <label class="form-label" for="paidAmountInput" style="font-size: 0.75rem;">Uang Diterima (Rp)</label>
                        <input type="number" id="paidAmountInput" class="form-control" placeholder="0" style="font-size: 1.1rem; font-weight: 700; color: #60a5fa;" oninput="calculateChange()">
                    </div>
                    <div>
                        <div class="form-label" style="font-size: 0.75rem;">Kembalian:</div>
                        <div id="changeDisplay" style="font-size: 1.15rem; font-weight: 800; color: #34d399; padding: 0.65rem 0.5rem; text-align: right; background: rgba(0,0,0,0.2); border-radius: var(--radius-md);">
                            Rp 0
                        </div>
                    </div>
                </div>

                <!-- Checkout Button -->
                <button type="button" class="btn btn-success btn-block btn-lg" id="btnCheckout" onclick="processCheckout()" style="margin-top: 0.35rem; font-size: 1.05rem;">
                    <span>⚡ PROSES BAYAR (F8)</span>
                </button>

            </div>

        </div>

    </div>

</div>

<!-- Modal: Sukses Transaksi & Cetak Struk -->
<div class="modal-overlay" id="receiptModal">
    <div class="modal-container" style="max-width: 440px;">
        <div class="modal-header">
            <h3 class="modal-title" style="color: #34d399; display: flex; align-items: center; gap: 0.5rem;">
                <span>✅</span> Transaksi Sukses!
            </h3>
            <button type="button" class="modal-close-btn" onclick="closeReceiptModal()">✕</button>
        </div>

        <div class="modal-body" style="padding: 1rem; background: #0f172a;">
            <!-- Thermal Receipt Preview -->
            <div id="receiptPreviewContainer">
                <!-- Injected via JS -->
            </div>
        </div>

        <div class="modal-footer" style="justify-content: space-between;">
            <button type="button" class="btn btn-secondary" onclick="closeReceiptModal()">
                ✕ Tutup / Layar Baru
            </button>
            <button type="button" class="btn btn-primary" id="btnPrintReceipt" onclick="printReceiptDirect()">
                🖨️ Cetak Struk (Print)
            </button>
        </div>
    </div>
</div>

<!-- Hidden Iframe for silent printing -->
<iframe id="printFrame" style="display: none;"></iframe>
@endsection

@section('scripts')
<script>
    // Product data cache from PHP
    const productsMaster = @json($products);
    let cart = []; // items: { product_id, sku, name, price, stock, qty, subtotal }
    let selectedPayment = 'cash';
    let currentReceiptUrl = '';

    // Elements
    const barcodeInput = document.getElementById('barcodeInput');
    const cartItemsList = document.getElementById('cartItemsList');
    const cartEmptyState = document.getElementById('cartEmptyState');
    const summaryTotalItems = document.getElementById('summaryTotalItems');
    const summaryGrandTotal = document.getElementById('summaryGrandTotal');
    const cartBadgeCount = document.getElementById('cartBadgeCount');
    const paidAmountInput = document.getElementById('paidAmountInput');
    const changeDisplay = document.getElementById('changeDisplay');
    const cashPresetsSection = document.getElementById('cashPresetsSection');

    // 1. Barcode Scanner Handler
    barcodeInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = barcodeInput.value.trim();
            if (!query) return;

            // Find matching product (exact SKU or name)
            const matched = productsMaster.find(p => p.sku.toLowerCase() === query.toLowerCase()) ||
                            productsMaster.find(p => p.name.toLowerCase().includes(query.toLowerCase()));

            if (matched) {
                addToCart(matched);
                window.playBeep();
                barcodeInput.value = '';
            } else {
                alert('Produk dengan SKU / nama "' + query + '" tidak ditemukan!');
                barcodeInput.select();
            }
        }
    });

    // 2. Global Keyboard Shortcuts (F2, F4, F8)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F2') {
            e.preventDefault();
            barcodeInput.focus();
            barcodeInput.select();
        } else if (e.key === 'F4') {
            e.preventDefault();
            clearCart();
        } else if (e.key === 'F8') {
            e.preventDefault();
            processCheckout();
        }
    });

    // 3. Product Click Handler
    function handleProductClick(productId) {
        const product = productsMaster.find(p => p.id === productId);
        if (product) {
            addToCart(product);
            window.playBeep();
        }
    }

    // 4. Cart Logic
    function addToCart(product) {
        if (product.stock <= 0) {
            alert('Perhatian: Stok produk "' + product.name + '" sudah HABIS di sistem!');
            return;
        }

        const existingIndex = cart.findIndex(item => item.product_id === product.id);

        if (existingIndex > -1) {
            if (cart[existingIndex].qty >= product.stock) {
                alert('Tidak bisa menambah lebih banyak. Sisa stok tersedia hanya: ' + product.stock);
                return;
            }
            cart[existingIndex].qty += 1;
            cart[existingIndex].subtotal = cart[existingIndex].qty * cart[existingIndex].price;
        } else {
            cart.push({
                product_id: product.id,
                sku: product.sku,
                name: product.name,
                price: parseFloat(product.selling_price),
                stock: product.stock,
                qty: 1,
                subtotal: parseFloat(product.selling_price)
            });
        }

        renderCart();
    }

    function updateCartQty(productId, delta) {
        const index = cart.findIndex(item => item.product_id === productId);
        if (index === -1) return;

        const newQty = cart[index].qty + delta;
        if (newQty <= 0) {
            removeFromCart(productId);
            return;
        }

        if (newQty > cart[index].stock) {
            alert('Jumlah melebihi stok yang tersedia (' + cart[index].stock + ')');
            return;
        }

        cart[index].qty = newQty;
        cart[index].subtotal = cart[index].qty * cart[index].price;
        renderCart();
    }

    function setDirectQty(productId, inputEl) {
        const index = cart.findIndex(item => item.product_id === productId);
        if (index === -1) return;

        let val = parseInt(inputEl.value) || 1;
        if (val <= 0) val = 1;
        if (val > cart[index].stock) {
            alert('Jumlah melebihi stok yang tersedia (' + cart[index].stock + ')');
            val = cart[index].stock;
        }

        cart[index].qty = val;
        cart[index].subtotal = cart[index].qty * cart[index].price;
        renderCart();
    }

    function removeFromCart(productId) {
        cart = cart.filter(item => item.product_id !== productId);
        renderCart();
    }

    function clearCart() {
        if (cart.length > 0) {
            if (confirm('Kosongkan keranjang belanja kasir saat ini?')) {
                cart = [];
                renderCart();
                barcodeInput.focus();
            }
        }
    }

    // 5. Render Cart UI
    function renderCart() {
        cartItemsList.innerHTML = '';

        if (cart.length === 0) {
            cartEmptyState.style.display = 'flex';
            cartItemsList.appendChild(cartEmptyState);
            summaryTotalItems.textContent = '0 pcs';
            summaryGrandTotal.textContent = 'Rp 0';
            cartBadgeCount.textContent = '0';
            paidAmountInput.value = '';
            changeDisplay.textContent = 'Rp 0';
            return;
        }

        cartEmptyState.style.display = 'none';

        let totalQty = 0;
        let grandTotal = 0;

        cart.forEach(item => {
            totalQty += item.qty;
            grandTotal += item.subtotal;

            const row = document.createElement('div');
            row.className = 'cart-item-row';
            row.innerHTML = `
                <div class="cart-item-info">
                    <div class="cart-item-name" title="${item.name}">${item.name}</div>
                    <div class="cart-item-price">${item.sku} • Rp ${new Intl.NumberFormat('id-ID').format(item.price)}</div>
                </div>
                <div class="cart-qty-control">
                    <button type="button" class="qty-btn" onclick="updateCartQty(${item.product_id}, -1)">-</button>
                    <input type="text" class="qty-input" value="${item.qty}" onchange="setDirectQty(${item.product_id}, this)">
                    <button type="button" class="qty-btn" onclick="updateCartQty(${item.product_id}, 1)">+</button>
                </div>
                <div class="cart-item-subtotal">Rp ${new Intl.NumberFormat('id-ID').format(item.subtotal)}</div>
                <button type="button" class="cart-remove-btn" onclick="removeFromCart(${item.product_id})" title="Hapus">✕</button>
            `;
            cartItemsList.appendChild(row);
        });

        summaryTotalItems.textContent = totalQty + ' pcs';
        summaryGrandTotal.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal);
        cartBadgeCount.textContent = totalQty;

        // Auto update change
        calculateChange();
    }

    // 6. Payment Selection & Calculation
    function selectPaymentMethod(method) {
        selectedPayment = method;
        ['btnMethodCash', 'btnMethodQris', 'btnMethodDebit', 'btnMethodTransfer'].forEach(id => {
            document.getElementById(id).classList.remove('active');
        });

        if (method === 'cash') {
            document.getElementById('btnMethodCash').classList.add('active');
            cashPresetsSection.style.display = 'block';
        } else {
            const btnId = 'btnMethod' + method.charAt(0).toUpperCase() + method.slice(1);
            if (document.getElementById(btnId)) {
                document.getElementById(btnId).classList.add('active');
            }
            cashPresetsSection.style.display = 'none';
            // Non-cash defaults to exact amount
            const total = getCartTotal();
            paidAmountInput.value = total;
            calculateChange();
        }
    }

    function getCartTotal() {
        return cart.reduce((sum, item) => sum + item.subtotal, 0);
    }

    function setCashPreset(amount) {
        const total = getCartTotal();
        if (amount === 'exact') {
            paidAmountInput.value = total;
        } else {
            paidAmountInput.value = amount;
        }
        calculateChange();
    }

    function calculateChange() {
        const total = getCartTotal();
        const paid = parseFloat(paidAmountInput.value) || 0;
        const change = paid - total;

        if (change >= 0) {
            changeDisplay.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(change);
            changeDisplay.style.color = '#34d399';
        } else {
            changeDisplay.textContent = 'Kurang: Rp ' + new Intl.NumberFormat('id-ID').format(Math.abs(change));
            changeDisplay.style.color = '#fb7185';
        }
    }

    // 7. Checkout Process
    async function processCheckout() {
        if (cart.length === 0) {
            alert('Keranjang belanja kasir masih kosong!');
            barcodeInput.focus();
            return;
        }

        const total = getCartTotal();
        const paid = parseFloat(paidAmountInput.value) || 0;

        if (paid < total) {
            alert('Jumlah uang pembayaran kurang! Total: Rp ' + new Intl.NumberFormat('id-ID').format(total) + ', Pembayaran: Rp ' + new Intl.NumberFormat('id-ID').format(paid));
            paidAmountInput.focus();
            return;
        }

        const btnCheckout = document.getElementById('btnCheckout');
        btnCheckout.disabled = true;
        btnCheckout.innerHTML = '<span>⏳ Memproses...</span>';

        try {
            const response = await fetch("{{ route('pos.checkout') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    items: cart.map(item => ({ product_id: item.product_id, qty: item.qty })),
                    payment_method: selectedPayment,
                    paid_amount: paid
                })
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                alert(result.message || 'Gagal memproses transaksi.');
                btnCheckout.disabled = false;
                btnCheckout.innerHTML = '<span>⚡ PROSES BAYAR (F8)</span>';
                return;
            }

            // Update today counter in UI
            document.getElementById('todayTxCount').textContent = parseInt(document.getElementById('todayTxCount').textContent) + 1;

            // Render Receipt Preview in modal
            showReceiptModal(result.transaction, result.receipt_url);

            // Update master stock in client memory
            cart.forEach(cItem => {
                const p = productsMaster.find(prod => prod.id === cItem.product_id);
                if (p) {
                    p.stock -= cItem.qty;
                    // update badge on grid
                    const gridItem = document.querySelector(`.product-pos-item[data-id="${p.id}"] .prod-stock-pill`);
                    if (gridItem) {
                        gridItem.textContent = p.stock <= 0 ? 'Habis' : 'Stok: ' + p.stock;
                        if (p.stock <= 0) gridItem.className = 'prod-stock-pill out';
                        else if (p.stock <= 5) gridItem.className = 'prod-stock-pill low';
                    }
                }
            });

            // Clean cart for next customer
            cart = [];
            renderCart();

        } catch (err) {
            console.error(err);
            alert('Terjadi kesalahan jaringan atau server saat memproses transaksi.');
        } finally {
            btnCheckout.disabled = false;
            btnCheckout.innerHTML = '<span>⚡ PROSES BAYAR (F8)</span>';
        }
    }

    // 8. Receipt Modal & Thermal Printing
    function showReceiptModal(tx, receiptUrl) {
        currentReceiptUrl = receiptUrl;
        const container = document.getElementById('receiptPreviewContainer');

        let itemsHtml = '';
        tx.details.forEach(detail => {
            const prodName = detail.product ? detail.product.name : 'Produk';
            itemsHtml += `
                <div class="receipt-row" style="font-weight: 600; margin-top: 4px;">
                    <span>${prodName}</span>
                </div>
                <div class="receipt-row" style="color: #444; font-size: 0.75rem;">
                    <span>${detail.qty} x ${new Intl.NumberFormat('id-ID').format(detail.selling_price)}</span>
                    <span>Rp ${new Intl.NumberFormat('id-ID').format(detail.subtotal)}</span>
                </div>
            `;
        });

        container.innerHTML = `
            <div class="receipt-wrapper">
                <div class="receipt-header">
                    <div class="receipt-store-name">INDOMARET RETAIL</div>
                    <div style="font-size: 0.7rem; color: #555;">Jl. Raya Retail No. 88, Jakarta</div>
                    <div style="font-size: 0.7rem; color: #555;">Telp: (021) 8876-5432</div>
                </div>

                <div class="receipt-row" style="font-size: 0.75rem;">
                    <span>No: ${tx.invoice_number}</span>
                    <span>${new Date(tx.created_at).toLocaleDateString('id-ID')}</span>
                </div>
                <div class="receipt-row" style="font-size: 0.75rem;">
                    <span>Kasir: ${tx.user ? tx.user.name : 'Kasir'}</span>
                    <span>${new Date(tx.created_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'})}</span>
                </div>

                <div class="receipt-divider"></div>

                ${itemsHtml}

                <div class="receipt-divider"></div>

                <div class="receipt-row" style="font-weight: 700;">
                    <span>TOTAL:</span>
                    <span>Rp ${new Intl.NumberFormat('id-ID').format(tx.total_amount)}</span>
                </div>
                <div class="receipt-row">
                    <span>Bayar (${tx.payment_method.toUpperCase()}):</span>
                    <span>Rp ${new Intl.NumberFormat('id-ID').format(tx.paid_amount)}</span>
                </div>
                <div class="receipt-row" style="font-weight: 700;">
                    <span>KEMBALIAN:</span>
                    <span>Rp ${new Intl.NumberFormat('id-ID').format(tx.change_amount)}</span>
                </div>

                <div class="receipt-double-divider"></div>

                <div class="receipt-footer">
                    <div>TERIMA KASIH ATAS KUNJUNGAN ANDA</div>
                    <div style="margin-top: 4px; font-size: 0.65rem;">Barang yang sudah dibeli tidak dapat ditukar</div>
                    <div style="margin-top: 8px; letter-spacing: 2px; font-weight: bold;">* * * LUNAS * * *</div>
                </div>
            </div>
        `;

        document.getElementById('receiptModal').classList.add('active');
    }

    function closeReceiptModal() {
        document.getElementById('receiptModal').classList.remove('active');
        barcodeInput.focus();
    }

    function printReceiptDirect() {
        if (!currentReceiptUrl) return;
        const frame = document.getElementById('printFrame');
        frame.src = currentReceiptUrl;
        frame.onload = function() {
            frame.contentWindow.print();
        };
    }

    // Category Fast Filtering
    function filterCatalog(category, btn) {
        document.querySelectorAll('.pos-catalog-panel .preset-chip').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const items = document.querySelectorAll('.product-pos-item');
        items.forEach(el => {
            const name = el.getAttribute('data-name').toLowerCase();
            if (category === 'all') {
                el.style.display = 'flex';
            } else if (category === 'makanan') {
                el.style.display = (name.includes('indomie') || name.includes('chitato') || name.includes('taro') || name.includes('silverqueen') || name.includes('kopi') || name.includes('good day')) ? 'flex' : 'none';
            } else if (category === 'minuman') {
                el.style.display = (name.includes('aqua') || name.includes('minerale') || name.includes('ultra') || name.includes('sosro') || name.includes('pocari')) ? 'flex' : 'none';
            } else if (category === 'perawatan') {
                el.style.display = (name.includes('pepsodent') || name.includes('lifebuoy') || name.includes('rinso') || name.includes('bimoli')) ? 'flex' : 'none';
            }
        });
    }
</script>
@endsection
