<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produk;

class ProdukController extends Controller
{
    public function index()
    {
        $products = Produk::query()
            ->active()
            ->withStockSummary()
            ->get()
            ->map(function (Produk $product): array {
                $data = $product->toArray();
                $data['stok_tersedia'] = $product->availableStock();
                $data['kedaluwarsa_terdekat'] = $product->nearestExpiry();
                $data['gambar_url'] = $product->imageUrl();

                return $data;
            });

        return response()->json([
            'message' => 'Berhasil mengambil data produk',
            'products' => $products,
        ]);
    }

    public function show($id)
    {
        $product = Produk::query()
            ->active()
            ->withStockSummary()
            ->findOrFail($id);

        $data = $product->toArray();
        $data['stok_tersedia'] = $product->availableStock();
        $data['kedaluwarsa_terdekat'] = $product->nearestExpiry();
        $data['gambar_url'] = $product->imageUrl();

        return response()->json([
            'message' => 'Berhasil mengambil detail produk',
            'product' => $data,
        ]);
    }
}
