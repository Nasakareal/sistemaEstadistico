<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountSettingsController extends Controller
{
    public function show(Request $request)
    {
        return response()->json([
            'data' => $this->settings($request),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'receive_waze_alerts' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $user->receive_waze_alerts = (int) ($user->unidad_id ?? 0) === 5
            ? false
            : (bool) $validated['receive_waze_alerts'];
        $user->save();

        return response()->json([
            'message' => 'Ajustes guardados correctamente.',
            'data' => $this->settings($request),
        ]);
    }

    private function settings(Request $request): array
    {
        $user = $request->user();
        return [
            'receive_waze_alerts' => (int) ($user->unidad_id ?? 0) === 5
                ? false
                : (bool) ($user->receive_waze_alerts ?? true),
        ];
    }
}
