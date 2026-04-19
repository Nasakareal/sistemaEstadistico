<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\VialidadDispositivo;
use App\Models\VialidadDispositivoDetalle;
use App\Models\VialidadDispositivoFoto;

class VialidadesUrbanasDispositivoController extends Controller
{
    public function index($vialidadUrbana, $dispositivoId)
    {
        $dispositivo = VialidadDispositivo::query()
            ->with([
                'catalogo',
                'delegacion',
                'usuario',
                'creador',
                'actualizador',
                'detalles',
                'fotos',
                'fotoPortada',
            ])
            ->where('unidad_id', 5)
            ->findOrFail($dispositivoId);

        $this->authorizeDispositivo($dispositivo);

        return view('vialidades_urbanas.dispositivos.index', compact(
            'dispositivo',
            'vialidadUrbana'
        ));
    }

    public function create($vialidadUrbana, $dispositivoId)
    {
        $dispositivo = VialidadDispositivo::query()
            ->with([
                'catalogo',
                'detalles',
                'fotos',
                'fotoPortada',
            ])
            ->where('unidad_id', 5)
            ->findOrFail($dispositivoId);

        $this->authorizeDispositivo($dispositivo);

        return view('vialidades_urbanas.dispositivos.create', compact(
            'dispositivo',
            'vialidadUrbana'
        ));
    }

