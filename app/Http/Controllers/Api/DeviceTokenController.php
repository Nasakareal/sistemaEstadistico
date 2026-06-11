<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'token' => ['required', 'string', 'min:10', 'max:255'],
            'platform' => ['required', 'string', 'max:20'],
        ]);

        if ($v->fails()) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors' => $v->errors(),
            ], 422);
        }

        $user = $request->user();

        $token = (string) $request->input('token');
        $platform = (string) $request->input('platform');
        $now = Carbon::now('America/Mexico_City');

        if ($this->isVialidadesUrbanasNoWazeUser($user)) {
            DeviceToken::query()
                ->where('user_id', (int) $user->id)
                ->delete();

            return response()->json([
                'message' => 'Token no registrado para este rol.',
            ]);
        }

        DB::transaction(function () use ($user, $token, $platform, $now) {
            DeviceToken::query()
                ->where('user_id', (int) $user->id)
                ->where('platform', $platform)
                ->where('token', '!=', $token)
                ->delete();

            DB::table('device_tokens')->upsert(
                [[
                    'user_id' => (int) $user->id,
                    'token' => $token,
                    'platform' => $platform,
                    'last_seen_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['token'],
                ['user_id', 'platform', 'last_seen_at', 'updated_at']
            );
        });

        return response()->json([
            'message' => 'Token registrado.',
        ]);
    }

    private function isVialidadesUrbanasNoWazeUser($user): bool
    {
        if ((int) ($user->unidad_id ?? 0) !== 5) {
            return false;
        }

        return $user->hasAnyRole([
            'Motociclista',
            'Agente Vial',
            'Fenix',
            'Fénix',
        ]);
    }
}
