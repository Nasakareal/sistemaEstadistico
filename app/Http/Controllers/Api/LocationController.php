<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLocation;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        if ((int)($user->compartir_ubicacion ?? 0) !== 1) {
            return response()->json([
                'message' => 'Tu ubicación está desactivada por tu jefe o por administración. No se guardó tu ubicación.',
            ], 200);
        }

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

    public function last(Request $request)
    {
        $user = $request->user();

        $location = UserLocation::where('user_id', $user->id)->first();

        return response()->json([
            'data' => $location,
        ]);
    }

    public function lastByUser(Request $request, User $user)
    {
        $actor = $request->user();

        if (!$this->canManageUser($actor, $user)) {
            abort(403, 'No autorizado.');
        }

        if ((int)($user->compartir_ubicacion ?? 0) !== 1) {
            return response()->json([
                'data' => null,
                'message' => 'La ubicación de este usuario está desactivada.',
            ], 200);
        }

        $location = UserLocation::where('user_id', $user->id)->first();

        return response()->json([
            'data' => $location,
        ]);
    }

    public function index(Request $request)
    {
        $actor = $request->user();

        $usersQuery = User::query()
            ->where('compartir_ubicacion', 1);

        if ($actor->hasRole('subdirector')) {
            if ($actor->unidad_id) {
                $usersQuery->where('unidad_id', $actor->unidad_id);
            } else {
                $usersQuery->whereRaw('1=0');
            }
        } else {
            if ($actor->unidad_id) {
                $usersQuery->where('unidad_id', $actor->unidad_id);
            }
            if ($actor->turno_id) {
                $usersQuery->where('turno_id', $actor->turno_id);
            }
        }

        $userIds = $usersQuery->pluck('id');

        $latest = UserLocation::query()
            ->selectRaw('user_id, MAX(captured_at) AS max_captured_at')
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id');

        $data = UserLocation::query()
            ->joinSub($latest, 'ul', function ($join) {
                $join->on('user_locations.user_id', '=', 'ul.user_id')
                     ->on('user_locations.captured_at', '=', 'ul.max_captured_at');
            })
            ->join('users', 'users.id', '=', 'user_locations.user_id')
            ->orderByDesc('user_locations.captured_at')
            ->get([
                'user_locations.id',
                'user_locations.user_id',
                'users.name',
                'users.email',
                'users.patrulla_id',
                'user_locations.lat',
                'user_locations.lng',
                'user_locations.accuracy',
                'user_locations.speed',
                'user_locations.heading',
                'user_locations.captured_at',
            ]);

        return response()->json([
            'data' => $data,
        ]);
    }

    private function canManageUser(User $actor, User $target): bool
    {
        if ($actor->unidad_id && (int)$target->unidad_id !== (int)$actor->unidad_id) {
            return false;
        }

        if ($actor->hasRole('subdirector')) {
            return true;
        }

        if ($actor->turno_id && (int)$target->turno_id !== (int)$actor->turno_id) {
            return false;
        }

        return true;
    }
}
