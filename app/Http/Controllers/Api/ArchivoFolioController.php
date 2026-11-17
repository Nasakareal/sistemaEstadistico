<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArchivoLibro;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArchivoFolioController extends Controller
{
    public function index(Request $request, ArchivoLibro $libro)
    {
        $filtros = $request->validate([
            'folio' => ['nullable', 'string', 'max:80'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $folios = $libro->folios()
            ->when(!empty($filtros['folio']), fn ($q) => $q->where('folio', $filtros['folio']))
            ->orderBy('numero_pagina')->orderBy('id')
            ->paginate((int) ($filtros['per_page'] ?? 50))->withQueryString();

        $folios->getCollection()->transform(fn ($folio) => $folio->only([
            'id', 'libro_id', 'anio', 'folio', 'fecha_siniestro', 'numero_pagina',
            'documento_id', 'pagina_documento', 'estado_verificacion',
        ]));

        return \App\Support\ApiPaginationResponse::make($folios);
    }

    public function store(Request $request, ArchivoLibro $libro)
    {
        $datos = $request->validate([
            'anio' => ['required', 'integer', 'between:2010,2100'],
            'folio' => ['required', 'string', 'max:80'],
            'fecha_siniestro' => ['nullable', 'date'],
            'numero_pagina' => ['nullable', 'integer', 'between:1,100000'],
            'documento_id' => ['nullable', Rule::exists('archivo_documentos', 'id')->where('libro_id', $libro->id)],
            'pagina_documento' => ['nullable', 'integer', 'between:1,100000'],
        ]);

        if ($libro->total_paginas && isset($datos['numero_pagina'])
            && (int) $datos['numero_pagina'] > (int) $libro->total_paginas) {
            return response()->json([
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => ['numero_pagina' => ['La página está fuera del libro.']],
            ], 422);
        }
        if (isset($datos['pagina_documento']) && empty($datos['documento_id'])) {
            return response()->json([
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => ['documento_id' => ['Selecciona el documento al indicar una página digital.']],
            ], 422);
        }
        if (!empty($datos['documento_id']) && isset($datos['pagina_documento'])) {
            $documento = $libro->documentos()->findOrFail($datos['documento_id']);
            $totalPaginasDocumento = max(1, (int) $documento->pagina_fin - (int) $documento->pagina_inicio + 1);
            if ((int) $datos['pagina_documento'] > $totalPaginasDocumento) {
                return response()->json([
                    'message' => 'Los datos proporcionados no son válidos.',
                    'errors' => [
                        'pagina_documento' => ["La página digital debe estar entre 1 y {$totalPaginasDocumento}."],
                    ],
                ], 422);
            }
        }

        $datos['estado_verificacion'] = 'pendiente';
        $folio = $libro->folios()->create($datos);

        return response()->json([
            'message' => 'Folio registrado correctamente.',
            'data' => $folio->only([
                'id', 'libro_id', 'anio', 'folio', 'fecha_siniestro', 'numero_pagina',
                'documento_id', 'pagina_documento', 'estado_verificacion',
            ]),
        ], 201);
    }
}
