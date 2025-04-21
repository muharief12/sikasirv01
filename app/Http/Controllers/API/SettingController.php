<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $setting = Setting::first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'The data is not available',
                'data' => null,
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'The setting data is available',
            'data' => $setting,
        ]);
    }
}
