<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = Schedule::all();
        return response()->json($schedules);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'day' => 'required|string|max:20',
            'open_time' => 'required',
            'close_time' => 'required',
            'max_booking_per_slot' => 'required|integer|min:1',
        ]);

        $schedule = Schedule::create($request->all());

        return response()->json([
            'message' => 'Schedule created',
            'schedule' => $schedule,
        ], 201);
    }

    public function show(Schedule $schedule)
    {
        return response()->json($schedule);
    }

    public function update(Request $request, Schedule $schedule)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'day' => 'sometimes|required|string|max:20',
            'open_time' => 'sometimes|required',
            'close_time' => 'sometimes|required',
            'max_booking_per_slot' => 'sometimes|required|integer|min:1',
        ]);

        $schedule->update($request->all());

        return response()->json([
            'message' => 'Schedule updated',
            'schedule' => $schedule,
        ]);
    }

    public function destroy(Schedule $schedule)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $schedule->delete();

        return response()->json(['message' => 'Schedule deleted']);
    }
}
