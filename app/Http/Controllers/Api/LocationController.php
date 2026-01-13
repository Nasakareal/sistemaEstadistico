<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLocation;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * POST /api/location
     * Guarda (upsert) la última ubicación del usuario autenticado.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'lat'         => 'required|numeric|between:-90,90',
            'lng'         => 'required|numeric|between:-180,180',
            'accuracy'    => 'nullable|numeric|min:0',
            'speed'       => 'nullable|numeric|min:0',
            'heading'     => 'nullable|numeric|between:0,360',
            'captured_at' => 'nullable|date',
        ]);

        $location = UserLocation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'lat'         => $validated['lat'],
                'lng'         => $validated['lng'],
                'accuracy'    => $validated['accuracy'] ?? null,
                'speed'       => $validated['speed'] ?? null,
                'heading'     => $validated['heading'] ?? null,
                'captured_at' => $validated['captured_at'] ?? now(),
            ]
        );

        return response()->json([
            'message' => 'Ubicación guardada',
            'data'    => $location,
        ], 201);
    }

    /**
     * GET /api/location/last
     * Regresa la última ubicación del usuario autenticado.
     */
    public function last(Request $request)
    {
        $user = $request->user();

        $location = UserLocation::where('user_id', $user->id)->first();

        return response()->json([
            'data' => $location
        ]);
    }

    /**
     * GET /api/users/{user}/location/last
     * Regresa la última ubicación de un usuario específico (para mapa web/admin).
     */
    public function lastByUser(User $user)
    {
        $location = UserLocation::where('user_id', $user->id)->first();

        return response()->json([
            'data' => $location
        ]);
    }
}
