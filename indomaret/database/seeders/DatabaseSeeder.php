<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Users for each role
        $kasir = User::updateOrCreate(
            ['username' => 'kasir'],
            [
                'name' => 'Budi Santoso',
                'email' => 'kasir@retailpos.id',
                'password' => Hash::make('password'),
                'role' => 'ukasir',
            ]
        );

        $gudang = User::updateOrCreate(
            ['username' => 'gudang'],
            [
                'name' => 'Siti Rahma',
                'email' => 'gudang@retailpos.id',
                'password' => Hash::make('password'),
                'role' => 'ugudang',
            ]
        );

        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Hendro Wijaya',
                'email' => 'admin@retailpos.id',
                'password' => Hash::make('password'),
                'role' => 'uadmin',
            ]
        );

        // 2. Realistic Retail Products
        $productsData = [
            ['sku' => '089686010118', 'name' => 'Indomie Goreng Spesial 85g', 'purchase_price' => 2700, 'selling_price' => 3500, 'stock' => 120],
            ['sku' => '089686010125', 'name' => 'Indomie Kuah Kari Ayam 72g', 'purchase_price' => 2650, 'selling_price' => 3500, 'stock' => 95],
            ['sku' => '8886008101053', 'name' => 'Aqua Air Mineral 600ml', 'purchase_price' => 2800, 'selling_price' => 4000, 'stock' => 84],
            ['sku' => '8996001600269', 'name' => 'Le Minerale 600ml', 'purchase_price' => 2700, 'selling_price' => 3800, 'stock' => 60],
            ['sku' => '8992753101124', 'name' => 'Ultra Milk Cokelat 250ml', 'purchase_price' => 5500, 'selling_price' => 7200, 'stock' => 48],
            ['sku' => '8991001101123', 'name' => 'Teh Botol Sosro Kotak 250ml', 'purchase_price' => 3200, 'selling_price' => 4500, 'stock' => 55],
            ['sku' => '8992388111012', 'name' => 'Chitato Sapi Panggang 68g', 'purchase_price' => 9800, 'selling_price' => 12500, 'stock' => 32],
            ['sku' => '8991002102145', 'name' => 'SilverQueen Cashew 62g', 'purchase_price' => 14500, 'selling_price' => 18000, 'stock' => 28],
            ['sku' => '8999999014125', 'name' => 'Pepsodent Pasta Gigi 190g', 'purchase_price' => 12000, 'selling_price' => 15500, 'stock' => 40],
            ['sku' => '8999999512341', 'name' => 'Lifebuoy Body Wash 450ml', 'purchase_price' => 22000, 'selling_price' => 28500, 'stock' => 16],
            ['sku' => '8999999712093', 'name' => 'Rinso Molto Konsentrat 770g', 'purchase_price' => 21500, 'selling_price' => 27000, 'stock' => 18],
            ['sku' => '8992775210018', 'name' => 'Minyak Goreng Bimoli Klasik 2L', 'purchase_price' => 32000, 'selling_price' => 37500, 'stock' => 24],
            ['sku' => '8992696414013', 'name' => 'Good Day Mocacinno 5x20g', 'purchase_price' => 9500, 'selling_price' => 12000, 'stock' => 30],
            ['sku' => '8992388310026', 'name' => 'Taro Net Seaweed 65g', 'purchase_price' => 4200, 'selling_price' => 6000, 'stock' => 4], // Low Stock
            ['sku' => '8992772010116', 'name' => 'Pocari Sweat Botol 500ml', 'purchase_price' => 6800, 'selling_price' => 8500, 'stock' => 3], // Low Stock
        ];

        $createdProducts = [];
        foreach ($productsData as $data) {
            $product = Product::updateOrCreate(['sku' => $data['sku']], $data);
            $createdProducts[] = $product;

            // Inbound stock movement
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $gudang->id,
                'type' => 'in',
                'qty' => $product->stock,
                'note' => 'Penerimaan Awal Supplier PT Indomarco Adi Prima',
                'created_at' => Carbon::now()->subDays(7),
                'updated_at' => Carbon::now()->subDays(7),
            ]);
        }

        // 3. Sample historical transactions across the last 3 days
        $sampleTransactions = [
            [
                'days_ago' => 2,
                'invoice' => 'INV-20261018-0001',
                'payment' => 'cash',
                'paid' => 50000,
                'items' => [
                    ['prod_idx' => 0, 'qty' => 5], // Indomie Goreng
                    ['prod_idx' => 2, 'qty' => 2], // Aqua
                    ['prod_idx' => 6, 'qty' => 1], // Chitato
                ]
            ],
            [
                'days_ago' => 2,
                'invoice' => 'INV-20261018-0002',
                'payment' => 'qris',
                'paid' => 38000,
                'items' => [
                    ['prod_idx' => 11, 'qty' => 1], // Bimoli
                ]
            ],
            [
                'days_ago' => 1,
                'invoice' => 'INV-20261019-0001',
                'payment' => 'cash',
                'paid' => 100000,
                'items' => [
                    ['prod_idx' => 7, 'qty' => 2], // SilverQueen
                    ['prod_idx' => 4, 'qty' => 2], // Ultra Milk
                    ['prod_idx' => 5, 'qty' => 3], // Teh Botol
                ]
            ],
            [
                'days_ago' => 1,
                'invoice' => 'INV-20261019-0002',
                'payment' => 'qris',
                'paid' => 43500,
                'items' => [
                    ['prod_idx' => 8, 'qty' => 1], // Pepsodent
                    ['prod_idx' => 9, 'qty' => 1], // Lifebuoy
                ]
            ],
            [
                'days_ago' => 0,
                'invoice' => 'INV-20261020-0001',
                'payment' => 'cash',
                'paid' => 50000,
                'items' => [
                    ['prod_idx' => 0, 'qty' => 4], // Indomie
                    ['prod_idx' => 1, 'qty' => 4], // Indomie Kari
                    ['prod_idx' => 2, 'qty' => 3], // Aqua
                ]
            ],
            [
                'days_ago' => 0,
                'invoice' => 'INV-20261020-0002',
                'payment' => 'qris',
                'paid' => 27000,
                'items' => [
                    ['prod_idx' => 10, 'qty' => 1], // Rinso
                ]
            ],
        ];

        foreach ($sampleTransactions as $txData) {
            $txDate = Carbon::now()->subDays($txData['days_ago'])->setHour(10)->setMinute(rand(10, 50));
            $totalAmount = 0;
            $itemsToCreate = [];

            foreach ($txData['items'] as $item) {
                $product = $createdProducts[$item['prod_idx']];
                $subtotal = $product->selling_price * $item['qty'];
                $profit = $subtotal - ($product->purchase_price * $item['qty']);
                $totalAmount += $subtotal;

                $itemsToCreate[] = [
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                    'purchase_price' => $product->purchase_price,
                    'selling_price' => $product->selling_price,
                    'subtotal' => $subtotal,
                    'profit' => $profit,
                    'created_at' => $txDate,
                    'updated_at' => $txDate,
                ];
            }

            $paid = max($txData['paid'], $totalAmount);
            $change = $paid - $totalAmount;

            $transaction = Transaction::create([
                'invoice_number' => $txData['invoice'],
                'user_id' => $kasir->id,
                'total_amount' => $totalAmount,
                'paid_amount' => $paid,
                'change_amount' => $change,
                'payment_method' => $txData['payment'],
                'created_at' => $txDate,
                'updated_at' => $txDate,
            ]);

            foreach ($itemsToCreate as $detail) {
                $detail['transaction_id'] = $transaction->id;
                TransactionDetail::create($detail);
            }
        }
    }
}
