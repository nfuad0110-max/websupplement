@extends('layouts.admin')
@use('App\Models\Produk')
@use('App\Support\Fmt')
@section('eyebrow', 'Katalog toko')
@section('title', 'Produk')
@section('actions')<x-button variant="accent" :href="route('admin.products')">+ Tambah produk</x-button>@endsection

@section('content')
@php
    $card = 'rounded-2xl border border-ink/10 bg-white';
    $th = 'px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-ink/60';
    $field = 'mt-1 block w-full rounded-xl border border-ink/20 bg-white px-4 py-3 text-[15px]';
@endphp
<div class="grid items-start gap-6 xl:grid-cols-[1fr_340px]">
    <section class="{{ $card }} overflow-hidden">
        <form role="search" method="GET" class="p-4">
            <label for="q" class="sr-only">Cari nama produk</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Cari nama produk" class="block w-full rounded-xl border border-ink/20 px-4 py-3">
        </form>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[40rem] text-sm">
                <thead class="bg-mint"><tr><th class="{{ $th }}">Produk</th><th class="{{ $th }}">Kategori</th><th class="{{ $th }} text-right">Harga</th><th class="{{ $th }}">Stok</th><th class="{{ $th }}"><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody class="divide-y divide-ink/10">
                    @forelse ($products as $p)
                        @php $st = Produk::stockStatus($p['stock'], $p['min_stock'] ?? null); $sel = $edit && $edit['id'] === $p['id']; @endphp
                        <tr class="{{ $sel ? 'bg-mint' : '' }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-lg bg-mint">
                                        @if (! empty($p['image_url']))
                                            <img src="{{ $p['image_url'] }}" alt="{{ $p['name'] }}" class="h-full w-full object-cover">
                                        @else
                                            <x-bottle :color="$p['color']" class="h-8" />
                                        @endif
                                    </span>
                                    <span class="font-bold">{{ $p['name'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ $p['category'] }}</td>
                            <td class="px-4 py-3 text-right font-bold">{{ Fmt::rp($p['price']) }}</td>
                            <td class="px-4 py-3">
                                <x-chip :type="$st['key']">{{ $p['stock'] === 0 ? 'Habis' : ($st['key'] === 'menipis' ? 'Menipis' : 'Tersedia') }}</x-chip>
                                <p class="mt-1 text-xs text-ink/70">{{ $p['stock'] }} unit</p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <x-button variant="outline" :href="route('admin.products', ['edit' => $p['slug']])" :aria-label="'Edit '.$p['name']">Edit</x-button>
                                    <form method="POST" action="{{ route('admin.products.destroy', $p['id']) }}" onsubmit="return confirm('Hapus {{ e($p['name']) }}?')">@csrf @method('DELETE')<x-button variant="danger" :aria-label="'Hapus '.$p['name']">Hapus</x-button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-ink/70">Produk tidak ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="border-t border-ink/10 p-4 text-sm text-ink/70">Jumlah stok berubah lewat menu <a href="{{ route('admin.stock') }}" class="font-semibold text-brand underline">Stok masuk</a> dan saat pesanan dibuat atau dibatalkan.</p>
    </section>

    {{-- Panel tambah / edit --}}
    <section class="{{ $card }} p-5" aria-labelledby="panel-title">
        <div class="flex items-center justify-between">
            <h2 id="panel-title" class="text-[22px] font-extrabold tracking-tight">{{ $edit ? 'Edit produk' : 'Tambah produk' }}</h2>
            @if ($edit)<a href="{{ route('admin.products') }}" aria-label="Tutup panel" class="grid h-11 w-11 place-items-center rounded-lg hover:bg-mint"><x-icon name="close" /></a>@endif
        </div>

        <form method="POST" enctype="multipart/form-data" action="{{ $edit ? route('admin.products.update', $edit['id']) : route('admin.products.store') }}" class="mt-4 space-y-4">
            @csrf @if ($edit) @method('PUT') @endif
            <x-input label="Nama produk" name="name" :value="$edit['name'] ?? null" required />

            <fieldset>
                <legend class="text-sm font-bold">Kategori</legend>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($categories as $cat)
                        <x-choice name="category" :value="$cat" :label="$cat" :checked="old('category', $edit['category'] ?? ($categories[0] ?? null)) === $cat" />
                    @endforeach
                </div>
                @error('category')<p class="mt-1 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror
            </fieldset>

            <x-input label="Harga (Rp)" name="price" type="number" inputmode="numeric" min="1" step="500" :value="isset($edit['price']) ? (int) $edit['price'] : null" required />
            <x-input label="Deskripsi" name="description" :textarea="true" :value="$edit['description'] ?? null" />

            <div>
                <label for="photo" class="text-sm font-bold">Foto produk</label>
                <label for="photo" class="mt-1 flex cursor-pointer flex-col items-center rounded-xl border-2 border-dashed border-brand/40 bg-mint px-4 py-6 text-center hover:border-brand">
                    <span class="font-bold">Tarik foto ke sini</span>
                    <span class="text-sm text-ink/70">atau pilih file JPG atau PNG, maksimal 2 MB</span>
                </label>
                <input id="photo" name="photo" type="file" accept="image/png,image/jpeg" class="sr-only">
                @error('photo')<p class="mt-1 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3">
                <x-button>Simpan produk</x-button>
                <x-button variant="outline" :href="route('admin.products')">Batal</x-button>
            </div>
        </form>
    </section>
</div>
@endsection
