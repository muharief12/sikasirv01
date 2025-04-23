<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email',
            'gender' => 'required',
            'phone' => 'required',
            'address' => 'required',
            'total_price' => 'required',
            'note' => 'required',
            'paid_amount' => 'required|integer',
            'change_amount' => 'required|integer',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'There is a wrong validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        //  storing data validation process to orderProducts
        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);

            if (!$product || $product->stock < $item['qty']) {
                return response()->json([
                    'success' => false,
                    'message' => 'The ' . $product->name . ' product is not available',
                ], 422);
            }
        }

        //If the validation are eligible
        $order = Order::create($request->only([
            'name',
            'email',
            'gender',
            'phone',
            'address',
            'total_price',
            'note',
            'paid_amount',
            'change_amount',
            'payment_method_id',
        ]));

        //Storing data to orderProducts
        foreach ($request->items as $item) {
            $order->orderProducts()->create([
                'product_id' => $item['product_id'],
                'order_id' => $order->id,
                'qty' => $item['qty'],
                'unit_price' => $item['unit_price'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'The order data have been created succesfully',
            'data' => $order,
        ], 200);
    }
}
