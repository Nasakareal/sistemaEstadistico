<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Waze\WazeFeedService;
use Illuminate\Http\Request;

class WazeFeedController extends Controller
{
    public function incidents(Request $request, WazeFeedService $service)
    {
        $token = (string) $request->query('token', '');

        if ($token !== (string) config('waze.feed_token')) {
            return response()->json([
                'message' => 'Token inválido.'
            ], 403);
        }

        return response()->json($service->buildIncidentsFeed());
    }
}
