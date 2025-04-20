<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $payment_methods = PaymentMethod::all();

        return response()->json([
            'success' => true,
            'message' => 'The payment method data is available',
            'data' => $payment_methods,
        ]);
    }
}
