<?php

namespace Tests\Feature;

use App\Models\AlokasiStokPesanan;
use App\Models\BatchStok;
use App\Models\Kategori;
use App\Models\Pembayaran;
use App\Models\Pengaturan;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\RiwayatStatusPesanan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplemartIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private function makeAdmin(bool $active = true): User
    {
        return User::query()->create([
            'name' => 'Admin Uji',
            'email' => 'admin.test.' . uniqid() . '@supplemart.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_active' => $active,
        ]);
    }

    private function makeOwner(bool $active = true): User
    {
        return User::query()->create([
            'name' => 'Owner Uji',
            'email' => 'owner.test.' . uniqid() . '@supplemart.test',
            'password' => Hash::make('secret123'),
            'role' => 'owner',
            'is_active' => $active,
        ]);
    }

    private function makeCategory(string $name = 'Vitamin Test'): Kategori
    {
        $suffix = uniqid();

        return Kategori::query()->create([
            'nama_kategori' => $name . ' ' . $suffix,
            'slug' => 'kategori-test-' . $suffix,
        ]);
    }

    private function makeProductWithBatch(Kategori $cat, int $stock = 10, int $daysUntilExpiry = 60, float $price = 50000): array
    {
        $suffix = uniqid();
        $produk = Produk::query()->create([
            'kategori_id' => $cat->id,
            'kode_produk' => 'TST-' . strtoupper(substr($suffix, -8)),
            'nama_produk' => 'Suplemen Uji ' . $suffix,
            'slug' => 'suplemen-uji-' . $suffix,
            'deskripsi' => 'Deskripsi uji',
            'harga' => $price,
            'isi_kemasan' => '30 kapsul',
            'aturan_pakai' => '1x1 sehari',
            'batas_minimum_stok' => 10,
            'is_active' => true,
        ]);

        $batch = BatchStok::query()->create([
            'produk_id' => $produk->id,
            'kode_batch' => 'BT-' . strtoupper(substr($suffix, -6)),
            'jumlah_masuk' => $stock > 0 ? $stock : 5,
            'sisa' => $stock,
            'tanggal_masuk' => now()->subDays(5)->toDateString(),
            'tanggal_kedaluwarsa' => now()->addDays($daysUntilExpiry)->toDateString(),
        ]);

        return [$produk, $batch];
    }

    public function test_katalog_pencarian_filter_kategori_dan_detail_produk(): void
    {
        $catA = $this->makeCategory('Vitamin Uji');
        $catB = $this->makeCategory('Mineral Uji');
        [$prodA] = $this->makeProductWithBatch($catA, 15, 90, 80000);
        [$prodB] = $this->makeProductWithBatch($catB, 8, 45, 90000);

        $this->get(route('shop.catalog'))
            ->assertOk()
            ->assertSee($prodA->nama_produk)
            ->assertSee($prodB->nama_produk);

        $this->get(route('shop.catalog', ['kategori' => $catA->nama_kategori]))
            ->assertOk()
            ->assertSee($prodA->nama_produk)
            ->assertDontSee($prodB->nama_produk);

        $this->get(route('shop.catalog', ['q' => $prodB->nama_produk]))
            ->assertOk()
            ->assertSee($prodB->nama_produk)
            ->assertDontSee($prodA->nama_produk);

        $this->get(route('shop.product', $prodA->slug))
            ->assertOk()
            ->assertSee($prodA->nama_produk)
            ->assertSee('Tersedia · 15 botol');
    }

    public function test_keranjang_dan_checkout_alokasi_stok_fefo(): void
    {
        $cat = $this->makeCategory();
        [$produk, $batchLama] = $this->makeProductWithBatch($cat, 3, 20, 75000);

        $batchBaru = BatchStok::query()->create([
            'produk_id' => $produk->id,
            'kode_batch' => 'BT-NEW-' . uniqid(),
            'jumlah_masuk' => 10,
            'sisa' => 10,
            'tanggal_masuk' => now()->subDays(2)->toDateString(),
            'tanggal_kedaluwarsa' => now()->addDays(180)->toDateString(),
        ]);

        $this->post(route('shop.cart.add'), [
            'product_id' => $produk->id,
            'qty' => 5,
        ])->assertRedirect();

        $this->get(route('shop.cart'))
            ->assertOk()
            ->assertSee($produk->nama_produk);

        $res = $this->post(route('shop.order.store'), [
            'qty' => [$produk->id => 5],
            'nama' => 'Dewi Uji',
            'whatsapp' => '0812-3456-7890',
            'alamat' => 'Jl. Mawar No. 1, Gresik',
            'catatan' => 'Kirim pagi',
        ]);

        $pesanan = Pesanan::query()->latest('id')->firstOrFail();
        $res->assertRedirect(route('shop.payment', $pesanan->kode_pesanan));

        $this->assertEquals(Pesanan::STATUS_MENUNGGU, $pesanan->status);
        $this->assertEquals(375000.0, (float) $pesanan->total_produk);

        // FEFO: batch lebih cepat kedaluwarsa (sisa 3) habis dulu, sisa 2 diambil dari batch baru
        $this->assertEquals(0, $batchLama->fresh()->sisa);
        $this->assertEquals(8, $batchBaru->fresh()->sisa);

        $alokasi = AlokasiStokPesanan::query()
            ->whereHas('detailPesanan', fn ($q) => $q->where('pesanan_id', $pesanan->id))
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $alokasi);
        $this->assertEquals(3, $alokasi[0]->jumlah);
        $this->assertEquals(2, $alokasi[1]->jumlah);

        $this->get(route('shop.payment', $pesanan->kode_pesanan))
            ->assertOk()
            ->assertSee($pesanan->kode_pesanan)
            ->assertSee('Dewi Uji');
    }

    public function test_autentikasi_dan_otorisasi_admin_serta_owner(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeOwner();
        $inactive = $this->makeAdmin(false);

        // Guest diarahkan ke halaman masuk admin
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('owner.report'))->assertRedirect(route('admin.login'));

        // Akun nonaktif ditolak
        $this->post(route('admin.login.submit'), [
            'role' => 'admin',
            'email' => $inactive->email,
            'password' => 'secret123',
        ])->assertSessionHasErrors('email');

        // Role salah ditolak
        $this->post(route('admin.login.submit'), [
            'role' => 'owner',
            'email' => $admin->email,
            'password' => 'secret123',
        ])->assertSessionHasErrors('email');

        // Login owner berhasil dan diarahkan ke rekap, serta tidak boleh akses /admin
        $this->post(route('admin.login.submit'), [
            'role' => 'owner',
            'email' => $owner->email,
            'password' => 'secret123',
        ])->assertRedirect(route('owner.report'));

        $this->get(route('owner.report'))->assertOk();
        $this->get(route('admin.dashboard'))->assertForbidden();

        // Logout
        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest();

        // Login admin berhasil dan bisa akses dashboard maupun rekap
        $this->post(route('admin.login.submit'), [
            'role' => 'admin',
            'email' => $admin->email,
            'password' => 'secret123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('owner.report'))->assertOk();
    }

    public function test_manajemen_produk_admin_crud_dan_upload_foto(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();
        $cat = $this->makeCategory();
        $this->actingAs($admin);

        $this->get(route('admin.products'))->assertOk();

        $pngBinary = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

        // Tambah produk
        $this->post(route('admin.products.store'), [
            'name' => 'Vitamin E 400 IU',
            'category' => $cat->nama_kategori,
            'price' => 125000,
            'description' => 'Antioksidan harian',
            'photo' => UploadedFile::fake()->createWithContent('vite.png', $pngBinary),
        ])->assertRedirect(route('admin.products'));

        $produk = Produk::query()->where('nama_produk', 'Vitamin E 400 IU')->firstOrFail();
        $this->assertNotNull($produk->gambar);
        Storage::disk('public')->assertExists($produk->gambar);

        // Edit produk
        $this->put(route('admin.products.update', $produk->id), [
            'name' => 'Vitamin E 400 IU Updated',
            'category' => $cat->nama_kategori,
            'price' => 130000,
            'description' => 'Antioksidan updated',
        ])->assertRedirect(route('admin.products'));

        $this->assertEquals('Vitamin E 400 IU Updated', $produk->fresh()->nama_produk);
        $this->assertEquals(130000.0, (float) $produk->fresh()->harga);

        // Hapus (soft delete)
        $this->delete(route('admin.products.destroy', $produk->id))
            ->assertRedirect(route('admin.products'));

        $this->assertSoftDeleted('produk', ['id' => $produk->id]);
    }

    public function test_manajemen_stok_prioritas_batch_dan_siklus_pesanan(): void
    {
        $admin = $this->makeAdmin();
        $cat = $this->makeCategory();
        [$produk, $batch1] = $this->makeProductWithBatch($cat, 5, 30, 60000);
        $this->actingAs($admin);

        $this->get(route('admin.stock'))->assertOk();
        $this->get(route('admin.orders'))->assertOk();

        // Catat stok masuk baru dengan kedaluwarsa lebih lama
        $this->post(route('admin.stock.store'), [
            'product_id' => $produk->id,
            'quantity' => 12,
            'received_at' => now()->toDateString(),
            'expires_at' => now()->addDays(200)->toDateString(),
        ])->assertRedirect(route('admin.stock'));

        $batch2 = BatchStok::query()->where('produk_id', $produk->id)->where('id', '!=', $batch1->id)->firstOrFail();
        $this->assertEquals(12, $batch2->sisa);

        // Prioritaskan batch2 agar dijual duluan
        $this->post(route('admin.stock.priority', $batch2->kode_batch))
            ->assertRedirect();

        // Buat pesanan 2 unit -> harus mengambil dari batch2 karena diprioritaskan
        $this->post(route('shop.cart.add'), ['product_id' => $produk->id, 'qty' => 2]);
        $this->post(route('shop.order.store'), [
            'nama' => 'Budi Uji',
            'whatsapp' => '0813-0000-1111',
            'alamat' => 'Gresik',
        ]);

        $pesanan = Pesanan::query()->latest('id')->firstOrFail();
        $this->assertEquals(10, $batch2->fresh()->sisa);
        $this->assertEquals(5, $batch1->fresh()->sisa);

        // Admin tandai bukti tidak valid -> tetap Menunggu pembayaran
        $this->post(route('admin.orders.update', $pesanan->kode_pesanan), ['aksi' => 'tidak_valid'])
            ->assertSessionHas('status');
        $this->assertEquals(Pesanan::STATUS_MENUNGGU, $pesanan->fresh()->status);
        $this->assertEquals(Pembayaran::STATUS_DITOLAK, $pesanan->pembayaran->fresh()->status_verifikasi);

        // Admin verifikasi pembayaran valid -> kirim -> selesai
        $this->post(route('admin.orders.update', $pesanan->kode_pesanan), ['aksi' => 'valid']);
        $this->assertEquals(Pesanan::STATUS_DIBAYAR, $pesanan->fresh()->status);
        $this->assertEquals(Pembayaran::STATUS_VALID, $pesanan->pembayaran->fresh()->status_verifikasi);

        $this->post(route('admin.orders.update', $pesanan->kode_pesanan), ['aksi' => 'kirim']);
        $this->assertEquals(Pesanan::STATUS_DIKIRIM, $pesanan->fresh()->status);

        $this->post(route('admin.orders.update', $pesanan->kode_pesanan), ['aksi' => 'selesai']);
        $this->assertEquals(Pesanan::STATUS_SELESAI, $pesanan->fresh()->status);

        $this->assertGreaterThanOrEqual(4, RiwayatStatusPesanan::query()->where('pesanan_id', $pesanan->id)->count());

        // Cek laporan dan export PDF / Excel oleh owner
        $owner = $this->makeOwner();
        $this->actingAs($owner);

        $this->get(route('owner.report'))
            ->assertOk()
            ->assertSee($produk->nama_produk);

        $pdfRes = $this->get(route('owner.export', 'pdf'))->assertOk();
        $this->assertStringStartsWith('%PDF-1.4', $pdfRes->getContent());

        $xlsxRes = $this->get(route('owner.export', 'excel'))->assertOk();
        $this->assertStringStartsWith('PK', $xlsxRes->getContent());

        Pengaturan::query()->where('kunci', 'prioritized_batches')->delete();
    }

    public function test_pembatalan_pesanan_mengembalikan_stok_dan_command_kedaluwarsa(): void
    {
        $admin = $this->makeAdmin();
        $cat = $this->makeCategory();
        [$produk, $batch] = $this->makeProductWithBatch($cat, 10, 60, 50000);

        $this->post(route('shop.cart.add'), ['product_id' => $produk->id, 'qty' => 4]);
        $this->post(route('shop.order.store'), [
            'nama' => 'Siti Uji',
            'whatsapp' => '0812-1111-2222',
            'alamat' => 'Gresik',
        ]);

        $pesanan = Pesanan::query()->latest('id')->firstOrFail();
        $this->assertEquals(6, $batch->fresh()->sisa);

        // Pembatalan oleh admin mengembalikan stok
        $this->actingAs($admin);
        $this->post(route('admin.orders.update', $pesanan->kode_pesanan), ['aksi' => 'batal'])
            ->assertSessionHas('status');

        $this->assertEquals(Pesanan::STATUS_DIBATALKAN, $pesanan->fresh()->status);
        $this->assertEquals(10, $batch->fresh()->sisa);

        // Uji command pembatalan otomatis pesanan kedaluwarsa
        $this->post(route('shop.cart.add'), ['product_id' => $produk->id, 'qty' => 3]);
        $this->post(route('shop.order.store'), [
            'nama' => 'Rina Uji',
            'whatsapp' => '0812-3333-4444',
            'alamat' => 'Gresik',
        ]);
        $pesanan2 = Pesanan::query()->latest('id')->firstOrFail();
        $this->assertEquals(7, $batch->fresh()->sisa);

        $pesanan2->update(['batas_waktu_bayar' => now()->subMinute()]);
        $this->artisan('pesanan:batalkan-kedaluwarsa')->assertExitCode(0);

        $this->assertEquals(Pesanan::STATUS_DIBATALKAN, $pesanan2->fresh()->status);
        $this->assertEquals(10, $batch->fresh()->sisa);
    }

    public function test_api_endpoints_yang_sudah_ada_tetap_berfungsi(): void
    {
        $cat = $this->makeCategory();
        [$produk] = $this->makeProductWithBatch($cat, 9, 50, 45000);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonStructure(['message', 'categories']);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonStructure(['message', 'products']);

        $this->getJson('/api/products/' . $produk->id)
            ->assertOk()
            ->assertJsonPath('product.id', $produk->id)
            ->assertJsonPath('product.stok_tersedia', 9);
    }
}
