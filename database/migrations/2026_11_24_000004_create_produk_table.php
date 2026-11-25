<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategori')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->string('kode_produk', 30)->unique('uq_produk_kode');
            $table->string('nama_produk', 150);
            $table->string('slug', 180)->unique('uq_produk_slug');
            $table->text('deskripsi')->nullable();
            $table->decimal('harga', 12, 2);
            $table->string('gambar')->nullable();
            $table->string('isi_kemasan', 100)->nullable();
            $table->string('aturan_pakai', 255)->nullable()
                ->default('Ikuti petunjuk pada kemasan');
            $table->unsignedInteger('batas_minimum_stok')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'deleted_at', 'kategori_id'], 'idx_produk_katalog');
            $table->index('nama_produk', 'idx_produk_nama');
            $table->fullText(['nama_produk', 'deskripsi'], 'ft_produk_pencarian');
        });

        DB::statement('ALTER TABLE produk ADD CONSTRAINT chk_produk_harga CHECK (harga > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};
