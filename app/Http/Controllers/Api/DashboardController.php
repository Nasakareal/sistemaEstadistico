<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\HechoAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function home(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }

        $user->loadMissing([
            'unidad',
            'delegacion',
            'destacamento',
            'turno',
            'patrulla',
            'roles.permissions',
            'permissions',
        ]);

        $permissions = HechoAccess::filterPermissionsForUser(
            $user->roles
                ->flatMap(fn ($role) => $role->permissions->pluck('name'))
                ->merge($user->permissions->pluck('name'))
                ->unique(fn ($permission) => mb_strtolower(trim((string) $permission), 'UTF-8'))
                ->values(),
            $user
        )->values();

        $home = 'default';
        if ((int) ($user->unidad_id ?? 0) === 1 && $user->hasRole('Perito')) {
            $home = 'perito';
        }

        return response()->json([
            'ok' => true,
            'home' => $home,
            'role' => $user->roles->first()->name ?? null,
            'role_id' => $user->roles->first()->id ?? null,
            'permissions' => $permissions->all(),
            'flags' => [
                'is_jefe_grupo' => $user->roles->contains(fn ($role) => $role->name === 'Jefe de Grupo'),
                'is_subdirector' => $user->roles->contains(fn ($role) => $role->name === 'Subdirector'),
            ],
            'modules' => [
                'hechos' => [
                    'view' => $user->can('ver hechos'),
                    'create' => $user->can('crear hechos'),
                ],
                'busqueda' => $user->can('ver busqueda'),
                'mapa' => $user->can('ver mapa'),
                'gruas' => $user->can('ver gruas'),
                'estadisticas' => $user->can('ver estadisticas'),
                'actividades' => $user->can('ver actividades'),
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'unidad_id' => $user->unidad_id,
                'delegacion_id' => $user->delegacion_id,
                'destacamento_id' => $user->destacamento_id,
                'turno_id' => $user->turno_id,
                'patrulla_id' => $user->patrulla_id,
                'compartir_ubicacion' => (int) ($user->compartir_ubicacion ?? 0),
                'unidad' => $user->unidad ? [
                    'id' => $user->unidad->id,
                    'nombre' => $user->unidad->nombre,
                    'slug' => $user->unidad->slug,
                ] : null,
                'delegacion' => $user->delegacion ? [
                    'id' => $user->delegacion->id,
                    'nombre' => $user->delegacion->nombre,
                ] : null,
                'destacamento' => $user->destacamento ? [
                    'id' => $user->destacamento->id,
                    'nombre' => $user->destacamento->nombre,
                ] : null,
                'turno' => $user->turno ? [
                    'id' => $user->turno->id,
                    'nombre' => $user->turno->nombre,
                ] : null,
                'patrulla' => $user->patrulla ? [
                    'id' => $user->patrulla->id,
                    'numero_economico' => $user->patrulla->numero_economico,
                ] : null,
            ],
        ]);
    }

    public function accidentesHoy(Request $request)
    {
        $tz = config('app.timezone', 'America/Mexico_City');

        $start = Carbon::now($tz)->startOfDay();
        $end   = Carbon::now($tz)->endOfDay();

        $total = DB::table('hechos')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $rows = DB::table('hechos')
            ->selectRaw('HOUR(CONVERT_TZ(created_at, "+00:00", ?)) as hour, COUNT(*) as count', [$tz])
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->hour] = (int) $r->count;
        }

        $byHour = [];
        for ($h = 0; $h < 24; $h++) {
            $byHour[] = ['hour' => $h, 'count' => $map[$h] ?? 0];
        }

        return response()->json([
            'date'    => $start->format('Y-m-d'),
            'total'   => (int) $total,
            'by_hour' => $byHour,
        ]);
    }

    public function gruasHoy(Request $request)
    {
        $tz = config('app.timezone', 'America/Mexico_City');

        $start = Carbon::now($tz)->startOfDay();
        $end   = Carbon::now($tz)->endOfDay();

        $rows = DB::table('hechos as h')
            ->join('hecho_vehiculo as hv', 'hv.hecho_id', '=', 'h.id')
            ->join('vehiculos as v', 'v.id', '=', 'hv.vehiculo_id')
            ->whereBetween('h.created_at', [$start, $end])
            ->whereNotNull('v.grua')
            ->where('v.grua', '!=', '')
            ->selectRaw('v.grua as name, COUNT(*) as count')
            ->groupBy('v.grua')
            ->orderByDesc('count')
            ->get();

        $total = 0;
        $byGrua = [];

        foreach ($rows as $r) {
            $c = (int) $r->count;
            $total += $c;
            $byGrua[] = [
                'name'  => (string) $r->name,
                'count' => $c,
            ];
        }

        return response()->json([
            'date'    => $start->format('Y-m-d'),
            'total'   => (int) $total,
            'by_grua' => $byGrua,
        ]);
    }
}
