<?php

namespace App\Console\Commands;

use App\Services\FefoStockService;
use Illuminate\Console\Command;

class CancelExpiredOrders extends Command
{
    protected $signature = 'pesanan:batalkan-kedaluwarsa';

    protected $description = 'Batalkan pesanan menunggu pembayaran yang melewati batas waktu dan kembalikan stok FEFO';

    public function handle(FefoStockService $fefo): int
    {
        $count = $fefo->cancelExpiredOrders();
        $this->info("Pesanan dibatalkan otomatis: {$count}");

        return self::SUCCESS;
    }
}
