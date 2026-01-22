<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonalController extends Controller
{
    /**
     * GET /api/mi-personal
     */
    public function index(Request $request)
    {
        $actor = $request->user();
        $q = trim((string)$request->query('q'));

        $personal = User::query()
            ->whereKeyNot($actor->id)
            ->when($actor->unidad_id, function ($query) use ($actor) {
                $query->where('unidad_id', $actor->unidad_id);
            })
            ->when($actor->turno_id, function ($query) use ($actor) {
                $query->where('turno_id', $actor->turno_id);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', '%' . $q . '%')
                       ->orWhere('email', 'like', '%' . $q . '%');
                });
            })
            ->select([
                'id',
                'name',
                'email',
                'estado',
                'area',
                'unidad_id',
                'turno_id',
                'patrulla_id',
                'compartir_ubicacion',
            ])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $personal,
        ]);
    }

    /**
     * POST /api/mi-personal/{user}/ubicacion
     * Body: { "enabled": true|false }  (si no viene, toggle)
     *
     * ✅ Cuando enabled=false => BORRA user_locations de ese usuario
     */
    public function toggleUbicacion(Request $request, User $user)
    {
        $actor = $request->user();

        // Seguridad: solo sobre personal de su misma unidad/turno
        if ($actor->unidad_id && $user->unidad_id !== $actor->unidad_id) {
            abort(403, 'No autorizado.');
        }
        if ($actor->turno_id && $user->turno_id !== $actor->turno_id) {
            abort(403, 'No autorizado.');
        }

        // No permitir que el jefe se apague a sí mismo desde aquí
        if ($user->id === $actor->id) {
            abort(422, 'No puedes modificar tu propia ubicación desde este endpoint.');
        }

        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
        ]);

        $enabled = array_key_exists('enabled', $validated)
            ? (bool)$validated['enabled']
            : !$user->compartir_ubicacion;

        $user->compartir_ubicacion = $enabled ? 1 : 0;
        $user->save();

        $deleted = 0;

        // ✅ Limpia ubicaciones guardadas si se apaga
        if (!$enabled) {
            $deleted = DB::table('user_locations')
                ->where('user_id', $user->id)
                ->delete();
        }

        return response()->json([
            'message' => $enabled ? 'Ubicación activada' : 'Ubicación desactivada',
            'data' => [
                'user_id' => $user->id,
                'compartir_ubicacion' => (int)$user->compartir_ubicacion,
                'deleted_locations' => (int)$deleted,
            ],
        ]);
    }

    /**
     * POST /api/mi-personal/ubicacion/todos
     * Body: { "enabled": true|false }
     *
     * ✅ Si enabled=false => BORRA user_locations de todos los afectados
     */
    public function toggleUbicacionTodos(Request $request)
    {
        $actor = $request->user();

        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $enabled = (bool)$validated['enabled'];

        $query = User::query()
            ->whereKeyNot($actor->id)
            ->when($actor->unidad_id, function ($q) use ($actor) {
                $q->where('unidad_id', $actor->unidad_id);
            })
            ->when($actor->turno_id, function ($q) use ($actor) {
                $q->where('turno_id', $actor->turno_id);
            });

        // IDs afectados (para borrar locations)
        $ids = $query->pluck('id')->toArray();

        $updated = User::query()->whereIn('id', $ids)->update([
            'compartir_ubicacion' => $enabled ? 1 : 0,
        ]);

        $deleted = 0;

        if (!$enabled && !empty($ids)) {
            $deleted = DB::table('user_locations')
                ->whereIn('user_id', $ids)
                ->delete();
        }

        return response()->json([
            'message' => $enabled ? 'Ubicación activada para el personal' : 'Ubicación desactivada para el personal',
            'data' => [
                'updated' => (int)$updated,
                'enabled' => $enabled,
                'deleted_locations' => (int)$deleted,
            ],
        ]);
    }

    /**
     * POST /api/mi-personal/{user}/ubicacion/limpiar
     * ✅ Borra user_locations manualmente (por si lo necesitas desde UI)
     */
    public function limpiarUbicacionUsuario(Request $request, User $user)
    {
        $actor = $request->user();

        if ($actor->unidad_id && $user->unidad_id !== $actor->unidad_id) {
            abort(403, 'No autorizado.');
        }
        if ($actor->turno_id && $user->turno_id !== $actor->turno_id) {
            abort(403, 'No autorizado.');
        }
        if ($user->id === $actor->id) {
            abort(422, 'No puedes limpiar tu propia ubicación desde este endpoint.');
        }

        $deleted = DB::table('user_locations')
            ->where('user_id', $user->id)
            ->delete();

        return response()->json([
            'message' => 'Ubicaciones eliminadas',
            'data' => [
                'user_id' => $user->id,
                'deleted_locations' => (int)$deleted,
            ],
        ]);
    }

    /**
     * POST /api/mi-personal/ubicacion/limpiar-todos
     * ✅ Borra user_locations de todo tu personal
     */
    public function limpiarUbicacionTodos(Request $request)
    {
        $actor = $request->user();

        $q = User::query()
            ->whereKeyNot($actor->id)
            ->when($actor->unidad_id, function ($qq) use ($actor) {
                $qq->where('unidad_id', $actor->unidad_id);
            })
            ->when($actor->turno_id, function ($qq) use ($actor) {
                $qq->where('turno_id', $actor->turno_id);
            });

        $ids = $q->pluck('id')->toArray();

        $deleted = 0;
        if (!empty($ids)) {
            $deleted = DB::table('user_locations')
                ->whereIn('user_id', $ids)
                ->delete();
        }

        return response()->json([
            'message' => 'Ubicaciones eliminadas del personal',
            'data' => [
                'deleted_locations' => (int)$deleted,
            ],
        ]);
    }
}
