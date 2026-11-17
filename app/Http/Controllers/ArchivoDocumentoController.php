<?php

namespace App\Http\Controllers;

use App\Models\ArchivoDocumento;
use App\Models\ArchivoLibro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ArchivoDocumentoController extends Controller
{
    public function show(ArchivoLibro $libro, ArchivoDocumento $documento)
    {
        abort_unless((int) $documento->libro_id === (int) $libro->id, 404);
        abort_unless($documento->disco === 'local', 404);
        abort_unless(Storage::disk('local')->exists($documento->ruta_archivo), 404);

        return Storage::disk('local')->response(
            $documento->ruta_archivo,
            $documento->nombre_original ?: $documento->nombre,
            [
                'Content-Type' => $documento->mime_type,
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function store(Request $request, ArchivoLibro $libro)
    {
        $datos = $request->validate([
            // PHP acepta peticiones de hasta 8 MB en este servidor. Dejamos margen
            // para los demás campos multipart y evitamos un rechazo previo a Laravel.
            'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:7168'],
            'nombre' => ['required', 'string', 'max:200'],
            'pagina_inicio' => ['required', 'integer', 'min:1', 'max:100000'],
            'pagina_fin' => ['required', 'integer', 'gte:pagina_inicio', 'max:100000'],
        ]);
        $archivo = $request->file('archivo');
        $ruta = $archivo->storeAs(
            'archivo-siniestros/libros/' . $libro->id,
            (string) Str::uuid() . '.' . $archivo->extension(),
            'local'
        );
        if (!$ruta) {
            throw ValidationException::withMessages(['archivo' => 'No fue posible almacenar el documento.']);
        }

        try {
            DB::transaction(function () use ($libro, $datos, $archivo, $ruta, $request) {
                $libro = ArchivoLibro::whereKey($libro->id)->lockForUpdate()->firstOrFail();
                if ($libro->total_paginas !== null && (int) $datos['pagina_fin'] > (int) $libro->total_paginas) {
                    throw ValidationException::withMessages(['pagina_fin' => 'La última página supera el total del libro.']);
                }

                $libro->documentos()->create([
                    'nombre' => $datos['nombre'],
                    'nombre_original' => $archivo->getClientOriginalName(),
                    'disco' => 'local',
                    'ruta_archivo' => $ruta,
                    'mime_type' => $archivo->getMimeType(),
                    'tamano_bytes' => $archivo->getSize(),
                    'sha256' => hash_file('sha256', $archivo->getRealPath()),
                    'pagina_inicio' => $datos['pagina_inicio'],
                    'pagina_fin' => $datos['pagina_fin'],
                    'estado_revision' => 'pendiente',
                    'usuario_carga_id' => $request->user()->id,
                ]);

                $intervalos = $libro->documentos()->orderBy('pagina_inicio')->get(['pagina_inicio', 'pagina_fin']);
                $ultima = 0;
                $total = 0;
                foreach ($intervalos as $intervalo) {
                    $inicio = max((int) $intervalo->pagina_inicio, $ultima + 1);
                    $fin = (int) $intervalo->pagina_fin;
                    if ($fin >= $inicio) {
                        $total += $fin - $inicio + 1;
                    }
                    $ultima = max($ultima, $fin);
                }
                $libro->paginas_digitalizadas = $total;
                $libro->estado_digitalizacion = $libro->total_paginas && $total >= $libro->total_paginas
                    ? 'en_revision' : 'parcial';
                $libro->save();
            }, 3);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($ruta);
            throw $e;
        }

        return redirect()->route('archivo_siniestros.libros.show', $libro)->with('success', 'Documento privado guardado para revisión.');
    }
}
