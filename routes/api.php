<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProdukController;
use App\Http\Controllers\Api\KategoriController;

Route::get('/products', [ProdukController::class, 'index']);

Route::get('/products/{id}', [ProdukController::class, 'show']);

Route::get('/categories', [KategoriController::class, 'index']);