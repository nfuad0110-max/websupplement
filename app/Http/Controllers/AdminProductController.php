<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminProductController extends Controller
{
    public function index(Request $r): View
    {
        $query = Produk::query()
            ->withStockSummary()
            ->orderBy('id');

        if ($r->filled('q')) {
            $keyword = trim((string) $r->input('q'));
            $query->where(function ($q) use ($keyword): void {
                $q->where('nama_produk', 'like', "%{$keyword}%")
                    ->orWhere('kode_produk', 'like', "%{$keyword}%");
            });
        }

        $products = $query->get()->map->toViewArray()->values()->all();

        $edit = null;
        if ($r->filled('edit')) {
            $editModel = Produk::query()
                ->withStockSummary()
                ->where('slug', (string) $r->query('edit'))
                ->first();
            $edit = $editModel?->toViewArray();
        }

        $categories = Kategori::query()->orderBy('id')->pluck('nama_kategori')->all();

        return view('admin.products', compact('products', 'edit', 'categories'));
    }

    public function store(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'exists:kategori,nama_kategori'],
            'price' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'name.required' => 'Nama produk wajib diisi.',
            'category.required' => 'Pilih kategori produk.',
            'category.exists' => 'Kategori yang dipilih tidak valid.',
            'price.required' => 'Harga produk wajib diisi.',
            'price.min' => 'Harga harus lebih dari 0.',
            'photo.image' => 'File foto harus berupa gambar JPG atau PNG.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $kategori = Kategori::query()->where('nama_kategori', $data['category'])->firstOrFail();

        $photoPath = null;
        if ($r->hasFile('photo')) {
            $photoPath = $r->file('photo')->store('produk', 'public');
        }

        Produk::query()->create([
            'kategori_id' => $kategori->id,
            'kode_produk' => $this->generateProductCode(),
            'nama_produk' => $data['name'],
            'slug' => $this->generateUniqueSlug($data['name']),
            'deskripsi' => $data['description'] ?? null,
            'harga' => $data['price'],
            'gambar' => $photoPath,
            'is_active' => true,
        ]);

        return redirect()->route('admin.products')->with('status', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $r, int $id): RedirectResponse
    {
        $produk = Produk::query()->findOrFail($id);

        $data = $r->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'exists:kategori,nama_kategori'],
            'price' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'name.required' => 'Nama produk wajib diisi.',
            'category.required' => 'Pilih kategori produk.',
            'price.required' => 'Harga produk wajib diisi.',
            'price.min' => 'Harga harus lebih dari 0.',
            'photo.image' => 'File foto harus berupa gambar JPG atau PNG.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $kategori = Kategori::query()->where('nama_kategori', $data['category'])->firstOrFail();

        $photoPath = $produk->gambar;
        if ($r->hasFile('photo')) {
            if ($produk->gambar && Storage::disk('public')->exists($produk->gambar)) {
                Storage::disk('public')->delete($produk->gambar);
            }
            $photoPath = $r->file('photo')->store('produk', 'public');
        }

        $produk->update([
            'kategori_id' => $kategori->id,
            'nama_produk' => $data['name'],
            'slug' => $this->generateUniqueSlug($data['name'], $produk->id),
            'deskripsi' => $data['description'] ?? null,
            'harga' => $data['price'],
            'gambar' => $photoPath,
        ]);

        return redirect()->route('admin.products')->with('status', 'Perubahan produk disimpan.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $produk = Produk::query()->findOrFail($id);
        $produk->update(['is_active' => false]);
        $produk->delete();

        return redirect()->route('admin.products')->with('status', "Produk {$produk->nama_produk} dihapus.");
    }

    private function generateProductCode(): string
    {
        $next = ((int) Produk::withTrashed()->max('id')) + 1;
        do {
            $code = 'SM-PRD-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $exists = Produk::withTrashed()->where('kode_produk', $code)->exists();
            if ($exists) {
                $next++;
            }
        } while ($exists);

        return $code;
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'produk';
        $slug = $base;
        $i = 2;

        while (Produk::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