    public function store($vialidadUrbana, $dispositivoId, Request $request)
    {
        $dispositivo = VialidadDispositivo::query()
            ->where('unidad_id', 5)
            ->findOrFail($dispositivoId);

        $this->authorizeDispositivo($dispositivo);

        $validated = $request->validate([
            'detalles' => 'required|array|min:1',
            'detalles.*.tipo' => 'nullable|string|max:50',
            'detalles.*.titulo' => 'nullable|string|max:255',
            'detalles.*.contenido' => 'required|string',
            'detalles.*.ubicacion' => 'nullable|string|max:255',
            'detalles.*.hora' => 'nullable|date_format:H:i',
            'fotos' => 'nullable|array',
            'fotos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $this->storeDetalles($dispositivo, $validated['detalles'] ?? []);
        $this->storeFotos($dispositivo, $request->file('fotos', []));

        return redirect()
            ->route('vialidades_urbanas.dispositivos.edit', [$vialidadUrbana, $dispositivo->id])
            ->with('success', 'Información del dispositivo guardada correctamente.');
    }

    public function show($vialidadUrbana, $dispositivoId)
    {
        $dispositivo = VialidadDispositivo::query()
            ->with([
                'catalogo',
                'delegacion',
                'usuario',
                'creador',
                'actualizador',
                'revisor',
                'detalles',
                'fotos',
                'fotoPortada',
            ])
            ->where('unidad_id', 5)
            ->findOrFail($dispositivoId);

        $this->authorizeDispositivo($dispositivo);

        return view('vialidades_urbanas.dispositivos.show', compact(
            'dispositivo',
            'vialidadUrbana'
        ));
    }

    public function edit($vialidadUrbana, $dispositivoId)
    {
        $dispositivo = VialidadDispositivo::query()
            ->with([
                'catalogo',
                'detalles',
                'fotos',
                'fotoPortada',
            ])
            ->where('unidad_id', 5)
            ->findOrFail($dispositivoId);

        $this->authorizeDispositivo($dispositivo);

        return view('vialidades_urbanas.dispositivos.edit', compact(
            'dispositivo',
            'vialidadUrbana'
        ));
    }

    public function update($vialidadUrbana, Request $request, $dispositivoId)
    {
        $dispositivo = VialidadDispositivo::query()
            ->with(['detalles', 'fotos'])
            ->where('unidad_id', 5)
            ->findOrFail($dispositivoId);

        $this->authorizeDispositivo($dispositivo);

        $validated = $request->validate([
            'detalles' => 'required|array|min:1',
            'detalles.*.tipo' => 'nullable|string|max:50',
            'detalles.*.titulo' => 'nullable|string|max:255',
            'detalles.*.contenido' => 'required|string',
            'detalles.*.ubicacion' => 'nullable|string|max:255',
            'detalles.*.hora' => 'nullable|date_format:H:i',
            'fotos' => 'nullable|array',
            'fotos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'eliminar_fotos' => 'nullable|array',
            'eliminar_fotos.*' => 'integer|exists:vialidad_dispositivo_fotos,id',
            'foto_portada_id' => 'nullable|integer',
        ]);

        $this->syncDetalles($dispositivo, $validated['detalles'] ?? []);
        $this->deleteFotos($dispositivo, $request->input('eliminar_fotos', []));
        $this->storeFotos($dispositivo, $request->file('fotos', []));
        $this->syncFotoPortada($dispositivo, $request->input('foto_portada_id'));

        return redirect()
            ->route('vialidades_urbanas.dispositivos.edit', [$vialidadUrbana, $dispositivo->id])
            ->with('success', 'Información del dispositivo actualizada correctamente.');
    }

    public function destroy($vialidadUrbana, $dispositivoId, $detalleId)
    {
        $dispositivo = VialidadDispositivo::query()
            ->where('unidad_id', 5)
            ->findOrFail($dispositivoId);

        $this->authorizeDispositivo($dispositivo);

        $detalle = VialidadDispositivoDetalle::query()
            ->where('vialidad_dispositivo_id', $dispositivo->id)
            ->findOrFail($detalleId);

        $detalle->delete();

        return redirect()
            ->route('vialidades_urbanas.dispositivos.edit', [$vialidadUrbana, $dispositivo->id])
            ->with('success', 'Detalle eliminado correctamente.');
    }

    private function storeDetalles(VialidadDispositivo $dispositivo, array $detalles): void
    {
        $ordenBase = ((int) $dispositivo->detalles()->max('orden')) + 1;

        foreach ($detalles as $index => $detalle) {
            $contenido = trim((string) ($detalle['contenido'] ?? ''));

            if ($contenido === '') {
                continue;
            }

            VialidadDispositivoDetalle::create([
                'vialidad_dispositivo_id' => $dispositivo->id,
                'orden' => $ordenBase + $index,
                'tipo' => !empty($detalle['tipo']) ? trim((string) $detalle['tipo']) : 'texto',
                'titulo' => $this->normalizeText($detalle['titulo'] ?? null),
                'contenido' => $this->normalizeText($contenido),
                'ubicacion' => $this->normalizeText($detalle['ubicacion'] ?? null),
                'hora' => !empty($detalle['hora']) ? $detalle['hora'] : null,
            ]);
        }
    }

    private function syncDetalles(VialidadDispositivo $dispositivo, array $detalles): void
    {
        $dispositivo->detalles()->delete();

        $orden = 1;

        foreach ($detalles as $detalle) {
            $contenido = trim((string) ($detalle['contenido'] ?? ''));

            if ($contenido === '') {
                continue;
            }

            VialidadDispositivoDetalle::create([
                'vialidad_dispositivo_id' => $dispositivo->id,
                'orden' => $orden,
                'tipo' => !empty($detalle['tipo']) ? trim((string) $detalle['tipo']) : 'texto',
                'titulo' => $this->normalizeText($detalle['titulo'] ?? null),
                'contenido' => $this->normalizeText($contenido),
                'ubicacion' => $this->normalizeText($detalle['ubicacion'] ?? null),
                'hora' => !empty($detalle['hora']) ? $detalle['hora'] : null,
            ]);

            $orden++;
        }
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
        if ($this->canUseVialidadesUrbanas($usuario)) {
            return;
        }

        $query->whereRaw('1=0');
    }

    private function canUseVialidadesUrbanas($usuario): bool
    {
        if (!$usuario) {
            return false;
        }

        return $usuario->hasRole('Superadmin')
            || in_array((int) ($usuario->unidad_id ?? 0), [3, 5], true);
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

    public function whatsapp($vialidadUrbana, $dispositivoId)
    {
        $dispositivo = VialidadDispositivo::query()
            ->with([
                'catalogo',
                'creador',
                'detalles',
            ])
            ->where('unidad_id', 5)
            ->findOrFail($dispositivoId);

        $this->authorizeDispositivo($dispositivo);

        $nombreInformante = trim((string) optional($dispositivo->creador)->name);
        $catalogo = trim((string) optional($dispositivo->catalogo)->nombre);

        $lineas = [];
        $lineas[] = 'GUARDIA CIVIL';
        $lineas[] = '';
        $lineas[] = 'COORDINACION DEL AGRUPAMIENTO DE SEGURIDAD VIAL';
        $lineas[] = '';
        $lineas[] = 'UNIDAD DE PROTECCION EN VIALIDADES URBANAS';
        $lineas[] = '';
        $lineas[] = 'TARJETA INFORMATIVA';
        $lineas[] = '';

        $referencia = 'EN CUMPLIMIENTO AL DISPOSITIVO ACTIVO';
        if (!empty($dispositivo->id)) {
            $referencia .= ' ID ' . $dispositivo->id;
        }
        if ($catalogo !== '') {
            $referencia .= ', ' . strtoupper($this->removeAccents($catalogo));
        }
        if (!empty($dispositivo->asunto)) {
            $referencia .= ', ASUNTO: ' . trim((string) $dispositivo->asunto);
        }

        $lineas[] = $referencia . '.';

        if (!empty($dispositivo->fecha)) {
            $lineas[] = 'FECHA DEL DISPOSITIVO: ' . \Carbon\Carbon::parse($dispositivo->fecha)->format('d/m/Y');
        }

        $lineas[] = '';

        if ($dispositivo->detalles->count() > 0) {
            foreach ($dispositivo->detalles as $index => $detalle) {
                $prefijo = ($index + 1) . '.-';

                $partes = [];

                if (!empty($detalle->hora)) {
                    $partes[] = 'A LAS ' . substr((string) $detalle->hora, 0, 5) . ' HORAS';
                }

                if (!empty($detalle->ubicacion)) {
                    $partes[] = 'EN ' . trim((string) $detalle->ubicacion);
                }

                if (!empty($detalle->titulo)) {
                    $partes[] = trim((string) $detalle->titulo);
                }

                $encabezado = trim(implode(', ', $partes));

                if ($encabezado !== '') {
                    $lineas[] = $prefijo . ' ' . $encabezado . '.';
                } else {
                    $lineas[] = $prefijo;
                }

                if (!empty($detalle->contenido)) {
                    $lineas[] = trim((string) $detalle->contenido);
                }

                $lineas[] = '';
            }
        } else {
            $lineas[] = 'SIN NOVEDAD REPORTADA.';
            $lineas[] = '';
        }

        $lineas[] = 'INFORMA EL AGENTE';
        $lineas[] = $nombreInformante !== '' ? strtoupper($this->removeAccents($nombreInformante)) : 'SIN USUARIO REGISTRADO';

        return response()->json([
            'texto' => trim(implode("\n", $lineas)),
        ]);
    }
}
