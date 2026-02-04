<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'token' => ['required', 'string', 'min:10', 'max:255'],
            'platform' => ['nullable', 'string', 'max:20'],
        ]);

        if ($v->fails()) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors' => $v->errors(),
            ], 422);
        }

        $user = $request->user();

        DeviceToken::query()->updateOrCreate(
            ['token' => (string) $request->input('token')],
            [
                'user_id' => (int) $user->id,
                'platform' => $request->input('platform') ? (string) $request->input('platform') : null,
                'last_seen_at' => Carbon::now('America/Mexico_City'),
            ]
        );

        return response()->json([
            'message' => 'Token registrado.',
        ]);
    }
}
