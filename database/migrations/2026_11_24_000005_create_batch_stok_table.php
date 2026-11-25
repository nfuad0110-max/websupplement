<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('batch_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produk')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->string('kode_batch', 40)->nullable();
            $table->unsignedInteger('jumlah_masuk');
            $table->unsignedInteger('sisa');
            $table->decimal('harga_beli', 12, 2)->nullable();
            $table->string('supplier', 100)->nullable();
            $table->date('tanggal_masuk');
            $table->date('tanggal_kedaluwarsa');
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')
                ->cascadeOnUpdate()->nullOnDelete();
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->unique(['produk_id', 'kode_batch'], 'uq_batch_produk_kode');
            $table->index(['produk_id', 'tanggal_kedaluwarsa', 'sisa'], 'idx_batch_fefo');
            $table->index(['tanggal_kedaluwarsa', 'sisa'], 'idx_batch_kedaluwarsa');
        });

        DB::statement('ALTER TABLE batch_stok
            ADD CONSTRAINT chk_batch_jumlah CHECK (jumlah_masuk > 0),
            ADD CONSTRAINT chk_batch_sisa CHECK (sisa <= jumlah_masuk),
            ADD CONSTRAINT chk_batch_tanggal CHECK (tanggal_kedaluwarsa > tanggal_masuk)');
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_stok');
    }
};
