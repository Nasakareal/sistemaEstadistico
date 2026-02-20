<?php

namespace App\Http\Controllers;

use App\Models\WazeAlert;
use App\Models\WazeAlertRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WazeAlertWebController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $q = WazeAlert::query();

        if ($request->filled('tipo')) {
            $q->where('type', $request->tipo);
        }
        if ($request->filled('solo')) {
            if ($request->solo === 'accidentes') {
                $q->where(function ($x) {
                    $x->where('type', 'ACCIDENT')
                      ->orWhere('subtype', 'like', '%ACCIDENT%')
                      ->orWhere('subtype', 'like', '%CRASH%');
                });
            }
        }

        $q->leftJoin('waze_alert_reads as war', function ($join) use ($userId) {
            $join->on('war.waze_alert_id', '=', 'waze_alerts.id')
                 ->where('war.user_id', '=', $userId);
        })
        ->select('waze_alerts.*')
        ->addSelect(\DB::raw('CASE WHEN war.id IS NULL THEN 0 ELSE 1 END as is_read'))
        ->orderByDesc('published_at')
        ->orderByDesc('id');

        $alerts = $q->paginate(25)->withQueryString();

        return view('waze.alerts.index', compact('alerts'));
    }

    public function unreadCount()
    {
        $userId = Auth::id();

        $count = WazeAlert::query()
            ->leftJoin('waze_alert_reads as war', function ($join) use ($userId) {
                $join->on('war.waze_alert_id', '=', 'waze_alerts.id')
                     ->where('war.user_id', '=', $userId);
            })
            ->whereNull('war.id')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markRead(WazeAlert $alert)
    {
        $userId = Auth::id();

        WazeAlertRead::updateOrCreate(
            ['user_id' => $userId, 'waze_alert_id' => $alert->id],
            ['read_at' => now()]
        );

        return back()->with('success', 'Alerta marcada como leída');
    }

    public function markAllRead()
    {
        $userId = Auth::id();

        $ids = WazeAlert::pluck('id')->toArray();

        foreach (array_chunk($ids, 500) as $chunk) {
            $rows = [];
            $now = now();
            foreach ($chunk as $id) {
                $rows[] = [
                    'user_id' => $userId,
                    'waze_alert_id' => $id,
                    'read_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            \DB::table('waze_alert_reads')->insertOrIgnore($rows);
        }

        return back()->with('success', 'Todas las alertas quedaron como leídas');
    }
}
