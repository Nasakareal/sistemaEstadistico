<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserLocation;
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
        if ($this->roleIs($actor,'Superadmin') || $this->roleIs($actor,'Administrador')) return $query;

        if ($this->roleIs($actor,'Subdirector')) {
            if ($actor->unidad_id) $query->where('unidad_id',$actor->unidad_id);
            else $query->whereRaw('1=0');
            return $query;
        }

        if ($this->roleIs($actor,'Jefe de Grupo')) {
            if ($actor->unidad_id) $query->where('unidad_id',$actor->unidad_id);
            if ($actor->turno_id) $query->where('turno_id',$actor->turno_id);
            return $query;
        }

        if ($actor->unidad_id) $query->where('unidad_id',$actor->unidad_id);
        if ($actor->turno_id) $query->where('turno_id',$actor->turno_id);
        return $query;
    }

    public function data()
    {
        $actor = request()->user();

        $usersQuery = User::query()->where('compartir_ubicacion',1);
        $usersQuery = $this->applyVisibility($actor,$usersQuery);

        $userIds = $usersQuery->pluck('id');

        $latest = UserLocation::query()->selectRaw('user_id, MAX(captured_at) AS max_captured_at')->whereIn('user_id',$userIds)->groupBy('user_id');

        return UserLocation::query()->joinSub($latest,'ul',function($join){
            $join->on('user_locations.user_id','=','ul.user_id')->on('user_locations.captured_at','=','ul.max_captured_at');
        })->with('user:id,name,email,patrulla_id')->orderByDesc('user_locations.captured_at')->get()->map(function($loc){
            return [
                'user_id'=>$loc->user_id,
                'name'=>optional($loc->user)->name,
                'email'=>optional($loc->user)->email,
                'patrulla_id'=>optional($loc->user)->patrulla_id,
                'lat'=>(float)$loc->lat,
                'lng'=>(float)$loc->lng,
                'captured_at'=>$loc->captured_at? $loc->captured_at->toDateTimeString():null
            ];
        });
    }

    public function miPersonal()
    {
        $actor = request()->user();

        $q = User::query()->select('id','name','email','patrulla_id','compartir_ubicacion','unidad_id','turno_id');
        $q = $this->applyVisibility($actor,$q);

        return response()->json([
            'data'=>$q->orderBy('name')->get()->map(function($u){
                return [
                    'id'=>$u->id,
                    'name'=>$u->name,
                    'email'=>$u->email,
                    'patrulla_id'=>$u->patrulla_id,
                    'compartir_ubicacion'=>(int)$u->compartir_ubicacion
                ];
            })
        ]);
    }

    public function toggleUbicacionUsuario(Request $request, User $user)
    {
        $actor = $request->user();
        abort_unless($this->roleIs($actor,'Jefe de Grupo'),403);

        if ($actor->unidad_id && $user->unidad_id != $actor->unidad_id) abort(403);
        if ($actor->turno_id && $user->turno_id != $actor->turno_id) abort(403);

        $user->compartir_ubicacion = $request->boolean('enabled') ? 1 : 0;
        $user->save();

        return response()->json(['ok'=>true]);
    }

    public function toggleUbicacionTodos(Request $request)
    {
        $actor = $request->user();
        abort_unless($this->roleIs($actor,'Jefe de Grupo'),403);

        $enabled = $request->boolean('enabled');

        $q = User::query();
        if ($actor->unidad_id) $q->where('unidad_id',$actor->unidad_id);
        if ($actor->turno_id) $q->where('turno_id',$actor->turno_id);

        $q->update(['compartir_ubicacion'=>$enabled?1:0]);

        return response()->json(['ok'=>true]);
    }
}
