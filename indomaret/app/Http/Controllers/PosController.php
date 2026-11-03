<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\StockMovement;
use Carbon\Carbon;

class PosController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('name', 'asc')->get();
        $recentTransactions = Transaction::where('user_id', Auth::id())
            ->whereDate('created_at', Carbon::today())
            ->with('details.product')
            ->latest()
            ->take(5)
            ->get();

        $todayTotal = Transaction::where('user_id', Auth::id())
            ->whereDate('created_at', Carbon::today())
            ->sum('total_amount');

        $todayCount = Transaction::where('user_id', Auth::id())
            ->whereDate('created_at', Carbon::today())
            ->count();

        return view('pos.index', compact('products', 'recentTransactions', 'todayTotal', 'todayCount'));
    }

    public function searchProduct(Request $request)
    {
        $query = trim($request->input('q', ''));
        if (empty($query)) {
            return response()->json(['products' => []]);
        }

        // Exact match by SKU (for barcode scan)
        $exactBySku = Product::where('sku', $query)->first();
        if ($exactBySku) {
            return response()->json([
                'exact' => true,
                'product' => $exactBySku
            ]);
        }

        // Partial match by SKU or Name
        $products = Product::where('sku', 'like', "%{$query}%")
            ->orWhere('name', 'like', "%{$query}%")
            ->limit(10)
            ->get();

        return response()->json([
            'exact' => false,
            'products' => $products
        ]);
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'payment_method' => 'required|string|in:cash,qris,debit,transfer',
            'paid_amount' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $totalAmount = 0;
            $itemsData = [];

            // 1. Verify stock and calculate totals
            foreach ($validated['items'] as $item) {
                // Lock product row for update to prevent race conditions in multi-checkout
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->firstOrFail();

                if ($product->stock < $item['qty']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stok produk '{$product->name}' tidak mencukupi! Tersedia: {$product->stock}, Diminta: {$item['qty']}"
                    ], 422);
                }

                $subtotal = $product->selling_price * $item['qty'];
                $profit = $subtotal - ($product->purchase_price * $item['qty']);
                $totalAmount += $subtotal;

                $itemsData[] = [
                    'product' => $product,
                    'qty' => $item['qty'],
                    'purchase_price' => $product->purchase_price,
                    'selling_price' => $product->selling_price,
                    'subtotal' => $subtotal,
                    'profit' => $profit,
                ];
            }

            if ($validated['paid_amount'] < $totalAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jumlah pembayaran kurang dari total belanja! Total: Rp ' . number_format($totalAmount, 0, ',', '.')
                ], 422);
            }

            $changeAmount = $validated['paid_amount'] - $totalAmount;

            // Generate unique invoice number: INV-YYYYMMDD-XXXX
            $datePrefix = Carbon::now()->format('Ymd');
            $countToday = Transaction::whereDate('created_at', Carbon::today())->count() + 1;
            $invoiceNumber = 'INV-' . $datePrefix . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

            // 2. Create Transaction
            $transaction = Transaction::create([
                'invoice_number' => $invoiceNumber,
                'user_id' => Auth::id(),
                'total_amount' => $totalAmount,
                'paid_amount' => $validated['paid_amount'],
                'change_amount' => $changeAmount,
                'payment_method' => $validated['payment_method'],
            ]);

            // 3. Create Transaction Details and update inventory
            foreach ($itemsData as $data) {
                $product = $data['product'];

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'qty' => $data['qty'],
                    'purchase_price' => $data['purchase_price'],
                    'selling_price' => $data['selling_price'],
                    'subtotal' => $data['subtotal'],
                    'profit' => $data['profit'],
                ]);

                // Reduce inventory stock
                $product->decrement('stock', $data['qty']);

                // Record stock movement (audit trail)
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => Auth::id(),
                    'type' => 'out',
                    'qty' => $data['qty'],
                    'note' => 'Penjualan POS Kasir: ' . $invoiceNumber,
                ]);
            }

            $transaction->load(['details.product', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil diproses!',
                'transaction' => $transaction,
                'receipt_url' => route('pos.receipt', $transaction->id),
            ]);
        });
    }

    public function printReceipt($id)
    {
        $transaction = Transaction::with(['details.product', 'user'])->findOrFail($id);
        return view('pos.receipt', compact('transaction'));
    }
}
