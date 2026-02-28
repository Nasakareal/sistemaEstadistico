<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Services\WhatsApp\NearestUnit;

class BotC5IController extends Controller
{
    public function recommend(Request $request)
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $top = (int) ($request->input('top') ?? 3);

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return response()->json(['ok' => false, 'error' => 'coords_invalid'], 422);
        }

        $r = NearestUnit::recommendForCoords((float)$lat, (float)$lng, $top);
        $text = NearestUnit::recommendationText($r);

        return response()->json([
            'ok' => true,
            'text' => $text,
        ], 200);
    }
}
