<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alokasi_stok_pesanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detail_pesanan_id')->constrained('detail_pesanan')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('batch_stok_id')->constrained('batch_stok')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedInteger('jumlah');
            $table->dateTime('dikembalikan_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('detail_pesanan_id', 'idx_alokasi_detail');
            $table->index('batch_stok_id', 'idx_alokasi_batch');
        });

        DB::statement('ALTER TABLE alokasi_stok_pesanan
            ADD CONSTRAINT chk_alokasi_jumlah CHECK (jumlah > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('alokasi_stok_pesanan');
    }
};
