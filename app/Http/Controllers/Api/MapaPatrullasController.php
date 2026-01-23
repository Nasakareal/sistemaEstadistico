<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLocation;

class MapaPatrullasController extends Controller
{
    public function data()
    {
        $actor = request()->user();

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

        $locations = UserLocation::query()
            ->joinSub($latest, 'ul', function ($join) {
                $join->on('user_locations.user_id', '=', 'ul.user_id')
                     ->on('user_locations.captured_at', '=', 'ul.max_captured_at');
            })
            ->with('user:id,name,email,patrulla_id,compartir_ubicacion')
            ->orderByDesc('user_locations.captured_at')
            ->get()
            ->map(function ($loc) {
                return [
                    'user_id'     => $loc->user_id,
                    'name'        => optional($loc->user)->name ?? ('User '.$loc->user_id),
                    'email'       => optional($loc->user)->email,
                    'patrulla_id' => optional($loc->user)->patrulla_id,
                    'lat'         => (float) $loc->lat,
                    'lng'         => (float) $loc->lng,
                    'accuracy'    => $loc->accuracy !== null ? (float)$loc->accuracy : null,
                    'speed'       => $loc->speed !== null ? (float)$loc->speed : null,
                    'heading'     => $loc->heading !== null ? (float)$loc->heading : null,
                    'captured_at' => $loc->captured_at ? $loc->captured_at->toDateTimeString() : null,
                ];
            });

        return response()->json($locations);
    }
}
