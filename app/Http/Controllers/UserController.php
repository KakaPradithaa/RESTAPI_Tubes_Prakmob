<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    // ===================================================================
    // === METODE BARU YANG AMAN UNTUK PROFIL PENGGUNA YANG SEDANG LOGIN ===
    // ===================================================================

    /**
     * [UNTUK USER] Menampilkan profil PENGGUNA YANG SEDANG LOGIN.
     * Dipanggil oleh rute: GET /api/user
     */
    public function showProfile()
    {
        return response()->json(Auth::user(), 200);
    }

    /**
     * [UNTUK USER] Memperbarui profil PENGGUNA YANG SEDANG LOGIN.
     * Dipanggil oleh rute: PUT /api/user
     */
    public function updateProfile(Request $request)
    {
        // Dapatkan pengguna yang sedang login berdasarkan tokennya
        $user = Auth::user();

        // Aturan validasi
        $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'password' => 'nullable|string|min:8', // Validasi password baru jika ada
        ]);

        // --- LOGIKA UTAMA ADA DI SINI ---

        // Update data profil dasar
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->address = $request->address;

        // Cek jika pengguna ingin mengubah password
        if ($request->filled('password')) {
            // Validasi dulu apakah password lama yang dimasukkan benar
            if (!Hash::check($request->old_password, $user->password)) {
                // Jika password lama salah, kirim pesan error
                throw ValidationException::withMessages([
                    'message' => 'Password lama yang Anda masukkan salah.',
                ]);
            }

            // Jika password lama benar, hash password baru dan simpan
            $user->password = Hash::make($request->password);
        }

        // Simpan semua perubahan ke database
        $user->save();

        // Kirim kembali response sukses beserta data user yang sudah di-update
        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $user
        ], 200);
    }


    // ===================================================================
    // === METODE LAMA ANDA (DISARANKAN HANYA UNTUK ADMIN) ===
    // ===================================================================

    /**
     * [HANYA UNTUK ADMIN] Menampilkan semua pengguna.
     * Sebaiknya dilindungi oleh middleware admin.
     */
    public function index()
    {
        return response()->json(User::all(), 200);
    }

    /**
     * [HANYA UNTUK ADMIN] Membuat pengguna baru.
     * Fungsi register di AuthController lebih cocok untuk ini.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
            'phone'    => 'nullable|string|max:20',
            'address'  => 'nullable|string|max:255',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        return response()->json($user, 201);
    }

    /**
     * [HANYA UNTUK ADMIN] Menampilkan detail satu user berdasarkan ID.
     */
    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return response()->json($user, 200);
    }

    /**
     * [HANYA UNTUK ADMIN] Memperbarui user berdasarkan ID.
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'name'     => 'sometimes|required|string|max:255',
            'email'    => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|nullable|string|min:6',
            'phone'    => 'nullable|string|max:20',
            'address'  => 'nullable|string|max:255',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        return response()->json($user, 200);
    }

    /**
     * [HANYA UNTUK ADMIN] Menghapus user berdasarkan ID.
     */
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted'], 200);
    }
}
