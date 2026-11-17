<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArchivoLibro;
use App\Services\ArchivoUbicacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArchivoLibroController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'anio' => ['nullable', 'integer', 'between:2010,2100'],
            'estado_fisico' => ['nullable', 'string', 'max:30'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $libros = $this->filtrar($filtros)
            ->with(['bloqueActual.anaquel.area', 'bloqueDestino.anaquel.area', 'areaActual'])
            ->orderByDesc('anio_inicio')->orderBy('codigo')
            ->paginate((int) ($filtros['per_page'] ?? 30))->withQueryString();

        $libros->getCollection()->transform(fn ($libro) => $this->datosLibro($libro));
        return \App\Support\ApiPaginationResponse::make($libros);
    }

    public function buscar(Request $request)
    {
        return $this->index($request);
    }

    public function show(ArchivoLibro $libro)
    {
        $libro->load([
            'bloqueActual.anaquel.area', 'bloqueDestino.anaquel.area', 'areaActual',
            'documentos' => fn ($q) => $q->orderBy('pagina_inicio')->limit(100),
            'folios' => fn ($q) => $q->orderBy('numero_pagina')->limit(100),
            'movimientos' => fn ($q) => $q->latest('fecha_movimiento')->limit(30),
        ]);

        $datos = $this->datosLibro($libro);
        $datos['documentos'] = $libro->documentos->map(fn ($d) => [
            'id' => $d->id,
            'nombre' => $d->nombre,
            'nombre_original' => $d->nombre_original,
            'mime_type' => $d->mime_type,
            'tamano_bytes' => $d->tamano_bytes,
            'pagina_inicio' => $d->pagina_inicio,
            'pagina_fin' => $d->pagina_fin,
            'estado_revision' => $d->estado_revision,
        ])->values();
        $datos['folios'] = $libro->folios->map(fn ($f) => $f->only([
            'id', 'anio', 'folio', 'fecha_siniestro', 'numero_pagina', 'documento_id', 'pagina_documento', 'estado_verificacion',
        ]))->values();
        $datos['movimientos_recientes'] = $libro->movimientos->map(fn ($m) => $m->only([
            'id', 'tipo', 'origen_area_id', 'origen_bloque_id', 'origen_posicion',
            'destino_area_id', 'destino_bloque_id', 'destino_posicion', 'fecha_movimiento',
        ]))->values();

        return response()->json(['data' => $datos]);
    }

    public function porQr(string $uuid)
    {
        $libro = ArchivoLibro::where('qr_uuid', $uuid)->firstOrFail();
        return $this->show($libro);
    }

    public function store(Request $request, ArchivoUbicacionService $ubicaciones)
    {
        $datos = $request->validate(array_merge($this->reglas(), [
            'bloque_destino_id' => ['nullable', Rule::exists('archivo_bloques', 'id')->where('activo', true)],
            'posicion_destino' => ['nullable', 'integer', 'between:1,65535'],
        ]));

        if (!empty($datos['posicion_destino']) && empty($datos['bloque_destino_id'])) {
            return response()->json(['message' => 'Primero selecciona un bloque de destino.'], 422);
        }

        $bloqueId = $datos['bloque_destino_id'] ?? null;
        $posicion = $datos['posicion_destino'] ?? null;
        unset($datos['bloque_destino_id'], $datos['posicion_destino']);

        $datos['qr_uuid'] = (string) Str::uuid();
        $datos['estado_digitalizacion'] = 'pendiente';
        $datos['estado_fisico'] = 'por_ubicar';
        $datos['paginas_digitalizadas'] = 0;

        $libro = DB::transaction(function () use ($datos, $bloqueId, $posicion, $ubicaciones, $request) {
            $libro = ArchivoLibro::create($datos);
            if ($bloqueId) {
                $ubicaciones->asignarDestino($libro, (int) $bloqueId, $posicion, (int) $request->user()->id);
            }
            return $libro;
        }, 3);

        return response()->json([
            'message' => 'Libro registrado. Falta confirmar la ubicación física mediante QR.',
            'data' => $this->datosLibro($this->cargarLibro($libro)),
        ], 201);
    }

    public function update(Request $request, ArchivoLibro $libro)
    {
        $entrada = $request->isMethod('patch')
            ? array_merge($libro->only([
                'codigo', 'titulo', 'numero_libro', 'anio_inicio', 'anio_fin',
                'folio_inicial', 'folio_final', 'fecha_inicio', 'fecha_fin',
                'total_paginas', 'observaciones',
            ]), $request->all())
            : $request->all();
        $datos = validator($entrada, $this->reglas($libro))->validate();
        if (array_key_exists('total_paginas', $datos) && $datos['total_paginas'] !== null
            && (int) $datos['total_paginas'] < (int) $libro->paginas_digitalizadas) {
            return response()->json(['message' => 'El total no puede ser menor a las páginas digitalizadas.'], 422);
        }
        $libro->update($datos);

        return response()->json([
            'message' => 'Libro actualizado correctamente.',
            'data' => $this->datosLibro($this->cargarLibro($libro->fresh())),
        ]);
    }

    public function asignarDestino(Request $request, ArchivoLibro $libro, ArchivoUbicacionService $ubicaciones)
    {
        $datos = $request->validate([
            'bloque_destino_id' => ['required', 'integer', Rule::exists('archivo_bloques', 'id')->where('activo', true)],
            'posicion_destino' => ['nullable', 'integer', 'between:1,65535'],
        ]);

        $libro = $ubicaciones->asignarDestino($libro, (int) $datos['bloque_destino_id'], $datos['posicion_destino'] ?? null, (int) $request->user()->id);
        return response()->json([
            'message' => 'Ubicación de destino asignada.',
            'data' => $this->datosLibro($this->cargarLibro($libro)),
        ]);
    }

    private function cargarLibro(ArchivoLibro $libro): ArchivoLibro
    {
        return $libro->load(['bloqueActual.anaquel.area', 'bloqueDestino.anaquel.area', 'areaActual']);
    }

    private function datosLibro(ArchivoLibro $libro): array
    {
        return array_merge($libro->only([
            'id', 'codigo', 'qr_uuid', 'titulo', 'numero_libro', 'anio_inicio', 'anio_fin',
            'folio_inicial', 'folio_final', 'fecha_inicio', 'fecha_fin', 'total_paginas',
            'paginas_digitalizadas', 'estado_digitalizacion', 'estado_fisico',
            'bloque_destino_id', 'posicion_destino', 'area_actual_id', 'bloque_actual_id',
            'posicion_actual', 'observaciones',
        ]), [
            'area_actual' => $libro->areaActual?->only(['id', 'codigo', 'nombre']),
            'ubicacion_actual' => $this->ubicacion($libro->bloqueActual),
            'ubicacion_destino' => $this->ubicacion($libro->bloqueDestino),
        ]);
    }

    private function ubicacion($bloque): ?array
    {
        if (!$bloque) {
            return null;
        }
        return [
            'bloque_id' => $bloque->id,
            'bloque_codigo' => $bloque->codigo,
            'anaquel_id' => $bloque->anaquel?->id,
            'anaquel_codigo' => $bloque->anaquel?->codigo,
            'area_id' => $bloque->anaquel?->area?->id,
            'area_nombre' => $bloque->anaquel?->area?->nombre,
        ];
    }

    private function filtrar(array $filtros)
    {
        $q = trim((string) ($filtros['q'] ?? ''));
        return ArchivoLibro::query()
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . addcslashes($q, '%_\\') . '%';
                $query->where(function ($sub) use ($like, $q) {
                    $sub->where('codigo', 'like', $like)
                        ->orWhere('titulo', 'like', $like)
                        ->orWhere('numero_libro', 'like', $like)
                        ->orWhere('folio_inicial', $q)
                        ->orWhere('folio_final', $q)
                        ->orWhereHas('folios', fn ($f) => $f->where('folio', $q));
                });
            })
            ->when(isset($filtros['anio']), function ($query) use ($filtros) {
                $anio = (int) $filtros['anio'];
                $query->where('anio_inicio', '<=', $anio)
                    ->where(fn ($q) => $q->where('anio_fin', '>=', $anio)->orWhereNull('anio_fin'));
            })
            ->when(!empty($filtros['estado_fisico']), fn ($query) => $query->where('estado_fisico', $filtros['estado_fisico']));
    }

    private function reglas(?ArchivoLibro $libro = null): array
    {
        return [
            'codigo' => ['required', 'string', 'max:60', Rule::unique('archivo_libros', 'codigo')->ignore($libro?->id)],
            'titulo' => ['nullable', 'string', 'max:200'],
            'numero_libro' => ['nullable', 'string', 'max:40'],
            'anio_inicio' => ['nullable', 'integer', 'between:2010,2100'],
            'anio_fin' => ['nullable', 'integer', 'between:2010,2100', 'gte:anio_inicio'],
            'folio_inicial' => ['nullable', 'string', 'max:60'],
            'folio_final' => ['nullable', 'string', 'max:60'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'total_paginas' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
