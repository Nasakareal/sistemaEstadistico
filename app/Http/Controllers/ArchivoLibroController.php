<?php

namespace App\Http\Controllers;

use App\Models\ArchivoArea;
use App\Models\ArchivoBloque;
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
        $libros = $this->filtrar($request)
            ->with(['bloqueActual.anaquel.area', 'bloqueDestino.anaquel.area', 'areaActual'])
            ->orderByDesc('anio_inicio')->orderBy('codigo')
            ->paginate(30)->withQueryString();
        return view('admin.settings.archivo_siniestros.libros.index', compact('libros'));
    }

    public function buscar(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'anio' => ['nullable', 'integer', 'between:2010,2100'],
            'estado_fisico' => ['nullable', 'string', 'max:30'],
        ]);
        $libros = $this->filtrar($request)
            ->with(['bloqueActual.anaquel.area', 'bloqueDestino.anaquel.area', 'areaActual'])
            ->orderByDesc('anio_inicio')->orderBy('codigo')
            ->paginate(30)->withQueryString();
        return view('admin.settings.archivo_siniestros.libros.index', compact('libros'));
    }

    public function show(ArchivoLibro $libro)
    {
        $libro->load([
            'bloqueActual.anaquel.area', 'bloqueDestino.anaquel.area', 'areaActual',
            'documentos' => fn ($q) => $q->orderBy('pagina_inicio'),
            'folios' => fn ($q) => $q->orderBy('numero_pagina')->limit(100),
            'movimientos' => fn ($q) => $q->latest('fecha_movimiento')->limit(30),
        ]);
        $bloques = ArchivoBloque::with('anaquel.area')
            ->where('activo', true)
            ->orderBy('anaquel_id')
            ->orderBy('codigo')
            ->get();
        $areas = ArchivoArea::where('activo', true)->orderBy('nombre')->get();

        return view('admin.settings.archivo_siniestros.libros.show', compact('libro', 'bloques', 'areas'));
    }

    public function porQr(string $uuid)
    {
        $libro = ArchivoLibro::where('qr_uuid', $uuid)->firstOrFail();
        return redirect()->route('archivo_siniestros.libros.show', $libro);
    }

    public function create()
    {
        $bloques = ArchivoBloque::with('anaquel.area')->where('activo', true)->orderBy('anaquel_id')->orderBy('codigo')->get();
        $areas = ArchivoArea::where('activo', true)->orderBy('nombre')->get();
        return view('admin.settings.archivo_siniestros.libros.create', compact('bloques', 'areas'));
    }

    public function store(Request $request, ArchivoUbicacionService $ubicaciones)
    {
        $datos = $request->validate(array_merge($this->reglas(), [
            'bloque_destino_id' => ['nullable', Rule::exists('archivo_bloques', 'id')->where('activo', true)],
            'posicion_destino' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ]));
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

        return redirect()->route('archivo_siniestros.libros.show', $libro)
            ->with('success', 'Libro registrado. Falta confirmar su ubicación física mediante QR.');
    }

    public function edit(ArchivoLibro $libro)
    {
        $bloques = ArchivoBloque::with('anaquel.area')->where('activo', true)->orderBy('anaquel_id')->orderBy('codigo')->get();
        return view('admin.settings.archivo_siniestros.libros.edit', compact('libro', 'bloques'));
    }

    public function update(Request $request, ArchivoLibro $libro)
    {
        $datos = $request->validate($this->reglas($libro));
        if (array_key_exists('total_paginas', $datos) && $datos['total_paginas'] !== null
            && (int) $datos['total_paginas'] < (int) $libro->paginas_digitalizadas) {
            return back()->withErrors(['total_paginas' => 'El total no puede ser menor a las páginas digitalizadas.'])->withInput();
        }
        $libro->update($datos);
        return redirect()->route('archivo_siniestros.libros.show', $libro)->with('success', 'Libro actualizado correctamente.');
    }

    public function asignarDestino(Request $request, ArchivoLibro $libro, ArchivoUbicacionService $ubicaciones)
    {
        $datos = $request->validate([
            'bloque_destino_id' => ['required', 'integer', Rule::exists('archivo_bloques', 'id')],
            'posicion_destino' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ]);
        $ubicaciones->asignarDestino($libro, (int) $datos['bloque_destino_id'], $datos['posicion_destino'] ?? null, (int) $request->user()->id);
        return redirect()->route('archivo_siniestros.libros.show', $libro)->with('success', 'Ubicación de destino asignada.');
    }

    private function filtrar(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
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
            ->when($request->filled('anio'), function ($query) use ($request) {
                $anio = (int) $request->input('anio');
                $query->where('anio_inicio', '<=', $anio)
                    ->where(function ($q) use ($anio) {
                        $q->where('anio_fin', '>=', $anio)->orWhereNull('anio_fin');
                    });
            })
            ->when($request->filled('estado_fisico'), fn ($query) => $query->where('estado_fisico', $request->query('estado_fisico')));
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
