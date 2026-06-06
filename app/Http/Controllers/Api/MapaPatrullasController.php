<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLocation;
use App\Support\MapaPatrullasAccess;

class MapaPatrullasController extends Controller
{
    public function data()
    {
        $actor = request()->user();

        $usersQuery = User::query();

        if ($actor->hasRole('Superadmin')) {
            // Superadmin keeps the global view; every other role is scoped to its unit.
        } elseif ($actor->unidad_id) {
            $usersQuery->where('unidad_id', $actor->unidad_id);
        } else {
            $usersQuery->whereRaw('1=0');
        }

        MapaPatrullasAccess::applySiniestrosGroupLeadScope($usersQuery, $actor);

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
            ->with([
                'user:id,name,email,patrulla_id,unidad_id,turno_id,compartir_ubicacion,connection_status,last_seen_at',
                'user.patrulla:id,numero_economico',
            ])
            ->orderByDesc('user_locations.captured_at')
            ->get()
            ->map(function ($loc) {
                $user = $loc->user;

                return [
                    'user_id'         => $loc->user_id,
                    'name'            => optional($user)->name ?? ('User '.$loc->user_id),
                    'email'           => optional($user)->email,

                    'patrulla_id'     => optional($user)->patrulla_id,
                    'patrulla_numero' => optional(optional($user)->patrulla)->numero_economico,

                    'unidad_id'       => optional($user)->unidad_id,
                    'turno_id'        => optional($user)->turno_id,
                    'compartir_ubicacion' => (int) (optional($user)->compartir_ubicacion ?? 0),

                    'connection_status' => optional($user)->connection_status,
                    'last_seen_at'       => optional($user)->last_seen_at
                        ? optional($user)->last_seen_at->toDateTimeString()
                        : null,

                    'lat'            => (float) $loc->lat,
                    'lng'            => (float) $loc->lng,
                    'accuracy'       => $loc->accuracy !== null ? (float)$loc->accuracy : null,
                    'speed'          => $loc->speed !== null ? (float)$loc->speed : null,
                    'heading'        => $loc->heading !== null ? (float)$loc->heading : null,
                    'captured_at'    => $loc->captured_at ? $loc->captured_at->toDateTimeString() : null,
                ];
            });

        return response()->json($locations);
    }
}
