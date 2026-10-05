<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    public function show(Request $request)
    {
        $min = (string) config('app_update.android_min_version', '1.0.0');
        $latest = (string) config('app_update.android_latest_version', '1.0.0');
        $configuredDownloadUrl = trim((string) config('app_update.android_download_url', ''));
        $downloadUrl = $configuredDownloadUrl !== ''
            ? $configuredDownloadUrl
            : url('/app/seguridad-vial-michoacan.apk');
        $forceRequested = (bool) config('app_update.android_force_update', false);
        // Una actualización obligatoria sólo es segura cuando producción
        // configuró explícitamente el APK que debe descargar.
        $force = $forceRequested
            && $configuredDownloadUrl !== ''
            && filter_var($configuredDownloadUrl, FILTER_VALIDATE_URL) !== false;

        return response()->json([
            'platform'       => 'android',
            'min_version'    => $min,
            'latest_version' => $latest,
            'force'          => $force,
            'message'        => $force
                ? 'Debes actualizar para continuar.'
                : 'Hay una actualización disponible.',
            // La aplicación es de distribución interna: nunca debe intentar
            // resolver este paquete en Google Play o en la tienda del fabricante.
            'store_url'      => $downloadUrl,
            'market_url'     => '',
        ]);
    }
}
