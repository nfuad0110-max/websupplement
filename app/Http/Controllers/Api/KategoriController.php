<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kategori;

class KategoriController extends Controller
{
    public function index()
    {
        $categories = Kategori::query()->orderBy('id')->get();

        return response()->json([
            'message' => 'Berhasil mengambil data kategori',
            'categories' => $categories,
        ]);
    }
}
