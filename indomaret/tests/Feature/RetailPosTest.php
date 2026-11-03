<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RetailPosTest extends TestCase
{
    /**
     * Test login page is accessible and contains demo quick login buttons.
     */
    public function test_login_page_renders(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('RetailPOS System');
        $response->assertSee('UKasir (Kasir Toko)');
        $response->assertSee('UGudang (Staff Gudang)');
        $response->assertSee('UAdmin (Manajemen / Owner)');
    }

    /**
     * Test role-based authorization for Kasir (UKasir).
     */
    public function test_kasir_can_access_pos_module(): void
    {
        $kasir = User::where('role', 'ukasir')->first();
        $response = $this->actingAs($kasir)->get('/pos');
        $response->assertStatus(200);
        $response->assertSee('Kasir Terminal #01');
        $response->assertSee('Keranjang Belanja');
    }

    /**
     * Test Kasir cannot access Admin or Gudang modules.
     */
    public function test_kasir_cannot_access_gudang_or_admin(): void
    {
        $kasir = User::where('role', 'ukasir')->first();

        $responseGudang = $this->actingAs($kasir)->get('/gudang');
        $responseGudang->assertRedirect(route('pos.index'));

        $responseAdmin = $this->actingAs($kasir)->get('/admin');
        $responseAdmin->assertRedirect(route('pos.index'));
    }

    /**
     * Test checkout transaction automatically decreases product stock and creates stock movements.
     */
    public function test_pos_checkout_flow(): void
    {
        $kasir = User::where('role', 'ukasir')->first();
        $product = Product::where('stock', '>', 5)->first();
        $initialStock = $product->stock;
        $qtyToBuy = 2;

        $response = $this->actingAs($kasir)->postJson('/pos/checkout', [
            'items' => [
                ['product_id' => $product->id, 'qty' => $qtyToBuy]
            ],
            'payment_method' => 'cash',
            'paid_amount' => $product->selling_price * $qtyToBuy + 10000,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify database stock decrement
        $product->refresh();
        $this->assertEquals($initialStock - $qtyToBuy, $product->stock);

        // Verify stock movement record created
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'qty' => $qtyToBuy,
        ]);
    }

    /**
     * Test Gudang module access and inventory operations.
     */
    public function test_gudang_can_access_inventory(): void
    {
        $gudang = User::where('role', 'ugudang')->first();
        $response = $this->actingAs($gudang)->get('/gudang');
        $response->assertStatus(200);
        $response->assertSee('Manajemen Inventaris');
        $response->assertSee('Total Master SKU');
    }

    /**
     * Test Admin module access (read-only executive dashboard and reports).
     */
    public function test_admin_can_access_dashboard_and_reports(): void
    {
        $admin = User::where('role', 'uadmin')->first();

        $responseDashboard = $this->actingAs($admin)->get('/admin');
        $responseDashboard->assertStatus(200);
        $responseDashboard->assertSee('Executive Dashboard');
        $responseDashboard->assertSee('READ ONLY');

        $responseSales = $this->actingAs($admin)->get('/admin/sales');
        $responseSales->assertStatus(200);
        $responseSales->assertSee('Laporan Transaksi Penjualan');

        $responseProfit = $this->actingAs($admin)->get('/admin/profit');
        $responseProfit->assertStatus(200);
        $responseProfit->assertSee('Laporan Keuntungan (Profit)');
    }
}
