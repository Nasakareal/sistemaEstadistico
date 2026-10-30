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

        $payload = $apiController->index($request)->getData(true);

        return view('conduce_legalidad.index', [
            'operativos' => $payload['data'] ?? [],
            'resumen' => $payload['resumen'] ?? ['operativos' => 0, 'registros' => 0],
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

        $payload = $apiController->show($request, $operativo)->getData(true);

        return view('conduce_legalidad.show', [
            'operativo' => $payload['data'],
            'abilities' => $payload['abilities'] ?? [],
        ]);
    }
}
