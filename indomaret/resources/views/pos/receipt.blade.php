<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk #{{ $transaction->invoice_number }}</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            color: #000;
            background: #fff;
            width: 58mm;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .divider {
            border-top: 1px dashed #000;
            margin: 4px 0;
        }
        .double-divider {
            border-top: 1px double #000;
            margin: 4px 0;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="text-center bold" style="font-size: 13px;">INDOMARET RETAIL</div>
    <div class="text-center" style="font-size: 9px;">Jl. Raya Retail No. 88, Jakarta</div>
    <div class="text-center" style="font-size: 9px;">NPWP: 01.234.567.8-012.000</div>
    <div class="divider"></div>

    <div class="row">
        <span>No: {{ $transaction->invoice_number }}</span>
        <span>{{ $transaction->created_at->format('d/m/y') }}</span>
    </div>
    <div class="row">
        <span>Kasir: {{ $transaction->user->name ?? 'Kasir' }}</span>
        <span>{{ $transaction->created_at->format('H:i') }} WIB</span>
    </div>

    <div class="divider"></div>

    @foreach($transaction->details as $item)
        <div>{{ $item->product->name ?? 'Produk' }}</div>
        <div class="row" style="padding-left: 6px;">
            <span>{{ $item->qty }} x {{ number_format($item->selling_price, 0, ',', '.') }}</span>
            <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
        </div>
    @endforeach

    <div class="divider"></div>

    <div class="row bold">
        <span>TOTAL:</span>
        <span>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
    </div>
    <div class="row">
        <span>Bayar ({{ strtoupper($transaction->payment_method) }}):</span>
        <span>Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</span>
    </div>
    <div class="row bold">
        <span>KEMBALIAN:</span>
        <span>Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
    </div>

    <div class="double-divider"></div>

    <div class="text-center" style="margin-top: 6px;">TERIMA KASIH ATAS KUNJUNGAN ANDA</div>
    <div class="text-center" style="font-size: 9px;">Layanan Konsumen: 1500-280</div>
    <div class="text-center bold" style="margin-top: 4px; letter-spacing: 2px;">*** LUNAS ***</div>

</body>
</html>
