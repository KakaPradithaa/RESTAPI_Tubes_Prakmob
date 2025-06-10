<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    // ... (Metode-metode dari jawaban sebelumnya akan kita letakkan di sini)
    
    /**
     * [UNTUK ADMIN] Menampilkan SEMUA booking dari seluruh pengguna.
     * Dipanggil oleh: GET /api/admin/bookings
     */
    public function index()
    {
        $bookings = Booking::with(['user', 'vehicle', 'service', 'schedule'])->latest()->get();
        return response()->json($bookings);
    }

    /**
     * [UNTUK USER] Menampilkan booking milik pengguna yang sedang login.
     * Dipanggil oleh: GET /api/my-bookings
     */
    public function myBookings()
    {
        $bookings = Auth::user()->bookings()->with(['vehicle', 'service', 'schedule'])->latest()->get();
        return response()->json($bookings);
    }

    /**
     * [UNTUK USER] Menyimpan booking baru setelah validasi.
     * Dipanggil oleh: POST /api/bookings
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_id' => 'required|exists:services,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'booking_time' => 'required|date_format:H:i',
            'complaint' => 'nullable|string',
        ]);

        $vehicle = Auth::user()->vehicles()->find($validated['vehicle_id']);
        if (!$vehicle) {
            return response()->json(['message' => 'Kendaraan ini bukan milik Anda.'], 403);
        }

        $dayName = Carbon::parse($validated['booking_date'])->format('l');
        $schedule = Schedule::where('day', $dayName)->first();

        if (!$schedule || $validated['booking_time'] < $schedule->open_time || $validated['booking_time'] > $schedule->close_time) {
            return response()->json(['message' => 'Jadwal booking tidak tersedia.'], 400);
        }

        $booking = Booking::create(array_merge($validated, [
            'user_id' => Auth::id(),
            'schedule_id' => $schedule->id,
            'status' => 'pending',
        ]));

        return response()->json([
            'message' => 'Booking berhasil dibuat',
            'booking' => $booking->load(['vehicle', 'service', 'schedule']),
        ], 201);
    }
    
    /**
     * [UNTUK USER & ADMIN] Menampilkan detail satu booking spesifik.
     * Dipanggil oleh: GET /api/bookings/{booking}
     */
    public function show(Booking $booking)
    {
        $user = Auth::user();
        if ($booking->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json($booking->load(['user', 'vehicle', 'service', 'schedule']));
    }

    /**
     * [UNTUK USER] Memperbarui data booking (contoh: keluhan).
     * Dipanggil oleh: PUT /api/bookings/{booking}
     */
    public function update(Request $request, Booking $booking)
    {
        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate(['complaint' => 'sometimes|nullable|string']);
        $booking->update($validated);

        return response()->json([
            'message' => 'Booking berhasil diperbarui',
            'booking' => $booking->fresh()->load('vehicle', 'service', 'schedule'),
        ]);
    }

    /**
     * [UNTUK ADMIN] Memperbarui status booking.
     * Dipanggil oleh: PATCH /api/admin/bookings/{booking}/status
     */
    public function updateStatus(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,in_progress,completed,cancelled',
        ]);

        $booking->update($validated);

        return response()->json([
            'message' => 'Status booking berhasil diperbarui',
            'booking' => $booking->load('user', 'vehicle', 'service', 'schedule')
        ]);
    }

    /**
     * [UNTUK USER & ADMIN] Menghapus booking.
     * Dipanggil oleh: DELETE /api/bookings/{booking} ATAU /api/admin/bookings/{booking}
     */
    public function destroy(Booking $booking)
    {
        $user = Auth::user();
        if ($booking->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->delete();
        return response()->json(['message' => 'Booking berhasil dihapus']);
    }
}