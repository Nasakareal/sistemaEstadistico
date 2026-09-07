<?php

namespace App\Http\Controllers;

use App\Models\CaleaDirectiva;
use App\Models\CaleaDirectivaVersion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CaleaDirectivaVersionController extends Controller
{
    public function create($directiva)
    {
        $directiva = CaleaDirectiva::findOrFail($directiva);

        $siguienteNumero = ((int) $directiva->versiones()->max('numero_version')) + 1;

        if ($siguienteNumero < 1) {
            $siguienteNumero = 1;
        }

        return view('admin.settings.calea.versiones.create', compact(
            'directiva',
            'siguienteNumero'
        ));
    }

    public function store(Request $request, $directiva)
    {
        $directiva = CaleaDirectiva::findOrFail($directiva);

        $validated = $request->validate([
            'numero_version' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
                Rule::unique('calea_directiva_versiones', 'numero_version')
                    ->where(function ($query) use ($directiva) {
                        return $query->where('calea_directiva_id', $directiva->id);
                    }),
            ],
            'nombre_version' => 'nullable|string|max:100',
            'fecha_emision' => 'nullable|date',
            'mes_emision' => 'nullable|integer|between:1,12',
            'anio_emision' => 'nullable|integer|min:1900|max:2100|required_with:mes_emision',
            'fecha_revision' => 'nullable|date',
            'area_responsable' => 'nullable|string',
            'autoriza' => 'nullable|string',
            'realizado_por' => 'nullable|string',
            'leyenda_documento' => 'nullable|string',
            'archivo' => 'required|file|mimes:pdf|max:51200',
            'numero_paginas' => 'nullable|integer|min:1|max:10000',
            'texto_busqueda' => 'nullable|string',
            'vigente' => 'nullable|boolean',
            'publicada' => 'nullable|boolean',
        ]);

        $fechaEmision = $validated['fecha_emision'] ?? null;
        $mesEmision = $validated['mes_emision'] ?? null;
        $anioEmision = $validated['anio_emision'] ?? null;

        if ($fechaEmision) {
            $fecha = Carbon::parse($fechaEmision);
            $mesEmision = $fecha->month;
            $anioEmision = $fecha->year;
        }

        $esPrimeraVersion = !$directiva->versiones()->exists();
        $vigente = $esPrimeraVersion || $request->boolean('vigente');
        $publicada = $request->has('publicada') ? $request->boolean('publicada') : true;

        $archivo = $request->file('archivo');
        $disk = $this->diskCalea();
        $numeroVersion = (int) $validated['numero_version'];
        $hash = hash_file('sha256', $archivo->getRealPath());

        $path = null;

        try {
            $path = $archivo->store(
                'calea/directivas/' . $directiva->id . '/versiones/' . $numeroVersion,
                $disk
            );

            $version = DB::transaction(function () use (
                $directiva,
                $validated,
                $fechaEmision,
                $mesEmision,
                $anioEmision,
                $archivo,
                $disk,
                $path,
                $hash,
                $vigente,
                $publicada
            ) {
                if ($vigente) {
                    CaleaDirectivaVersion::where('calea_directiva_id', $directiva->id)
                        ->update(['vigente' => false]);
                }

                return CaleaDirectivaVersion::create([
                    'calea_directiva_id' => $directiva->id,
                    'numero_version' => $validated['numero_version'],
                    'nombre_version' => $this->normalizarTexto($validated['nombre_version'] ?? null),
                    'fecha_emision' => $fechaEmision,
                    'mes_emision' => $mesEmision,
                    'anio_emision' => $anioEmision,
                    'fecha_revision' => $validated['fecha_revision'] ?? null,
                    'area_responsable' => $this->normalizarTexto($validated['area_responsable'] ?? null),
                    'autoriza' => $this->normalizarTexto($validated['autoriza'] ?? null),
                    'realizado_por' => $this->normalizarTexto($validated['realizado_por'] ?? null),
                    'leyenda_documento' => $this->normalizarTexto($validated['leyenda_documento'] ?? null),
                    'archivo_disk' => $disk,
                    'archivo_path' => $path,
                    'archivo_nombre_original' => $archivo->getClientOriginalName(),
                    'archivo_mime' => $archivo->getMimeType(),
                    'archivo_size' => $archivo->getSize(),
                    'archivo_sha256' => $hash,
                    'numero_paginas' => $validated['numero_paginas'] ?? null,
                    'texto_busqueda' => $this->normalizarTexto($validated['texto_busqueda'] ?? null),
                    'vigente' => $vigente,
                    'publicada' => $publicada,
                    'created_by' => auth()->id(),
                ]);
            });

            Log::info('Versión CALEA creada', [
                'version_id' => $version->id,
                'directiva_id' => $directiva->id,
                'codigo' => $directiva->codigo,
                'numero_version' => $version->numero_version,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('settings.calea.versiones.edit', $version->id)
                ->with('success', 'Versión CALEA creada correctamente.');
        } catch (\Exception $e) {
            if ($path && Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }

            Log::error('Error al crear versión CALEA: ' . $e->getMessage(), [
                'directiva_id' => $directiva->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->back()
                ->withErrors('No fue posible crear la versión CALEA.')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $version = CaleaDirectivaVersion::query()
            ->with([
                'directiva',
                'secciones' => function ($query) {
                    $query->orderBy('orden');
                }
            ])
            ->withCount('secciones')
            ->findOrFail($id);

        return view('admin.settings.calea.versiones.edit', compact('version'));
    }

    public function update(Request $request, $id)
    {
        $version = CaleaDirectivaVersion::with('directiva')->findOrFail($id);

        $validated = $request->validate([
            'numero_version' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
                Rule::unique('calea_directiva_versiones', 'numero_version')
                    ->where(function ($query) use ($version) {
                        return $query->where(
                            'calea_directiva_id',
                            $version->calea_directiva_id
                        );
                    })
                    ->ignore($version->id),
            ],
            'nombre_version' => 'nullable|string|max:100',
            'fecha_emision' => 'nullable|date',
            'mes_emision' => 'nullable|integer|between:1,12',
            'anio_emision' => 'nullable|integer|min:1900|max:2100|required_with:mes_emision',
            'fecha_revision' => 'nullable|date',
            'area_responsable' => 'nullable|string',
            'autoriza' => 'nullable|string',
            'realizado_por' => 'nullable|string',
            'leyenda_documento' => 'nullable|string',
            'archivo' => 'nullable|file|mimes:pdf|max:51200',
            'numero_paginas' => 'nullable|integer|min:1|max:10000',
            'texto_busqueda' => 'nullable|string',
            'vigente' => 'nullable|boolean',
            'publicada' => 'nullable|boolean',
        ]);

        $fechaEmision = $validated['fecha_emision'] ?? null;
        $mesEmision = $validated['mes_emision'] ?? null;
        $anioEmision = $validated['anio_emision'] ?? null;

        if ($fechaEmision) {
            $fecha = Carbon::parse($fechaEmision);
            $mesEmision = $fecha->month;
            $anioEmision = $fecha->year;
        }

        $vigente = array_key_exists('vigente', $validated)
            ? $request->boolean('vigente')
            : $version->vigente;

        $publicada = array_key_exists('publicada', $validated)
            ? $request->boolean('publicada')
            : $version->publicada;

        $archivoNuevo = $request->file('archivo');
        $nuevoPath = null;
        $nuevoDisk = null;
        $nuevoHash = null;
        $pathAnterior = $version->archivo_path;
        $diskAnterior = $version->archivo_disk;

        try {
            if ($archivoNuevo) {
                $nuevoDisk = $this->diskCalea();

                $nuevoHash = hash_file(
                    'sha256',
                    $archivoNuevo->getRealPath()
                );

                $nuevoPath = $archivoNuevo->store(
                    'calea/directivas/' .
                    $version->calea_directiva_id .
                    '/versiones/' .
                    (int) $validated['numero_version'],
                    $nuevoDisk
                );
            }

            DB::transaction(function () use (
                $version,
                $validated,
                $fechaEmision,
                $mesEmision,
                $anioEmision,
                $vigente,
                $publicada,
                $archivoNuevo,
                $nuevoDisk,
                $nuevoPath,
                $nuevoHash
            ) {
                if ($vigente) {
                    CaleaDirectivaVersion::where(
                        'calea_directiva_id',
                        $version->calea_directiva_id
                    )
                        ->where('id', '!=', $version->id)
                        ->update(['vigente' => false]);
                }

                $datos = [
                    'numero_version' => $validated['numero_version'],
                    'nombre_version' => $this->normalizarTexto($validated['nombre_version'] ?? null),
                    'fecha_emision' => $fechaEmision,
                    'mes_emision' => $mesEmision,
                    'anio_emision' => $anioEmision,
                    'fecha_revision' => $validated['fecha_revision'] ?? null,
                    'area_responsable' => $this->normalizarTexto($validated['area_responsable'] ?? null),
                    'autoriza' => $this->normalizarTexto($validated['autoriza'] ?? null),
                    'realizado_por' => $this->normalizarTexto($validated['realizado_por'] ?? null),
                    'leyenda_documento' => $this->normalizarTexto($validated['leyenda_documento'] ?? null),
                    'numero_paginas' => $validated['numero_paginas'] ?? null,
                    'texto_busqueda' => $this->normalizarTexto($validated['texto_busqueda'] ?? null),
                    'vigente' => $vigente,
                    'publicada' => $publicada,
                ];

                if ($archivoNuevo) {
                    $datos['archivo_disk'] = $nuevoDisk;
                    $datos['archivo_path'] = $nuevoPath;
                    $datos['archivo_nombre_original'] = $archivoNuevo->getClientOriginalName();
                    $datos['archivo_mime'] = $archivoNuevo->getMimeType();
                    $datos['archivo_size'] = $archivoNuevo->getSize();
                    $datos['archivo_sha256'] = $nuevoHash;
                }

                $version->update($datos);
            });

            if (
                $archivoNuevo &&
                $pathAnterior &&
                $diskAnterior &&
                Storage::disk($diskAnterior)->exists($pathAnterior)
            ) {
                Storage::disk($diskAnterior)->delete($pathAnterior);
            }

            Log::info('Versión CALEA actualizada', [
                'version_id' => $version->id,
                'directiva_id' => $version->calea_directiva_id,
                'numero_version' => $version->numero_version,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('settings.calea.versiones.edit', $version->id)
                ->with('success', 'Versión CALEA actualizada correctamente.');
        } catch (\Exception $e) {
            if (
                $nuevoPath &&
                $nuevoDisk &&
                Storage::disk($nuevoDisk)->exists($nuevoPath)
            ) {
                Storage::disk($nuevoDisk)->delete($nuevoPath);
            }

            Log::error('Error al actualizar versión CALEA: ' . $e->getMessage(), [
                'version_id' => $version->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->back()
                ->withErrors('No fue posible actualizar la versión CALEA.')
                ->withInput();
        }
    }

    public function marcarVigente($id)
    {
        $version = CaleaDirectivaVersion::findOrFail($id);

        try {
            DB::transaction(function () use ($version) {
                CaleaDirectivaVersion::where(
                    'calea_directiva_id',
                    $version->calea_directiva_id
                )->update([
                    'vigente' => false,
                ]);

                $version->vigente = true;
                $version->save();
            });

            Log::info('Versión CALEA marcada como vigente', [
                'version_id' => $version->id,
                'directiva_id' => $version->calea_directiva_id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('settings.calea.edit', $version->calea_directiva_id)
                ->with('success', 'La versión fue marcada como vigente.');
        } catch (\Exception $e) {
            Log::error('Error al marcar versión CALEA como vigente: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('No fue posible marcar la versión como vigente.');
        }
    }

    public function pdf($id)
    {
        $version = CaleaDirectivaVersion::with('directiva')->findOrFail($id);

        if (request()->routeIs('calea.versiones.pdf')) {
            abort_unless(
                $version->vigente &&
                $version->publicada &&
                $version->directiva &&
                $version->directiva->activo,
                404
            );
        }

        abort_unless(
            $version->archivo_disk && $version->archivo_path,
            404
        );

        $disk = Storage::disk($version->archivo_disk);

        abort_unless(
            $disk->exists($version->archivo_path),
            404
        );

        $stream = $disk->readStream($version->archivo_path);

        abort_unless(
            is_resource($stream),
            404
        );

        $nombre = $version->archivo_nombre_original
            ?: 'directiva-calea-' . $version->id . '.pdf';

        return response()->stream(function () use ($stream) {
            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="directiva-calea.pdf"; filename*=UTF-8\'\'' . rawurlencode($nombre),
            'Content-Length' => $version->archivo_size ?: null,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy($id)
    {
        $version = CaleaDirectivaVersion::with('directiva')->findOrFail($id);

        $directivaId = $version->calea_directiva_id;
        $eraVigente = $version->vigente;
        $disk = $version->archivo_disk;
        $path = $version->archivo_path;
        $numeroVersion = $version->numero_version;

        try {
            DB::transaction(function () use ($version, $directivaId, $eraVigente) {
                $version->delete();

                if ($eraVigente) {
                    $nuevaVigente = CaleaDirectivaVersion::where(
                        'calea_directiva_id',
                        $directivaId
                    )
                        ->orderByDesc('numero_version')
                        ->first();

                    if ($nuevaVigente) {
                        $nuevaVigente->vigente = true;
                        $nuevaVigente->save();
                    }
                }
            });

            if ($disk && $path && Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }

            Log::info('Versión CALEA eliminada', [
                'directiva_id' => $directivaId,
                'numero_version' => $numeroVersion,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('settings.calea.edit', $directivaId)
                ->with('success', 'Versión CALEA eliminada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar versión CALEA: ' . $e->getMessage(), [
                'version_id' => $version->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->back()
                ->withErrors('No fue posible eliminar la versión CALEA.');
        }
    }

    private function diskCalea(): string
    {
        return config('filesystems.calea_disk', 'local');
    }

    private function normalizarTexto(?string $texto): ?string
    {
        $texto = trim((string) $texto);

        return $texto === '' ? null : $texto;
    }
}
