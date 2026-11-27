<?php

namespace App\Support;

use App\Models\Produk;

/**
 * Keranjang berbasis session: pelanggan memesan tanpa login.
 * Struktur session: ['cart' => [product_id => qty]]
 */
class Cart
{
    public const KEY = 'cart';

    private static function max(int $id): int
    {
        $product = Produk::query()->active()->withStockSummary()->find($id);

        return $product ? min(99, $product->availableStock()) : 0;
    }

    public static function add(int $id, int $qty = 1): void
    {
        $max = self::max($id);
        if ($max < 1) {
            return;
        }

        $cart = session(self::KEY, []);
        $cart[$id] = min($max, max(1, (int) ($cart[$id] ?? 0) + $qty));
        session([self::KEY => array_filter($cart)]);
    }

    public static function set(int $id, int $qty): void
    {
        $cart = session(self::KEY, []);
        if (! isset($cart[$id])) {
            return;
        }

        $max = self::max($id);
        if ($max < 1) {
            unset($cart[$id]);
            session([self::KEY => $cart]);

            return;
        }

        $cart[$id] = max(1, min($max, $qty));
        session([self::KEY => $cart]);
    }

    public static function remove(int $id): void
    {
        $cart = session(self::KEY, []);
        unset($cart[$id]);
        session([self::KEY => $cart]);
    }

    public static function clear(): void
    {
        session()->forget(self::KEY);
    }

    /** @return array<int, array{product:array<string,mixed>,qty:int,line:float}> */
    public static function items(): array
    {
        $raw = session(self::KEY, []);
        if (! $raw) {
            return [];
        }

        $ids = array_map('intval', array_keys($raw));
        $products = Produk::query()
            ->active()
            ->withStockSummary()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $items = [];
        foreach ($raw as $id => $qty) {
            $product = $products->get((int) $id);
            if (! $product) {
                continue;
            }

            $stock = $product->availableStock();
            if ($stock < 1) {
                continue;
            }

            $safeQty = max(1, min(min(99, $stock), (int) $qty));
            $view = $product->toViewArray();
            $items[] = [
                'product' => $view,
                'qty' => $safeQty,
                'line' => $view['price'] * $safeQty,
            ];
        }

        return $items;
    }

    public static function count(): int
    {
        return array_sum(array_column(self::items(), 'qty'));
    }

    public static function total(): float
    {
        return (float) array_sum(array_column(self::items(), 'line'));
    }
}
