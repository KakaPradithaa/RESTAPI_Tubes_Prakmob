<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Validator;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|same:password',
            // Tambahkan aturan 'unique:users' di sini
            'phone' => 'required|string|max:20|unique:users,phone',
            'address' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            // Respons ini akan otomatis muncul jika validasi gagal (termasuk nomor duplikat)
            return response()->json([
                'success' => false,
                'message' => 'Ada kesalahan pada data yang diinput.',
                'data' => $validator->errors()
            ], 422);
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);

        $user = User::create($input);

        $success['token'] = $user->createToken('auth_token')->plainTextToken;
        $success['name'] = $user->name;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'data' => $success
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $auth = Auth::user();
            $success['token'] = $auth->createToken('auth_token')->plainTextToken;
            $success['name'] = $auth->name;
            $success['email'] = $auth->email;
            $success['role'] = $auth->role;

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'data' => $success
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
                'data' => null
            ], 401);
        }
    }
}
