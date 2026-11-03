<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->integer('qty')->default(1);
            $table->decimal('purchase_price', 14, 2)->default(0); // snapshot modal saat transaksi
            $table->decimal('selling_price', 14, 2)->default(0); // snapshot harga jual saat transaksi
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('profit', 14, 2)->default(0); // subtotal - (purchase_price * qty)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
    }
};
