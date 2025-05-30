<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Auth::user()->bookings()->with(['vehicle', 'service'])->get();
        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_id' => 'required|exists:services,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'booking_time' => 'required',
            'complaint' => 'nullable|string',
        ]);

        $vehicle = Vehicle::find($request->vehicle_id);
        if ($vehicle->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized vehicle'], 403);
        }

        $booking = Booking::create([
            'user_id' => Auth::id(),
            'vehicle_id' => $request->vehicle_id,
            'service_id' => $request->service_id,
            'booking_date' => $request->booking_date,
            'booking_time' => $request->booking_time,
            'complaint' => $request->complaint,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Booking created',
            'booking' => $booking,
        ], 201);
    }

    public function show(Booking $booking)
    {
        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($booking->load(['vehicle', 'service']));
    }

    public function update(Request $request, Booking $booking)
    {
        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'booking_date' => 'sometimes|required|date|after_or_equal:today',
            'booking_time' => 'sometimes|required',
            'status' => 'sometimes|required|in:pending,confirmed,in_progress,completed,cancelled',
            'complaint' => 'nullable|string',
        ]);

        $booking->update($request->all());

        return response()->json([
            'message' => 'Booking updated',
            'booking' => $booking,
        ]);
    }

    public function destroy(Booking $booking)
    {
        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->delete();

        return response()->json(['message' => 'Booking deleted']);
    }
}
