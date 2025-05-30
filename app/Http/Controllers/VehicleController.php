<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Auth::user()->vehicles()->get();
        return response()->json($vehicles);
    }

    public function store(Request $request)
    {
        $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'license_plate' => 'required|string|max:100|unique:vehicles',
            'year' => 'nullable|integer',
        ]);

        $vehicle = Auth::user()->vehicles()->create($request->all());

        return response()->json([
            'message' => 'Vehicle created',
            'vehicle' => $vehicle,
        ], 201);
    }

    public function show(Vehicle $vehicle)
    {
        if ($vehicle->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($vehicle);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'brand' => 'sometimes|required|string|max:255',
            'model' => 'sometimes|required|string|max:255',
            'license_plate' => 'sometimes|required|string|max:100|unique:vehicles,license_plate,' . $vehicle->id,
            'year' => 'nullable|integer',
        ]);

        $vehicle->update($request->all());

        return response()->json([
            'message' => 'Vehicle updated',
            'vehicle' => $vehicle,
        ]);
    }

    public function destroy(Vehicle $vehicle)
    {
        if ($vehicle->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted']);
    }
}
