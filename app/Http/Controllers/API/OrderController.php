<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('paymentMethod', 'orderProducts')->get();

        $orders->transform(function ($order) {
            $order->payment_method = $order->paymentMethod->name ?? '-';
            $order->orderProducts->transform(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? '-',
                    'qty' => $item->qty ?? 0,
                    'unit_price' => $item->unit_price ?? 0,
                ];
            });

            return $order;
        });
        return response()->json([
            'success' => true,
            'message' => 'The order data is available',
            'data' => $orders,
        ]);
    }
}
