<?php

namespace App\Observers;

use App\Models\OrderProduct;
use App\Models\Product;

class OrderProductObserver
{
    /**
     * Handle the OrderProduct "created" event.
     */
    public function created(OrderProduct $orderProduct): void
    {
        $product = Product::find($orderProduct->product_id);
        $product->decrement('stock', $orderProduct->qty);
    }

    /**
     * Handle the OrderProduct "updated" event.
     */
    public function updated(OrderProduct $orderProduct): void
    {
        $product = Product::find($orderProduct->product_id);
        $originalQty = $orderProduct->getOriginal('qty');
        $newQty = $orderProduct->qty;

        if ($originalQty != $newQty) {
            $product->increment('stock', $originalQty);
            $product->decrement('stock', $newQty);
        }
    }

    /**
     * Handle the OrderProduct "deleted" event.
     */
    public function deleted(OrderProduct $orderProduct): void
    {
        $product = Product::find($orderProduct->product_id);
        $product->increment('stock', $orderProduct->qty);
    }

    /**
     * Handle the OrderProduct "restored" event.
     */
    public function restored(OrderProduct $orderProduct): void
    {
        //
    }

    /**
     * Handle the OrderProduct "force deleted" event.
     */
    public function forceDeleted(OrderProduct $orderProduct): void
    {
        //
    }
}
