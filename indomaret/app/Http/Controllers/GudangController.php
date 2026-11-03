<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\StockMovement;

class GudangController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->input('q', ''));
        $filter = $request->input('filter', 'all');

        $query = Product::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($filter === 'low_stock') {
            $query->where('stock', '<=', 5)->where('stock', '>', 0);
        } elseif ($filter === 'out_of_stock') {
            $query->where('stock', '<=', 0);
        }

        $products = $query->orderBy('name', 'asc')->paginate(15)->withQueryString();

        // Warehouse Quick Metrics
        $totalProducts = Product::count();
        $totalStockUnits = Product::sum('stock');
        $lowStockCount = Product::where('stock', '<=', 5)->where('stock', '>', 0)->count();
        $outOfStockCount = Product::where('stock', '<=', 0)->count();
        $totalAssetValue = Product::selectRaw('SUM(purchase_price * stock) as total_asset')->value('total_asset') ?? 0;

        $recentMovements = StockMovement::with(['product', 'user'])
            ->latest()
            ->take(8)
            ->get();

        return view('gudang.index', compact(
            'products',
            'totalProducts',
            'totalStockUnits',
            'lowStockCount',
            'outOfStockCount',
            'totalAssetValue',
            'recentMovements',
            'search',
            'filter'
        ));
    }

    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:50|unique:products,sku',
            'name' => 'required|string|max:255',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated) {
            $product = Product::create([
                'sku' => $validated['sku'],
                'name' => $validated['name'],
                'purchase_price' => $validated['purchase_price'],
                'selling_price' => $validated['selling_price'],
                'stock' => $validated['stock'],
            ]);

            if ($validated['stock'] > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => Auth::id(),
                    'type' => 'in',
                    'qty' => $validated['stock'],
                    'note' => $validated['note'] ?: 'Input Stok Awal Produk Baru',
                ]);
            }
        });

        return redirect()->route('gudang.index')->with('success', "Produk '{$validated['name']}' berhasil ditambahkan ke katalog master.");
    }

    public function updateProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'sku' => 'required|string|max:50|unique:products,sku,' . $product->id,
            'name' => 'required|string|max:255',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        $product->update([
            'sku' => $validated['sku'],
            'name' => $validated['name'],
            'purchase_price' => $validated['purchase_price'],
            'selling_price' => $validated['selling_price'],
        ]);

        return redirect()->route('gudang.index')->with('success', "Data produk '{$product->name}' berhasil diperbarui.");
    }

    public function destroyProduct($id)
    {
        $product = Product::findOrFail($id);

        if ($product->transactionDetails()->exists()) {
            return redirect()->route('gudang.index')->with('error', "Produk '{$product->name}' tidak dapat dihapus karena sudah memiliki riwayat transaksi penjualan. Stok dapat dinolkan melalui Koreksi Stok.");
        }

        $name = $product->name;
        $product->delete();

        return redirect()->route('gudang.index')->with('success', "Produk '{$name}' berhasil dihapus dari master katalog.");
    }

    public function inbound(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
            'note' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated) {
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);
            $product->increment('stock', $validated['qty']);

            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'type' => 'in',
                'qty' => $validated['qty'],
                'note' => $validated['note'],
            ]);
        });

        return redirect()->route('gudang.index')->with('success', "Penerimaan barang masuk ({$validated['qty']} unit) berhasil dicatat dan stok bertambah.");
    }

    public function correction(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer', // Can be positive or negative
            'reason' => 'required|string|in:rusak,hilang,kadaluwarsa,selisih_hitung,retur_supplier,lainnya',
            'note' => 'nullable|string|max:255',
        ]);

        if ($validated['qty'] == 0) {
            return redirect()->route('gudang.index')->with('error', "Nilai penyesuaian stok tidak boleh nol.");
        }

        DB::transaction(function () use ($validated) {
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);
            $newStock = $product->stock + $validated['qty'];

            if ($newStock < 0) {
                throw new \Exception("Penyesuaian stok melebihi stok yang ada. Stok saat ini: {$product->stock}");
            }

            $product->update(['stock' => $newStock]);

            $reasonLabels = [
                'rusak' => 'Barang Rusak',
                'hilang' => 'Barang Hilang',
                'kadaluwarsa' => 'Kadaluwarsa (Expired)',
                'selisih_hitung' => 'Selisih Perhitungan Opname',
                'retur_supplier' => 'Retur ke Supplier',
                'lainnya' => 'Penyesuaian Lainnya',
            ];

            $fullNote = ($reasonLabels[$validated['reason']] ?? $validated['reason']) . ($validated['note'] ? ': ' . $validated['note'] : '');

            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'type' => 'correction',
                'qty' => $validated['qty'],
                'note' => $fullNote,
            ]);
        });

        return redirect()->route('gudang.index')->with('success', "Koreksi stok berhasil disimpan dan audit trail diperbarui.");
    }

    public function movements(Request $request)
    {
        $type = $request->input('type');
        $query = StockMovement::with(['product', 'user']);

        if ($type && in_array($type, ['in', 'out', 'correction'])) {
            $query->where('type', $type);
        }

        $movements = $query->latest()->paginate(20)->withQueryString();

        return view('gudang.movements', compact('movements', 'type'));
    }
}
