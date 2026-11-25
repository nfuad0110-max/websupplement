<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('detail_pesanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanan')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->string('nama_produk', 150);
            $table->decimal('harga_satuan', 12, 2);
            $table->unsignedInteger('jumlah');
            $table->decimal('subtotal', 14, 2)->storedAs('harga_satuan * jumlah');
            $table->timestamps();

            $table->unique(['pesanan_id', 'produk_id'], 'uq_detail_pesanan_produk');
            $table->index('produk_id', 'idx_detail_produk');
        });

        DB::statement('ALTER TABLE detail_pesanan
            ADD CONSTRAINT chk_detail_jumlah CHECK (jumlah > 0 AND harga_satuan >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pesanan');
    }
};
