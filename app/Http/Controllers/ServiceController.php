<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // DIUBAH: Gunakan Storage facade

class ServiceController extends Controller
{
    /**
     * [PUBLIK] Menampilkan semua layanan.
     */
    public function index()
    {
        $services = Service::all();
        return response()->json($services);
    }

    /**
     * [ADMIN] Menyimpan service baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'img'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        if ($request->hasFile('img')) {
            // DIUBAH: Menyimpan file menggunakan Storage facade. Lebih aman dan standar.
            // File akan disimpan di storage/app/public/uploads/services
            $path = $request->file('img')->store('uploads/services', 'public');
            $validated['img'] = $path;
        }

        $service = Service::create($validated);

        return response()->json($service, 201);
    }

    /**
     * [ADMIN] Menampilkan detail satu service.
     */
    public function show(Service $service)
    {
        return response()->json($service);
    }

    /**
     * [ADMIN] Memperbarui service.
     */
    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'sometimes|required|numeric|min:0',
            'img'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        if ($request->hasFile('img')) {
            // BARU: Hapus gambar lama jika ada gambar baru yang di-upload
            if ($service->img) {
                Storage::disk('public')->delete($service->img);
            }

            // Simpan gambar baru dan dapatkan path-nya
            $path = $request->file('img')->store('uploads/services', 'public');
            $validated['img'] = $path;
        }

        $service->update($validated);

        return response()->json($service);
    }

    /**
     * [ADMIN] Menghapus service.
     */
    public function destroy(Service $service)
    {
        // BARU: Hapus file gambar terkait sebelum menghapus record dari database
        if ($service->img) {
            Storage::disk('public')->delete($service->img);
        }

        $service->delete();

        return response()->json(['message' => 'Service deleted successfully']);
    }
}