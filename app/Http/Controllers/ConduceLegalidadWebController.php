<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ConduceLegalidadController as ApiConduceLegalidadController;
use App\Models\ConduceLegalidadOperativo;
use Illuminate\Http\Request;

class ConduceLegalidadWebController extends Controller
{
    public function index(Request $request, ApiConduceLegalidadController $apiController)
    {
        // La app móvil lista todos los operativos activos, aunque hayan iniciado
        // en un mes anterior. Evitamos que un parámetro conservado en la URL
        // o por el navegador oculte operativos que todavía siguen activos.
        $request->query->remove('mes');
        $request->request->remove('mes');

        $request->merge([
            'tipo_operativo' => 'conduce_legalidad',
            'per_page' => $request->input('per_page', 24),
        ]);
        $request->attributes->set('conduce_legalidad_web_scope', true);

        $payload = $apiController->index($request)->getData(true);

        return view('conduce_legalidad.index', [
            'operativos' => $payload['data'] ?? [],
            'resumen' => $payload['resumen'] ?? [
                'operativos' => 0,
                'registros' => 0,
                'personas' => 0,
                'vehiculos' => 0,
            ],
            'pagination' => $payload['pagination'] ?? [],
            'abilities' => $payload['abilities'] ?? [],
        ]);
    }

    public function show(
        Request $request,
        ConduceLegalidadOperativo $operativo,
        ApiConduceLegalidadController $apiController
    ) {
        abort_unless($operativo->tipo_operativo === 'conduce_legalidad', 404);
        abort_unless($this->puedeVerEnWeb($request->user(), $operativo), 404);

        $request->attributes->set('conduce_legalidad_web_scope', true);

        $payload = $apiController->show($request, $operativo)->getData(true);

        return view('conduce_legalidad.show', [
            'operativo' => $payload['data'],
            'abilities' => $payload['abilities'] ?? [],
        ]);
    }

    private function puedeVerEnWeb($user, ConduceLegalidadOperativo $operativo): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasRole('Superadmin') || (int) ($user->unidad_id ?? 0) === 3) {
            return true;
        }

        $unidadId = (int) ($user->unidad_id ?? 0);
        if ($unidadId <= 0) {
            return false;
        }

        $operativo->loadMissing('creador');
        $unidadOperativo = (int) ($operativo->unidad_id ?? optional($operativo->creador)->unidad_id ?? 0);

        return $unidadOperativo === $unidadId;
    }
}
