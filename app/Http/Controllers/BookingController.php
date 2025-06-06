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
    /**
     * Menampilkan semua booking milik pengguna yang sedang login.
     */
    public function index()
    {
        $bookings = Auth::user()->bookings()->with(['vehicle', 'service', 'schedule'])->get();
        return response()->json($bookings);
    }

    /**
     * Menyimpan booking baru setelah validasi.
     */
    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_id' => 'required|exists:services,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'booking_time' => 'required|date_format:H:i',
            'complaint' => 'nullable|string',
        ]);

        // --- Validasi Jadwal ---
        $dayName = Carbon::parse($request->booking_date)->format('l');
        $schedule = Schedule::where('day', $dayName)->first();

        if (!$schedule) {
            return response()->json(['message' => 'Bengkel tutup pada hari yang dipilih.'], 400);
        }

        $bookingTime = $request->booking_time;
        if ($bookingTime < $schedule->open_time || $bookingTime > $schedule->close_time) {
            return response()->json([
                'message' => 'Jam booking di luar jam operasional.',
                'jam_operasional' => "Buka dari {$schedule->open_time} sampai {$schedule->close_time}"
            ], 400);
        }

        // --- Otorisasi Kendaraan ---
        $vehicle = Vehicle::find($request->vehicle_id);
        if ($vehicle->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized vehicle'], 403);
        }

        $booking = Booking::create([
            'user_id' => Auth::id(),
            'vehicle_id' => $request->vehicle_id,
            'service_id' => $request->service_id,
            'schedule_id' => $schedule->id,
            'booking_date' => $request->booking_date,
            'booking_time' => Carbon::parse($request->booking_time)->format('H:i:s'),
            'complaint' => $request->complaint,
            'status' => 'pending',
        ]);

        $booking->load(['vehicle', 'service', 'schedule']);

        return response()->json([
            'message' => 'Booking berhasil dibuat',
            'booking' => $booking,
        ], 201);
    }

    /**
     * Menampilkan detail satu booking spesifik.
     */
    public function show(Booking $booking)
    {
        // Pastikan user hanya bisa melihat booking miliknya sendiri
        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($booking->load(['vehicle', 'service', 'schedule']));
    }

    /**
     * Memperbarui data booking (contoh: keluhan).
     * Hanya bisa dilakukan oleh pemilik booking.
     */
    public function update(Request $request, Booking $booking)
    {
        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'complaint' => 'sometimes|nullable|string',
            // Tambahkan validasi lain jika user boleh mengubah data lain
        ]);

        $booking->update($request->only(['complaint']));

        return response()->json([
            'message' => 'Booking berhasil diperbarui',
            'booking' => $booking->fresh()->load('vehicle', 'service', 'schedule'),
        ]);
    }

    /**
     * Menghapus booking.
     * Hanya bisa dilakukan oleh pemilik booking.
     */
    public function destroy(Booking $booking)
    {
        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->delete();

        return response()->json(['message' => 'Booking berhasil dihapus']);
    }

    /**
     * Memperbarui status booking.
     * Hanya bisa diakses oleh Admin.
     */
    public function updateStatus(Request $request, Booking $booking)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Hanya admin yang dapat mengakses ini.'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,confirmed,in_progress,completed,cancelled',
        ]);

        $booking->status = $request->status;
        $booking->save();

        return response()->json([
            'message' => 'Status booking berhasil diperbarui',
            'booking' => $booking->load('user', 'vehicle', 'service', 'schedule')
        ]);
    }
}
