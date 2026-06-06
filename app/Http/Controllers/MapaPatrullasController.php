<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserLocation;
use App\Support\MapaPatrullasAccess;
use Illuminate\Http\Request;

class MapaPatrullasController extends Controller
{
    public function index()
    {
        return view('mapa.index');
    }

    private function roleIs(User $u, string $name): bool
    {
        return method_exists($u,'hasRole') && ($u->hasRole($name) || $u->hasRole(mb_strtolower($name)) || $u->hasRole(mb_strtoupper($name)));
    }

    private function applyVisibility(User $actor, $query, bool $subdirectorSeesAllTurns = false)
    {
        if ($this->roleIs($actor,'Superadmin')) return $query;

        if ($actor->unidad_id) $query->where('users.unidad_id',$actor->unidad_id);
        else $query->whereRaw('1=0');

        MapaPatrullasAccess::applySiniestrosGroupLeadScope($query, $actor);

        return $query;
    }

    public function data()
    {
        $actor = request()->user();

        $usersQuery = User::query()
            ->from('users')
            ->leftJoin('patrullas', 'patrullas.id', '=', 'users.patrulla_id')
            ->where('users.compartir_ubicacion', 1);

        $usersQuery = $this->applyVisibility($actor, $usersQuery);

        $userIds = $usersQuery->pluck('users.id');

        $latest = UserLocation::query()
            ->selectRaw('user_id, MAX(captured_at) AS max_captured_at')
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id');

        return UserLocation::query()
            ->joinSub($latest, 'ul', function($join){
                $join->on('user_locations.user_id','=','ul.user_id')
                     ->on('user_locations.captured_at','=','ul.max_captured_at');
            })
            ->join('users', 'users.id', '=', 'user_locations.user_id')
            ->leftJoin('patrullas', 'patrullas.id', '=', 'users.patrulla_id')
            ->orderByDesc('user_locations.captured_at')
            ->get([
                'user_locations.user_id',
                'users.name',
                'users.email',
                'users.patrulla_id',
                'patrullas.numero_economico as numero_economico',
                'user_locations.lat',
                'user_locations.lng',
                'user_locations.captured_at',
            ])
            ->map(function($row){
                return [
                    'user_id'          => (int)$row->user_id,
                    'name'             => $row->name,
                    'email'            => $row->email,
                    'patrulla_id'      => $row->patrulla_id,
                    'numero_economico' => $row->numero_economico,
                    'lat'              => (float)$row->lat,
                    'lng'              => (float)$row->lng,
                    'captured_at'      => $row->captured_at ? \Carbon\Carbon::parse($row->captured_at)->toDateTimeString() : null,
                ];
            });
    }

    public function miPersonal()
    {
        $actor = request()->user();

        $q = User::query()
            ->from('users')
            ->leftJoin('patrullas', 'patrullas.id', '=', 'users.patrulla_id')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.patrulla_id',
                'users.compartir_ubicacion',
                'users.unidad_id',
                'users.turno_id',
                'patrullas.numero_economico as numero_economico',
            ]);

        $q = $this->applyVisibility($actor, $q);

        return response()->json([
            'data' => $q->orderBy('users.name')->get()->map(function($u){
                return [
                    'id'               => (int)$u->id,
                    'name'             => $u->name,
                    'email'            => $u->email,
                    'patrulla_id'      => $u->patrulla_id,
                    'numero_economico' => $u->numero_economico,
                    'compartir_ubicacion' => (int)$u->compartir_ubicacion,
                ];
            })
        ]);
    }

    public function toggleUbicacionUsuario(Request $request, User $user)
    {
        $actor = $request->user();
        abort_unless(MapaPatrullasAccess::isSiniestrosGroupLead($actor),403);

        if ($actor->unidad_id && $user->unidad_id != $actor->unidad_id) abort(403);
        if (!MapaPatrullasAccess::canManageScopedUser($actor, $user)) abort(403);

        $user->compartir_ubicacion = $request->boolean('enabled') ? 1 : 0;
        $user->save();

        return response()->json(['ok'=>true]);
    }

    public function toggleUbicacionTodos(Request $request)
    {
        $actor = $request->user();
        abort_unless(MapaPatrullasAccess::isSiniestrosGroupLead($actor),403);

        $enabled = $request->boolean('enabled');

        $q = User::query();
        if ($actor->unidad_id) $q->where('unidad_id',$actor->unidad_id);
        if ($actor->turno_id) $q->where('turno_id',$actor->turno_id);

        MapaPatrullasAccess::applySiniestrosGroupLeadScope($q, $actor);

        $q->update(['compartir_ubicacion'=>$enabled?1:0]);

        return response()->json(['ok'=>true]);
    }
}
