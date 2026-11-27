@extends('layouts.base')
@section('title', 'Masuk admin')

@section('body')
<div class="grid min-h-screen lg:grid-cols-2">
    <section class="flex flex-col justify-between bg-brand p-6 text-white sm:p-12">
        <a href="{{ route('shop.catalog') }}" aria-label="SUPPLEMART, ke toko"><x-logo :dark="true" /></a>
        <div class="my-10 lg:my-0">
            <h2 class="text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Suplemen sehat,<br>hidup lebih baik.</h2>
            <p class="mt-4 max-w-sm text-lg text-white/90">Kelola stok, tanggal kedaluwarsa, dan pesanan {{ config('supplemart.name') }} dalam satu tempat.</p>
        </div>
        <p class="hidden text-sm text-white/80 lg:block">Khusus admin toko dan pemilik usaha. Pelanggan tidak perlu login.</p>
    </section>

    <section class="flex items-center bg-canvas px-6 py-10 sm:px-12">
        <div class="mx-auto w-full max-w-sm">
            <h1 class="text-3xl font-extrabold tracking-tight">Masuk</h1>
            <p class="mt-1 text-ink/70">Pilih peran, lalu masukkan email dan kata sandi.</p>

            @if (session('status') || session('error'))
                <div class="mt-4 space-y-2"><x-flash /></div>
            @endif

            <form method="POST" action="{{ route('admin.login.submit') }}" class="mt-6 space-y-4">
                @csrf
                <fieldset class="grid grid-cols-2 gap-3">
                    <legend class="sr-only">Peran</legend>
                    <x-choice name="role" value="admin" label="Admin toko" shape="rounded-xl" class="w-full justify-center" :checked="old('role', 'admin') === 'admin'" />
                    <x-choice name="role" value="owner" label="Pemilik usaha" shape="rounded-xl" class="w-full justify-center" :checked="old('role') === 'owner'" />
                </fieldset>

                <x-input label="Email" name="email" type="email" autocomplete="username" placeholder="admin@alrisha.example" required />

                <div>
                    <label for="password" class="text-sm font-bold">Kata sandi</label>
                    <div class="relative">
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                               class="mt-1 block w-full rounded-xl border border-ink/20 bg-white py-3 pl-4 pr-28 text-[15px]">
                        <button type="button" data-toggle-password="password" class="absolute right-2 top-1/2 min-h-11 -translate-y-1/2 px-3 text-sm font-bold text-brand">Tampilkan</button>
                    </div>
                    @error('password')<p class="mt-1 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror
                </div>

                <x-button class="w-full"><span data-role-label>Masuk sebagai admin</span></x-button>
            </form>

            <p class="mt-5 text-sm text-ink/70">Akses fitur dibatasi sesuai peran. Kata sandi disimpan terenkripsi dan tidak pernah ditampilkan.</p>
            <a href="{{ route('shop.catalog') }}" class="mt-5 inline-block text-sm font-bold text-brand underline">← Kembali ke toko</a>
        </div>
    </section>
</div>
@endsection
