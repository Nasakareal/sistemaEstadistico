<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $alerts = Alert::query()
            ->where('to_user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json($alerts);
    }

    public function markRead(Request $request, Alert $alert)
    {
        $user = $request->user();

        if ((int)$alert->to_user_id !== (int)$user->id) {
            abort(403, 'No autorizado.');
        }

        if (!$alert->read_at) {
            $alert->read_at = now();
            $alert->save();
        }

        return response()->json([
            'message' => 'OK',
            'data' => $alert,
        ]);
    }

    public function markReadAll(Request $request)
    {
        $user = $request->user();

        Alert::where('to_user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'OK',
        ]);
    }
}
