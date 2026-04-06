<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\VialidadDispositivo;
use App\Models\VialidadDispositivoCatalogo;
use App\Models\VialidadDispositivoFoto;
use App\Models\Personal;

class VialidadesUrbanasController extends Controller
{
    public function index(Request $request)
    {
        $tz = 'America/Mexico_City';

        $fechaSeleccionada = (string) $request->query('fecha', now($tz)->toDateString());

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSeleccionada)) {
            $fechaSeleccionada = now($tz)->toDateString();
        }

        $usuario = auth()->user();

        $query = VialidadDispositivo::query()
            ->with([
                'catalogo',
                'delegacion',
                'usuario',
                'creador',
                'actualizador',
                'detalles',
                'fotoPortada',
            ])
            ->where('unidad_id', 5)
            ->whereDate('fecha', $fechaSeleccionada)
            ->orderByDesc('hora')
            ->orderByDesc('created_at');

        $this->applyVisibilityScope($query, $usuario);

        $dispositivos = $query->paginate(30)->withQueryString();

        $catalogos = VialidadDispositivoCatalogo::query()
            ->where('unidad_id', 5)
            ->where('activo', 1)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $vialidadUrbana = 1;

        return view('vialidades_urbanas.index', compact(
            'dispositivos',
            'catalogos',
            'fechaSeleccionada',
            'vialidadUrbana'
        ));
    }

    public function create()
    {
        $catalogos = VialidadDispositivoCatalogo::query()
            ->where('unidad_id', 5)
            ->where('activo', 1)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $vialidadUrbana = 1;

        return view('vialidades_urbanas.create', compact(
            'catalogos',
            'vialidadUrbana'
        ));
    }

    public function store(Request $request)
    {
        $usuario = auth()->user();

        $validated = $request->validate([
            'vialidad_dispositivo_catalogo_id' => [
                'required',
                Rule::exists('vialidad_dispositivo_catalogos', 'id')->where(function ($q) {
                    $q->where('unidad_id', 5)->where('activo', 1);
                }),
            ],
            'asunto' => 'required|string|max:255',
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'municipio' => 'nullable|string|max:255',
            'lugar' => 'nullable|string|max:255',
            'evento' => 'nullable|string|max:255',
            'objetivo' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'narrativa' => 'nullable|string',
            'acciones_realizadas' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'elementos' => 'nullable|integer|min:0',
            'crp' => 'nullable|integer|min:0',
            'motopatrullas' => 'nullable|integer|min:0',
            'fenix' => 'nullable|integer|min:0',
            'unidades_motorizadas' => 'nullable|integer|min:0',
            'patrullas' => 'nullable|integer|min:0',
            'gruas' => 'nullable|integer|min:0',
            'otros_apoyos' => 'nullable|integer|min:0',
            'supervision' => 'nullable|string|max:255',
            'fotos' => 'nullable|array',
            'fotos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $dispositivo = VialidadDispositivo::create([
            'client_uuid' => $request->input('client_uuid'),
            'sync_status' => $request->input('sync_status'),
            'sync_error' => $request->input('sync_error'),
            'synced_at' => $request->input('synced_at'),
            'vialidad_dispositivo_catalogo_id' => $validated['vialidad_dispositivo_catalogo_id'],
            'unidad_id' => 5,
            'delegacion_id' => $usuario->delegacion_id ?? null,
            'user_id' => $usuario->id ?? null,
            'created_by' => $usuario->id ?? null,
            'updated_by' => null,
            'asunto' => $this->normalizeText($validated['asunto'] ?? null),
            'fecha' => $validated['fecha'],
            'hora' => $validated['hora'],
            'municipio' => $this->normalizeText($validated['municipio'] ?? null),
            'lugar' => $this->normalizeText($validated['lugar'] ?? null),
            'evento' => $this->normalizeText($validated['evento'] ?? null),
            'objetivo' => $this->normalizeText($validated['objetivo'] ?? null),
            'descripcion' => $this->normalizeText($validated['descripcion'] ?? null),
            'narrativa' => $this->normalizeText($validated['narrativa'] ?? null),
            'acciones_realizadas' => $this->normalizeText($validated['acciones_realizadas'] ?? null),
            'observaciones' => $this->normalizeText($validated['observaciones'] ?? null),
            'elementos' => (int) ($validated['elementos'] ?? 0),
            'crp' => (int) ($validated['crp'] ?? 0),
            'motopatrullas' => (int) ($validated['motopatrullas'] ?? 0),
            'fenix' => (int) ($validated['fenix'] ?? 0),
            'unidades_motorizadas' => (int) ($validated['unidades_motorizadas'] ?? 0),
            'patrullas' => (int) ($validated['patrullas'] ?? 0),
            'gruas' => (int) ($validated['gruas'] ?? 0),
            'otros_apoyos' => (int) ($validated['otros_apoyos'] ?? 0),
            'supervision' => $this->normalizeText($validated['supervision'] ?? null),
            'responsable_nombre' => null,
            'responsable_cargo' => null,
            'revisado' => false,
            'revisado_por' => null,
            'revisado_en' => null,
        ]);

        $this->storeFotos($dispositivo, $request->file('fotos', []));

        return redirect()
            ->route('vialidades_urbanas.edit', $dispositivo->id)
            ->with('success', 'Dispositivo creado correctamente.');
    }

    public function edit($vialidadUrbana, Request $request)
    {
        $dispositivo = VialidadDispositivo::query()
            ->with(['catalogo', 'detalles', 'fotos', 'fotoPortada'])
            ->where('unidad_id', 5)
            ->findOrFail($vialidadUrbana);

        $this->authorizeDispositivo($dispositivo);

        $catalogos = VialidadDispositivoCatalogo::query()
            ->where('unidad_id', 5)
            ->where('activo', 1)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return view('vialidades_urbanas.edit', compact(
            'dispositivo',
            'catalogos',
            'vialidadUrbana'
        ));
    }

    public function update($vialidadUrbana, Request $request)
    {
        $dispositivo = VialidadDispositivo::query()
            ->where('unidad_id', 5)
            ->findOrFail($vialidadUrbana);

        $this->authorizeDispositivo($dispositivo);

        $usuario = auth()->user();

        $validated = $request->validate([
            'vialidad_dispositivo_catalogo_id' => [
                'required',
                Rule::exists('vialidad_dispositivo_catalogos', 'id')->where(function ($q) {
                    $q->where('unidad_id', 5)->where('activo', 1);
                }),
            ],
            'asunto' => 'required|string|max:255',
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'municipio' => 'nullable|string|max:255',
            'lugar' => 'nullable|string|max:255',
            'evento' => 'nullable|string|max:255',
            'objetivo' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'narrativa' => 'nullable|string',
            'acciones_realizadas' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'elementos' => 'nullable|integer|min:0',
            'crp' => 'nullable|integer|min:0',
            'motopatrullas' => 'nullable|integer|min:0',
            'fenix' => 'nullable|integer|min:0',
            'unidades_motorizadas' => 'nullable|integer|min:0',
            'patrullas' => 'nullable|integer|min:0',
            'gruas' => 'nullable|integer|min:0',
            'otros_apoyos' => 'nullable|integer|min:0',
            'supervision' => 'nullable|string|max:255',
            'fotos' => 'nullable|array',
            'fotos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'eliminar_fotos' => 'nullable|array',
            'eliminar_fotos.*' => 'integer|exists:vialidad_dispositivo_fotos,id',
            'foto_portada_id' => 'nullable|integer',
        ]);

        $dispositivo->update([
            'vialidad_dispositivo_catalogo_id' => $validated['vialidad_dispositivo_catalogo_id'],
            'updated_by' => $usuario->id ?? null,
            'asunto' => $this->normalizeText($validated['asunto'] ?? null),
            'fecha' => $validated['fecha'],
            'hora' => $validated['hora'],
            'municipio' => $this->normalizeText($validated['municipio'] ?? null),
            'lugar' => $this->normalizeText($validated['lugar'] ?? null),
            'evento' => $this->normalizeText($validated['evento'] ?? null),
            'objetivo' => $this->normalizeText($validated['objetivo'] ?? null),
            'descripcion' => $this->normalizeText($validated['descripcion'] ?? null),
            'narrativa' => $this->normalizeText($validated['narrativa'] ?? null),
            'acciones_realizadas' => $this->normalizeText($validated['acciones_realizadas'] ?? null),
            'observaciones' => $this->normalizeText($validated['observaciones'] ?? null),
            'elementos' => (int) ($validated['elementos'] ?? 0),
            'crp' => (int) ($validated['crp'] ?? 0),
            'motopatrullas' => (int) ($validated['motopatrullas'] ?? 0),
            'fenix' => (int) ($validated['fenix'] ?? 0),
            'unidades_motorizadas' => (int) ($validated['unidades_motorizadas'] ?? 0),
            'patrullas' => (int) ($validated['patrullas'] ?? 0),
            'gruas' => (int) ($validated['gruas'] ?? 0),
            'otros_apoyos' => (int) ($validated['otros_apoyos'] ?? 0),
            'supervision' => $this->normalizeText($validated['supervision'] ?? null),
            'responsable_nombre' => null,
            'responsable_cargo' => null,
        ]);

        $this->deleteFotos($dispositivo, $request->input('eliminar_fotos', []));
        $this->storeFotos($dispositivo, $request->file('fotos', []));
        $this->syncFotoPortada($dispositivo, $request->input('foto_portada_id'));

        return redirect()
            ->route('vialidades_urbanas.edit', $dispositivo->id)
            ->with('success', 'Dispositivo actualizado correctamente.');
    }

    public function resumen($vialidadUrbana, Request $request)
    {
        $tz = 'America/Mexico_City';

        $fechaSeleccionada = (string) $request->query('fecha', now($tz)->toDateString());

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSeleccionada)) {
            $fechaSeleccionada = now($tz)->toDateString();
        }

        $usuario = auth()->user();

        $query = VialidadDispositivo::query()
            ->with(['catalogo', 'detalles'])
            ->where('unidad_id', 5)
            ->whereDate('fecha', $fechaSeleccionada)
            ->orderBy('hora')
            ->orderBy('id');

        $this->applyVisibilityScope($query, $usuario);

        $dispositivos = $query->get();

        $totales = [
            'dispositivos' => $dispositivos->count(),
            'elementos' => (int) $dispositivos->sum('elementos'),
            'crp' => (int) $dispositivos->sum('crp'),
            'motopatrullas' => (int) $dispositivos->sum('motopatrullas'),
            'fenix' => (int) $dispositivos->sum('fenix'),
            'unidades_motorizadas' => (int) $dispositivos->sum('unidades_motorizadas'),
            'patrullas' => (int) $dispositivos->sum('patrullas'),
            'gruas' => (int) $dispositivos->sum('gruas'),
            'otros_apoyos' => (int) $dispositivos->sum('otros_apoyos'),
        ];

        return view('vialidades_urbanas.resumen', compact(
            'dispositivos',
            'totales',
            'fechaSeleccionada',
            'vialidadUrbana'
        ));
    }

    public function whatsapp($vialidadUrbana, Request $request)
    {
        $tz = 'America/Mexico_City';

        $fechaSeleccionada = (string) $request->query('fecha', now($tz)->toDateString());

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSeleccionada)) {
            $fechaSeleccionada = now($tz)->toDateString();
        }

        $usuario = auth()->user();

        $query = VialidadDispositivo::query()
            ->with(['catalogo', 'detalles'])
            ->where('unidad_id', 5)
            ->whereDate('fecha', $fechaSeleccionada)
            ->orderBy('hora')
            ->orderBy('id');

        $this->applyVisibilityScope($query, $usuario);

        $dispositivos = $query->get();

        $subdirector = Personal::query()
            ->where('unidad_id', 5)
            ->where('puesto', 'SUBDIRECTOR')
            ->where('estatus', 'ACTIVO')
            ->orderBy('id')
            ->first();

        $cargoFirma = 'SUBDIRECTOR DE PROTECCIÓN EN VIALIDADES URBANAS';

        $nombreFirma = $subdirector
            ? trim(collect([
                $subdirector->grado,
                $subdirector->nombre,
                $subdirector->ap_paterno,
                $subdirector->ap_materno,
            ])->filter()->implode(' '))
            : '';

        $lineas = [];
        $lineas[] = 'GUARDIA CIVIL';
        $lineas[] = '';
        $lineas[] = 'COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL';
        $lineas[] = '';
        $lineas[] = 'UNIDAD DE PROTECCIÓN EN VIALIDADES URBANAS';
        $lineas[] = '';
        $lineas[] = strtoupper(\Carbon\Carbon::parse($fechaSeleccionada)->locale('es')->translatedFormat('l d F Y'));
        $lineas[] = '';

        foreach ($dispositivos as $dispositivo) {
            $lineas[] = 'ASUNTO: ' . trim((string) $dispositivo->asunto);
            $lineas[] = substr((string) $dispositivo->hora, 0, 5) . ' HORAS';
            $lineas[] = '';

            if (!empty($dispositivo->descripcion)) {
                $lineas[] = trim((string) $dispositivo->descripcion);
                $lineas[] = '';
            }

            foreach ($dispositivo->detalles as $detalle) {
                $texto = trim((string) $detalle->contenido);
                if ($texto !== '') {
                    $lineas[] = $texto;
                }
            }

            if ($dispositivo->detalles->count() > 0) {
                $lineas[] = '';
            }

            $lineas[] = 'ESTADO DE FUERZA';
            $lineas[] = (int) $dispositivo->elementos . ' ELEMENTOS';

            if ((int) $dispositivo->crp > 0) {
                $lineas[] = (int) $dispositivo->crp . ' CRP';
            }

            if ((int) $dispositivo->motopatrullas > 0) {
                $lineas[] = (int) $dispositivo->motopatrullas . ' MOTOPATRULLAS';
            }

            if ((int) $dispositivo->fenix > 0) {
                $lineas[] = (int) $dispositivo->fenix . ' FÉNIX';
            }

            if ((int) $dispositivo->unidades_motorizadas > 0) {
                $lineas[] = (int) $dispositivo->unidades_motorizadas . ' UNIDADES MOTORIZADAS';
            }

            if ((int) $dispositivo->patrullas > 0) {
                $lineas[] = (int) $dispositivo->patrullas . ' PATRULLAS';
            }

            if ((int) $dispositivo->gruas > 0) {
                $lineas[] = (int) $dispositivo->gruas . ' GRÚAS';
            }

            if ((int) $dispositivo->otros_apoyos > 0) {
                $lineas[] = (int) $dispositivo->otros_apoyos . ' OTROS APOYOS';
            }

            $lineas[] = '';
            $lineas[] = '----------------------------------------';
            $lineas[] = '';
        }

        $lineas[] = 'RESPETUOSAMENTE';
        $lineas[] = $cargoFirma;

        if ($nombreFirma !== '') {
            $lineas[] = strtoupper($this->removeAccents($nombreFirma));
        }

        return response()->json([
            'texto' => trim(implode("\n", $lineas)),
        ]);
    }

    private function storeFotos(VialidadDispositivo $dispositivo, array $fotos): void
    {
        if (empty($fotos)) {
            return;
        }

        $ordenBase = ((int) $dispositivo->fotos()->max('orden')) + 1;

        foreach ($fotos as $index => $archivo) {
            if (!$archivo) {
                continue;
            }

            $ruta = $archivo->store('vialidades_urbanas/' . $dispositivo->id, 'public');

            VialidadDispositivoFoto::create([
                'vialidad_dispositivo_id' => $dispositivo->id,
                'ruta' => $ruta,
                'nombre_original' => $archivo->getClientOriginalName(),
                'orden' => $ordenBase + $index,
                'portada' => false,
                'included_in_share' => true,
                'lat' => null,
                'lng' => null,
            ]);
        }

        if (!$dispositivo->fotos()->where('portada', true)->exists()) {
            $primera = $dispositivo->fotos()->orderBy('orden')->first();
            if ($primera) {
                $primera->update(['portada' => true]);
            }
        }
    }

    private function deleteFotos(VialidadDispositivo $dispositivo, array $fotoIds): void
    {
        if (empty($fotoIds)) {
            return;
        }

        $fotos = $dispositivo->fotos()->whereIn('id', $fotoIds)->get();

        foreach ($fotos as $foto) {
            if (!empty($foto->ruta) && Storage::disk('public')->exists($foto->ruta)) {
                Storage::disk('public')->delete($foto->ruta);
            }

            $foto->delete();
        }

        if (!$dispositivo->fotos()->where('portada', true)->exists()) {
            $primera = $dispositivo->fotos()->orderBy('orden')->first();
            if ($primera) {
                $primera->update(['portada' => true]);
            }
        }
    }

    private function syncFotoPortada(VialidadDispositivo $dispositivo, $fotoPortadaId): void
    {
        if (empty($fotoPortadaId)) {
            return;
        }

        $foto = $dispositivo->fotos()->where('id', $fotoPortadaId)->first();

        if (!$foto) {
            return;
        }

        $dispositivo->fotos()->update(['portada' => false]);
        $foto->update(['portada' => true]);
    }

    private function authorizeDispositivo(VialidadDispositivo $dispositivo): void
    {
        $usuario = auth()->user();

        if ((int) $dispositivo->unidad_id !== 5) {
            abort(404);
        }

        $query = VialidadDispositivo::query()->whereKey($dispositivo->id);
        $this->applyVisibilityScope($query, $usuario);

        if (!$query->exists()) {
            abort(404);
        }
    }

    private function applyVisibilityScope($query, $usuario): void
    {
        if (
            $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Administrador')
            || (int) $usuario->unidad_id === 3
        ) {
            return;
        }

        $query->where('unidad_id', 5);
    }

    private function normalizeText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return strtoupper($this->removeAccents($value));
    }

    private function removeAccents($string)
    {
        $unwantedArray = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
            'Â' => 'A', 'Ê' => 'E', 'Î' => 'I', 'Ô' => 'O', 'Û' => 'U',
            'Ä' => 'A', 'Ë' => 'E', 'Ï' => 'I', 'Ö' => 'O', 'Ü' => 'U',
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
            'à' => 'A', 'è' => 'E', 'ì' => 'I', 'ò' => 'O', 'ù' => 'U',
            'â' => 'A', 'ê' => 'E', 'î' => 'I', 'ô' => 'O', 'û' => 'U',
            'ä' => 'A', 'ë' => 'E', 'ï' => 'I', 'ö' => 'O', 'ü' => 'U',
            'Ñ' => 'N', 'ñ' => 'N',
            'Ç' => 'C', 'ç' => 'C'
        ];

        return strtr($string, $unwantedArray);
    }
}
