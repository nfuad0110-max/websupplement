<?php

namespace Database\Seeders;

use App\Models\BatchStok;
use App\Models\Kategori;
use App\Models\Pengaturan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed data awal aplikasi secara non-destruktif (tidak menghapus data yang ada).
     */
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@supplemart.test'],
            [
                'name' => 'Admin Toko',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'owner@supplemart.test'],
            [
                'name' => 'Widodo',
                'password' => Hash::make('password123'),
                'role' => 'owner',
                'is_active' => true,
            ]
        );

        $defaultSettings = [
            'nama_toko' => ['Alrisha Health Care', 'Nama toko yang tampil di halaman pelanggan'],
            'alamat_toko' => ['Swan Menganti Park Residence, Gresik', 'Alamat toko'],
            'no_whatsapp' => [env('SUPPLEMART_WA', '6281234567801'), 'Nomor WhatsApp konfirmasi pembayaran'],
            'bank_nama' => [env('SUPPLEMART_BANK', 'BCA'), 'Nama bank rekening toko'],
            'bank_rekening' => [env('SUPPLEMART_ACCOUNT', '1234567890'), 'Nomor rekening transfer'],
            'bank_atas_nama' => [env('SUPPLEMART_HOLDER', 'Widodo'), 'Nama pemilik rekening'],
            'batas_stok_menipis' => ['10', 'Ambang batas notifikasi stok menipis'],
            'batas_hampir_kedaluwarsa' => ['90', 'Ambang batas hari hampir kedaluwarsa'],
            'batas_waktu_bayar_jam' => ['24', 'Batas waktu transfer sebelum pesanan dibatalkan otomatis'],
        ];

        foreach ($defaultSettings as $kunci => [$nilai, $keterangan]) {
            Pengaturan::query()->firstOrCreate(
                ['kunci' => $kunci],
                ['nilai' => $nilai, 'keterangan' => $keterangan]
            );
        }

        $categories = [
            ['nama_kategori' => 'Vitamin', 'slug' => 'vitamin'],
            ['nama_kategori' => 'Mineral', 'slug' => 'mineral'],
            ['nama_kategori' => 'Multivitamin', 'slug' => 'multivitamin'],
            ['nama_kategori' => 'Lainnya', 'slug' => 'lainnya'],
        ];

        $categoryMap = [];
        foreach ($categories as $cat) {
            $model = Kategori::query()->firstOrCreate(
                ['slug' => $cat['slug']],
                ['nama_kategori' => $cat['nama_kategori']]
            );
            $categoryMap[$cat['nama_kategori']] = $model->id;
        }

        if (Produk::query()->count() === 0) {
            $usage = 'Ikuti petunjuk pada kemasan';
            $products = [
                ['SM-PRD-0001', 'Vitamin C 1000 mg', 'vitamin-c-1000-mg', 'Vitamin', 85000, '60 kapsul', $usage, 'Suplemen vitamin C untuk membantu memenuhi kebutuhan vitamin C harian. Cocok dikonsumsi satu kapsul sehari setelah makan.', 60, 48, 325],
                ['SM-PRD-0002', 'Vitamin D3 1000 IU', 'vitamin-d3-1000-iu', 'Vitamin', 95000, '60 kapsul', $usage, 'Suplemen vitamin D3 untuk membantu memenuhi kebutuhan vitamin D harian. Cocok dikonsumsi satu kapsul sehari setelah makan.', 40, 32, 300],
                ['SM-PRD-0003', 'Vitamin B Complex', 'vitamin-b-complex', 'Vitamin', 78000, '60 tablet', $usage, 'Kombinasi vitamin B untuk membantu memenuhi kebutuhan vitamin B harian. Konsumsi satu tablet sehari setelah makan.', 30, 5, 80],
                ['SM-PRD-0004', 'Zinc 50 mg', 'zinc-50-mg', 'Mineral', 69000, '60 tablet', $usage, 'Suplemen mineral zinc untuk membantu memenuhi kebutuhan zinc harian. Konsumsi satu tablet sehari setelah makan.', 60, 60, 260],
                ['SM-PRD-0005', 'Magnesium 400 mg', 'magnesium-400-mg', 'Mineral', 112000, '60 kapsul', $usage, 'Suplemen magnesium untuk membantu memenuhi kebutuhan mineral harian. Konsumsi satu kapsul sehari setelah makan.', 30, 14, 25],
                ['SM-PRD-0006', 'Multivitamin Harian', 'multivitamin-harian', 'Multivitamin', 135000, '60 tablet', $usage, 'Kombinasi vitamin dan mineral untuk membantu memenuhi kebutuhan nutrisi harian. Konsumsi satu tablet sehari setelah makan.', 40, 40, 215],
                ['SM-PRD-0007', 'Omega-3 Fish Oil', 'omega-3-fish-oil', 'Lainnya', 165000, '60 softgel', $usage, 'Minyak ikan sebagai sumber asam lemak omega-3. Konsumsi satu softgel sehari bersama makanan.', 40, 21, 65],
                ['SM-PRD-0008', 'Probiotik 10 Miliar', 'probiotik-10-miliar', 'Lainnya', 148000, '30 kapsul', $usage, 'Suplemen probiotik dengan 10 miliar CFU per kapsul. Konsumsi satu kapsul sehari sesuai petunjuk kemasan.', 24, 0, 75],
            ];

            foreach ($products as $idx => $p) {
                $produk = Produk::query()->create([
                    'kategori_id' => $categoryMap[$p[3]],
                    'kode_produk' => $p[0],
                    'nama_produk' => $p[1],
                    'slug' => $p[2],
                    'harga' => $p[4],
                    'isi_kemasan' => $p[5],
                    'aturan_pakai' => $p[6],
                    'deskripsi' => $p[7],
                    'batas_minimum_stok' => 10,
                    'is_active' => true,
                ]);

                BatchStok::query()->create([
                    'produk_id' => $produk->id,
                    'kode_batch' => 'B-' . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT),
                    'jumlah_masuk' => $p[8],
                    'sisa' => $p[9],
                    'tanggal_masuk' => now()->subDays(30)->toDateString(),
                    'tanggal_kedaluwarsa' => now()->addDays($p[10])->toDateString(),
                    'dicatat_oleh' => $admin->id,
                ]);
            }
        }
    }
}
