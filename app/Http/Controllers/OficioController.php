<?php

namespace App\Http\Controllers;

use App\Models\Oficio;
use App\Models\Unidad;
use App\Services\Oficios\OficioArchivoStorage;
use App\Services\OficioTerminoWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OficioController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->queryOficiosVisibles()
            ->with(['unidad', 'creador', 'contestaA' => function ($q) {
                $q->visibleFor($this->actor());
            }])
            ->withCount('contestaciones');

        $this->aplicarFiltros($query, $request);

        $oficios = $query
            ->orderByDesc('fecha_documento')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.settings.oficios.index', [
            'oficios' => $oficios,
            'tipos' => Oficio::TIPOS,
            'sentidos' => Oficio::SENTIDOS,
            'terminosHoras' => Oficio::TERMINOS_HORAS,
            'unidades' => $this->unidadesDisponibles(),
            'puedeFiltrarUnidad' => $this->actorEsSuperadmin(),
            'filtros' => $request->only(['buscar', 'tipo', 'sentido', 'unidad_id']),
        ]);
    }

    public function create(Request $request)
    {
        $contestaA = $request->filled('contesta_a_id')
            ? $this->queryOficiosVisibles()->find($request->query('contesta_a_id'))
            : null;

        $unidadId = $contestaA
            ? (int) $contestaA->unidad_id
            : (int) optional($this->actor())->unidad_id;

        $unidades = $this->unidadesDisponibles();

        return view('admin.settings.oficios.create', [
            'oficio' => new Oficio([
                'tipo' => 'oficio',
                'sentido' => $contestaA ? 'salida' : 'entrada',
                'unidad_id' => $unidadId ?: null,
                'fecha_documento' => now(),
                'termino_horas' => null,
                'asunto' => $contestaA ? 'Contestación a ' . $contestaA->numero_oficio : null,
                'destinatario' => $contestaA ? $contestaA->remitente : null,
                'contesta_a_id' => $contestaA ? $contestaA->id : null,
            ]),
            'tipos' => Oficio::TIPOS,
            'sentidos' => Oficio::SENTIDOS,
            'terminosHoras' => Oficio::TERMINOS_HORAS,
            'unidades' => $unidades,
            'prefijosUnidad' => $this->prefijosUnidad($unidades),
            'oficiosParaContestar' => $this->oficiosParaContestar($unidadId ?: null),
            'puedeElegirUnidad' => $this->actorEsSuperadmin(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        $unidadId = $this->unidadIdParaGuardar($validated);

        $this->validarContestaA($validated['contesta_a_id'] ?? null, $unidadId);

        $data = $this->datosParaGuardar($validated, $unidadId);
        $data['created_by'] = optional($this->actor())->id;
        $data['updated_by'] = optional($this->actor())->id;

        $this->guardarPdf($request, $data);
        $data['fotos'] = $this->sincronizarFotos($request);

        $oficio = DB::transaction(function () use (&$data) {
            $this->asignarNumeroSalida($data);

            return Oficio::create($data);
        });

        $this->notificarTerminoSiAplica($oficio);

        return redirect()
            ->route('oficios.show', $oficio)
            ->with('success', 'Oficio registrado correctamente.');
    }

    public function show(Oficio $oficio)
    {
        $this->asegurarVisible($oficio);

        $oficio->load([
            'unidad',
            'creador',
            'actualizador',
            'contestaA' => function ($q) {
                $q->visibleFor($this->actor())->with('unidad');
            },
            'contestaciones' => function ($q) {
                $q->visibleFor($this->actor())
                    ->with('unidad')
                    ->orderByDesc('fecha_documento')
                    ->orderByDesc('created_at');
            },
        ]);

        return view('admin.settings.oficios.show', [
            'oficio' => $oficio,
            'tipos' => Oficio::TIPOS,
            'sentidos' => Oficio::SENTIDOS,
            'terminosHoras' => Oficio::TERMINOS_HORAS,
        ]);
    }

    public function edit(Oficio $oficio)
    {
        $this->asegurarVisible($oficio);

        $unidades = $this->unidadesDisponibles();

        return view('admin.settings.oficios.edit', [
            'oficio' => $oficio,
            'tipos' => Oficio::TIPOS,
            'sentidos' => Oficio::SENTIDOS,
            'terminosHoras' => Oficio::TERMINOS_HORAS,
            'unidades' => $unidades,
            'prefijosUnidad' => $this->prefijosUnidad($unidades),
            'oficiosParaContestar' => $this->oficiosParaContestar((int) $oficio->unidad_id, (int) $oficio->id),
            'puedeElegirUnidad' => $this->actorEsSuperadmin(),
        ]);
    }

    public function update(Request $request, Oficio $oficio)
    {
        $this->asegurarVisible($oficio);

        $validated = $this->validar($request, $oficio);
        $unidadId = $this->unidadIdParaGuardar($validated);
        $terminoAnterior = (int) ($oficio->termino_horas ?? 0);

        $this->validarContestaA($validated['contesta_a_id'] ?? null, $unidadId, $oficio);

        $data = $this->datosParaGuardar($validated, $unidadId);

        if ((int) ($data['termino_horas'] ?? 0) !== $terminoAnterior) {
            $data['termino_notificado_at'] = null;
        }

        $data['updated_by'] = optional($this->actor())->id;

        $this->guardarPdf($request, $data, $oficio);
        $data['fotos'] = $this->sincronizarFotos($request, $oficio);

        DB::transaction(function () use ($oficio, &$data) {
            $this->asignarNumeroSalida($data, $oficio);
            $oficio->update($data);
        });

        $oficio->refresh();
        $this->notificarTerminoSiAplica($oficio);

        return redirect()
            ->route('oficios.show', $oficio)
            ->with('success', 'Oficio actualizado correctamente.');
    }

    public function destroy(Oficio $oficio)
    {
        $this->asegurarVisible($oficio);

        $this->eliminarArchivos($oficio);
        $oficio->delete();

        return redirect()
            ->route('oficios.index')
            ->with('success', 'Oficio eliminado correctamente.');
    }

    public function archivoPdf(Oficio $oficio)
    {
        $this->asegurarVisible($oficio);

        abort_unless($oficio->pdf_path, 404);

        return $this->archivos()->response($oficio->pdf_path, basename($oficio->pdf_path));
    }

    public function archivoFoto(Oficio $oficio, int $indice)
    {
        $this->asegurarVisible($oficio);

        $fotos = array_values($oficio->fotos ?: []);
        $path = $fotos[$indice] ?? null;

        abort_unless($path, 404);

        return $this->archivos()->response($path, basename($path));
    }

    private function actor()
    {
        return Auth::user();
    }

    private function actorEsSuperadmin(): bool
    {
        $actor = $this->actor();

        return $actor && $actor->hasRole('Superadmin');
    }

    private function queryOficiosVisibles()
    {
        return Oficio::query()->visibleFor($this->actor());
    }

    private function asegurarVisible(Oficio $oficio): void
    {
        if ($this->actorEsSuperadmin()) {
            return;
        }

        $unidadActor = (int) optional($this->actor())->unidad_id;

        abort_unless($unidadActor > 0 && (int) $oficio->unidad_id === $unidadActor, 404);
    }

    private function unidadesDisponibles()
    {
        $actor = $this->actor();

        return Unidad::query()
            ->when(Schema::hasColumn('unidades', 'activa'), function ($q) {
                $q->where('activa', true);
            })
            ->when(!$this->actorEsSuperadmin(), function ($q) use ($actor) {
                $q->where('id', (int) ($actor->unidad_id ?? 0));
            })
            ->orderBy('nombre')
            ->get();
    }

    private function aplicarFiltros($query, Request $request): void
    {
        $buscar = trim((string) $request->input('buscar', ''));

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('numero_oficio', 'like', '%' . $buscar . '%')
                    ->orWhere('asunto', 'like', '%' . $buscar . '%')
                    ->orWhere('remitente', 'like', '%' . $buscar . '%')
                    ->orWhere('destinatario', 'like', '%' . $buscar . '%')
                    ->orWhere('descripcion', 'like', '%' . $buscar . '%');
            });
        }

        if (array_key_exists($request->input('tipo'), Oficio::TIPOS)) {
            $query->where('tipo', $request->input('tipo'));
        }

        if (array_key_exists($request->input('sentido'), Oficio::SENTIDOS)) {
            $query->where('sentido', $request->input('sentido'));
        }

        if ($this->actorEsSuperadmin() && $request->filled('unidad_id')) {
            $query->where('unidad_id', (int) $request->input('unidad_id'));
        }
    }

    private function validar(Request $request, ?Oficio $oficio = null): array
    {
        if ($oficio) {
            $request->merge(['sentido' => $oficio->sentido]);
        }

        $unidadId = $this->actorEsSuperadmin()
            ? (int) $request->input('unidad_id')
            : (int) optional($this->actor())->unidad_id;

        abort_unless($this->actorEsSuperadmin() || $unidadId > 0, 403, 'Tu usuario no tiene unidad asignada.');

        $sentido = (string) $request->input('sentido', 'entrada');
        $numeroRules = ['nullable', 'string', 'max:500'];

        if ($sentido !== 'salida') {
            $numeroUnico = Rule::unique('oficios', 'numero_oficio')
                ->where(fn ($q) => $q->where('unidad_id', $unidadId));

            if ($oficio) {
                $numeroUnico->ignore($oficio->id);
            }

            $numeroRules = ['required', 'string', 'max:500', $numeroUnico];
        }

        return $request->validate([
            'numero_oficio' => $numeroRules,
            'tipo' => ['required', Rule::in(array_keys(Oficio::TIPOS))],
            'sentido' => ['required', Rule::in($oficio ? [$oficio->sentido] : array_keys(Oficio::SENTIDOS))],
            'unidad_id' => $this->actorEsSuperadmin()
                ? ['required', 'integer', 'exists:unidades,id']
                : ['nullable'],
            'fecha_documento' => ['nullable', 'date'],
            'termino_horas' => ['nullable', 'integer', Rule::in(array_keys(Oficio::TERMINOS_HORAS))],
            'remitente' => ['nullable', 'string', 'max:255'],
            'destinatario' => ['nullable', 'string', 'max:255'],
            'asunto' => ['nullable', 'string', 'max:500'],
            'descripcion' => ['nullable', 'string'],
            'contesta_a_id' => ['nullable', 'integer', 'exists:oficios,id'],
            'pdf_path' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'fotos' => ['nullable', 'array', 'max:3'],
            'fotos.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'eliminar_fotos' => ['nullable', 'array'],
            'eliminar_fotos.*' => ['string'],
        ], [
            'numero_oficio.unique' => 'Ya existe un documento con ese número dentro de la unidad seleccionada.',
            'unidad_id.required' => 'Selecciona la unidad propietaria del documento.',
        ]);
    }

    private function unidadIdParaGuardar(array $validated): int
    {
        if ($this->actorEsSuperadmin()) {
            return (int) ($validated['unidad_id'] ?? 0);
        }

        return (int) optional($this->actor())->unidad_id;
    }

    private function datosParaGuardar(array $validated, int $unidadId): array
    {
        return [
            'numero_oficio' => $validated['sentido'] === 'salida'
                ? null
                : trim((string) ($validated['numero_oficio'] ?? '')),
            'tipo' => $validated['tipo'],
            'sentido' => $validated['sentido'],
            'unidad_id' => $unidadId,
            'fecha_documento' => $validated['fecha_documento'] ?? null,
            'termino_horas' => !empty($validated['termino_horas'])
                ? (int) $validated['termino_horas']
                : null,
            'remitente' => $this->limpiarTexto($validated['remitente'] ?? null),
            'destinatario' => $this->limpiarTexto($validated['destinatario'] ?? null),
            'asunto' => $this->limpiarTexto($validated['asunto'] ?? null),
            'descripcion' => $this->limpiarTexto($validated['descripcion'] ?? null),
            'contesta_a_id' => $validated['contesta_a_id'] ?? null,
        ];
    }

    private function validarContestaA(?int $contestaAId, int $unidadId, ?Oficio $oficio = null): void
    {
        if (!$contestaAId) {
            return;
        }

        if ($oficio && (int) $oficio->id === (int) $contestaAId) {
            throw ValidationException::withMessages([
                'contesta_a_id' => 'Un documento no puede contestarse a sí mismo.',
            ]);
        }

        $existe = $this->queryOficiosVisibles()
            ->whereKey($contestaAId)
            ->where('unidad_id', $unidadId)
            ->exists();

        if (!$existe) {
            throw ValidationException::withMessages([
                'contesta_a_id' => 'Solo puedes contestar documentos de la misma unidad visible.',
            ]);
        }
    }

    private function oficiosParaContestar(?int $unidadId = null, ?int $exceptoId = null)
    {
        return $this->queryOficiosVisibles()
            ->when($unidadId, function ($q) use ($unidadId) {
                $q->where('unidad_id', $unidadId);
            })
            ->when($exceptoId, function ($q) use ($exceptoId) {
                $q->where('id', '!=', $exceptoId);
            })
            ->with('unidad')
            ->orderByDesc('fecha_documento')
            ->orderByDesc('created_at')
            ->limit(250)
            ->get();
    }

    private function prefijosUnidad($unidades): array
    {
        return $unidades
            ->mapWithKeys(fn (Unidad $unidad) => [(string) $unidad->id => Oficio::prefijoParaUnidad($unidad)])
            ->all();
    }

    private function asignarNumeroSalida(array &$data, ?Oficio $oficio = null): void
    {
        if (($data['sentido'] ?? null) !== 'salida') {
            return;
        }

        if ($oficio && $this->puedeConservarNumeroSalida($data, $oficio)) {
            $data['numero_oficio'] = $oficio->numero_oficio;
            return;
        }

        $unidad = Unidad::query()->findOrFail((int) $data['unidad_id']);
        $prefijo = Oficio::prefijoParaUnidad($unidad);
        $anio = $this->anioDocumento($data);
        $ignorarId = $oficio ? (int) $oficio->id : null;

        $data['numero_oficio'] = $this->siguienteNumeroSalida($prefijo, (int) $data['unidad_id'], $anio, $ignorarId);
    }

    private function puedeConservarNumeroSalida(array $data, Oficio $oficio): bool
    {
        if ($oficio->sentido !== 'salida' || !$oficio->numero_oficio) {
            return false;
        }

        return (int) $oficio->unidad_id === (int) ($data['unidad_id'] ?? 0)
            && $this->anioOficio($oficio) === $this->anioDocumento($data);
    }

    private function siguienteNumeroSalida(string $prefijo, int $unidadId, int $anio, ?int $ignorarId = null): string
    {
        $numeros = Oficio::query()
            ->where('unidad_id', $unidadId)
            ->where('sentido', 'salida')
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->where(function ($q) use ($anio) {
                $q->whereYear('fecha_documento', $anio)
                    ->orWhere(function ($legacy) use ($anio) {
                        $legacy->whereNull('fecha_documento')
                            ->whereYear('created_at', $anio);
                    });
            })
            ->lockForUpdate()
            ->pluck('numero_oficio');

        $maximo = 0;
        $patron = '/^' . preg_quote($prefijo, '/') . '\/(\d+)\/' . $anio . '$/i';

        foreach ($numeros as $numero) {
            if (preg_match($patron, (string) $numero, $matches)) {
                $maximo = max($maximo, (int) $matches[1]);
            }
        }

        do {
            $maximo++;
            $candidato = sprintf('%s/%03d/%d', $prefijo, $maximo, $anio);
        } while (
            Oficio::query()
                ->where('unidad_id', $unidadId)
                ->where('numero_oficio', $candidato)
                ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
                ->exists()
        );

        return $candidato;
    }

    private function anioDocumento(array $data): int
    {
        return !empty($data['fecha_documento'])
            ? (int) \Illuminate\Support\Carbon::parse($data['fecha_documento'])->format('Y')
            : (int) now()->format('Y');
    }

    private function anioOficio(Oficio $oficio): int
    {
        $fecha = $oficio->fecha_documento ?: $oficio->created_at;

        return $fecha ? (int) $fecha->format('Y') : (int) now()->format('Y');
    }

    private function guardarPdf(Request $request, array &$data, ?Oficio $oficio = null): void
    {
        if (!$request->hasFile('pdf_path')) {
            return;
        }

        if ($oficio && $oficio->pdf_path) {
            $this->archivos()->delete($oficio->pdf_path);
        }

        $data['pdf_path'] = $this->archivos()->putUploadedFile($request->file('pdf_path'), 'oficios/pdf');
    }

    private function sincronizarFotos(Request $request, ?Oficio $oficio = null): ?array
    {
        $fotos = $oficio ? array_values($oficio->fotos ?: []) : [];
        $eliminar = array_values((array) $request->input('eliminar_fotos', []));

        if (!empty($eliminar)) {
            $fotos = array_values(array_filter($fotos, function ($foto) use ($eliminar) {
                if (in_array($foto, $eliminar, true)) {
                    $this->archivos()->delete($foto);
                    return false;
                }

                return true;
            }));
        }

        $nuevas = $request->file('fotos', []);
        $nuevas = is_array($nuevas) ? $nuevas : ($nuevas ? [$nuevas] : []);

        if (count($fotos) + count($nuevas) > 3) {
            throw ValidationException::withMessages([
                'fotos' => 'Solo puedes conservar hasta 3 fotos por documento.',
            ]);
        }

        foreach ($nuevas as $foto) {
            $fotos[] = $this->archivos()->putUploadedFile($foto, 'oficios/fotos');
        }

        return !empty($fotos) ? $fotos : null;
    }

    private function eliminarArchivos(Oficio $oficio): void
    {
        if ($oficio->pdf_path) {
            $this->archivos()->delete($oficio->pdf_path);
        }

        foreach (($oficio->fotos ?: []) as $foto) {
            $this->archivos()->delete($foto);
        }
    }

    private function limpiarTexto(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function notificarTerminoSiAplica(Oficio $oficio): void
    {
        try {
            app(OficioTerminoWhatsAppService::class)->notificar($oficio);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function archivos(): OficioArchivoStorage
    {
        return app(OficioArchivoStorage::class);
    }
}
