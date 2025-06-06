<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Display a listing of the user's bookings.
     */
    public function index()
    {
        // Eager load relationships for efficiency
        $bookings = Auth::user()->bookings()->with(['vehicle', 'service', 'schedule'])->get();
        return response()->json($bookings);
    }

    /**
     * Store a newly created booking in storage after validation.
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

        // --- Schedule Validation Logic ---
        $dayName = Carbon::parse($request->booking_date)->format('l');
        $schedule = Schedule::where('day', $dayName)->first();

        // Check 1: Is the shop open on the selected day?
        if (!$schedule) {
            return response()->json(['message' => 'Bengkel tutup pada hari yang dipilih.'], 400);
        }

        // Check 2: Is the booking time within operational hours?
        $bookingTime = $request->booking_time;
        if ($bookingTime < $schedule->open_time || $bookingTime > $schedule->close_time) {
            return response()->json([
                'message' => 'Jam booking di luar jam operasional.',
                'jam_operasional' => "Buka dari {$schedule->open_time} sampai {$schedule->close_time}"
            ], 400);
        }

        // --- End of Schedule Validation ---


        // --- Vehicle Authorization ---
        $vehicle = Vehicle::find($request->vehicle_id);
        if ($vehicle->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized vehicle'], 403);
        }
        // --- End of Vehicle Authorization ---


        $booking = Booking::create([
            'user_id' => Auth::id(),
            'vehicle_id' => $request->vehicle_id,
            'service_id' => $request->service_id,
            'schedule_id' => $schedule->id, // Store the corresponding schedule_id
            'booking_date' => $request->booking_date,
            'booking_time' => Carbon::parse($request->booking_time)->format('H:i:s'),
            'complaint' => $request->complaint,
            'status' => 'pending',
        ]);

        // Load all relationships to return in the response
        $booking->load(['vehicle', 'service', 'schedule']);

        return response()->json([
            'message' => 'Booking berhasil dibuat',
            'booking' => $booking,
        ], 201);
    }

    /**
     * Display the specified booking.
     */
    public function show(Booking $booking)
    {
        // Authorize: Make sure the user owns the booking
        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($booking->load(['vehicle', 'service', 'schedule']));
    }

    /**
     * Update the specified booking in storage.
     * Note: If you allow updating date/time, validation logic should also be added here.
     */
    public function update(Request $request, Booking $booking)
    {
        // Authorize: Make sure the user owns the booking
        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'sometimes|required|in:pending,confirmed,in_progress,completed,cancelled',
            'complaint' => 'nullable|string',
        ]);

        $booking->update($request->only(['status', 'complaint']));

        return response()->json([
            'message' => 'Booking updated',
            'booking' => $booking,
        ]);
    }

    /**
     * Remove the specified booking from storage.
     */
    public function destroy(Booking $booking)
    {
        // Authorize: Make sure the user owns the booking
        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->delete();

        return response()->json(['message' => 'Booking deleted']);
    }
}
