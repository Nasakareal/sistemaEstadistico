<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserLocation;

class MapaPatrullasController extends Controller
{
    public function index()
    {
        return view('mapa.index');
    }

    public function data()
    {
        $lastIds = UserLocation::selectRaw('MAX(id) as id')
            ->groupBy('user_id')
            ->pluck('id');

        $locations = UserLocation::with('user')
            ->whereIn('id', $lastIds)
            ->orderBy('captured_at', 'desc')
            ->get()
            ->map(function ($loc) {
                return [
                    'user_id'     => $loc->user_id,
                    'name'        => optional($loc->user)->name ?? ('User '.$loc->user_id),
                    'lat'         => (float) $loc->lat,
                    'lng'         => (float) $loc->lng,
                    'captured_at' => optional($loc->captured_at)->toDateTimeString() ?? null,
                ];
            });

        return response()->json($locations);
    }
}
