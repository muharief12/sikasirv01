<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            /**
             * Email
             * @example admin001@sikasirv01.dev
             */
            'email' => 'required|email',
            /**
             * Password
             * @example password
             */
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Sorry, the account is not available!'
            ], 422);
        }

        $token = $user->createToken('API Token')->plainTextToken;
        return response()->json([
            'success' => true,
            'message' => 'Login success!',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ],
        ]);
    }
}
