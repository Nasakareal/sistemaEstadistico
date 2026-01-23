<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    public function show(Request $request)
    {
        $min    = env('ANDROID_MIN_VERSION', '1.0.0');
        $latest = env('ANDROID_LATEST_VERSION', '1.0.0');
        $force  = (bool) env('ANDROID_FORCE_UPDATE', false);

        $playUrl = 'https://play.google.com/store/apps/details?id=com.nasaka.seguridad_vial_app';
        $marketUrl = 'market://details?id=com.nasaka.seguridad_vial_app';

        return response()->json([
            'platform'       => 'android',
            'min_version'    => $min,
            'latest_version' => $latest,
            'force'          => $force,
            'message'        => $force
                ? 'Debes actualizar para continuar.'
                : 'Hay una actualización disponible.',
            'store_url'      => env('ANDROID_STORE_URL', $playUrl),
            'market_url'     => env('ANDROID_MARKET_URL', $marketUrl),
        ]);
    }
}
