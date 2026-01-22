<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class PersonalController extends Controller
{
    /**
     * GET /api/mi-personal
     * Lista el personal del mismo turno del jefe (y misma unidad base).
     * (No incluye al propio jefe por defecto)
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
     * Enciende / apaga compartir ubicación de un usuario del mismo turno del jefe.
     * Body: { "enabled": true|false }  (si no viene, hace toggle)
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

        if (array_key_exists('enabled', $validated)) {
            $enabled = (bool)$validated['enabled'];
        } else {
            $enabled = !$user->compartir_ubicacion;
        }

        $user->compartir_ubicacion = $enabled ? 1 : 0;
        $user->save();

        return response()->json([
            'message' => $enabled ? 'Ubicación activada' : 'Ubicación desactivada',
            'data' => [
                'user_id' => $user->id,
                'compartir_ubicacion' => (int)$user->compartir_ubicacion,
            ],
        ]);
    }

    /**
     * POST /api/mi-personal/ubicacion/todos
     * Activa o desactiva compartir ubicación a TODO su personal del mismo turno.
     * Body: { "enabled": true|false }
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

        $updated = $query->update([
            'compartir_ubicacion' => $enabled ? 1 : 0,
        ]);

        return response()->json([
            'message' => $enabled ? 'Ubicación activada para el personal' : 'Ubicación desactivada para el personal',
            'data' => [
                'updated' => (int)$updated,
                'enabled' => $enabled,
            ],
        ]);
    }
}
