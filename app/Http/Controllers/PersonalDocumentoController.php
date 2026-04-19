<?php

namespace App\Http\Controllers;

use App\Models\DocumentoTipo;
use App\Models\Personal;
use App\Models\PersonalDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PersonalDocumentoController extends Controller
{
    public function store(Request $request, Personal $personal)
    {
        $validated = $this->validar($request);

        try {
            PersonalDocumento::create($this->datosParaGuardar($request, $personal, $validated));

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Documento registrado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al registrar documento de personal: ' . $e->getMessage());

            return back()
                ->withErrors('Hubo un error al registrar el documento. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function update(Request $request, Personal $personal, PersonalDocumento $documento)
    {
        if ((int) $documento->personal_id !== (int) $personal->id) {
            abort(404);
        }

        $validated = $this->validar($request);

        try {
            $documento->update($this->datosParaGuardar($request, $personal, $validated, $documento));

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Documento actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar documento de personal: ' . $e->getMessage());

            return back()
                ->withErrors('Hubo un error al actualizar el documento. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function destroy(Personal $personal, PersonalDocumento $documento)
    {
        if ((int) $documento->personal_id !== (int) $personal->id) {
            abort(404);
        }

        try {
            $this->eliminarArchivo($documento->archivo_path);
            $this->eliminarArchivo($documento->archivo_oficio_comision);
            $this->eliminarArchivo($documento->archivo_oficio_asignacion);

            $documento->delete();

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Documento eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar documento de personal: ' . $e->getMessage());

            return back()->withErrors('Hubo un error al eliminar el documento.');
        }
    }

    private function validar(Request $request): array
    {
        $validated = $request->validate([
            'documento_tipo_id' => 'nullable|exists:documento_tipos,id',
            'numero' => 'nullable|string|max:80',
            'fecha_emision' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha_emision',
            'activo' => 'nullable|boolean',
            'observaciones' => 'nullable|string|max:5000',
            'oficio_comision_secretario' => 'nullable|string|max:255',
            'fecha_oficio' => 'nullable|date',
            'titular_firma_oficio' => 'nullable|string|max:255',
            'oficio_asignacion' => 'nullable|string|max:255',
            'fecha_asignacion' => 'nullable|date',
            'titular_firma_asignacion' => 'nullable|string|max:255',
            'archivo' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,webp|max:10240',
            'archivo_oficio_comision' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,webp|max:10240',
            'archivo_oficio_asignacion' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,webp|max:10240',
        ]);

        foreach ($validated as $campo => $valor) {
            if (is_string($valor)) {
                $validated[$campo] = trim($valor);
                if ($validated[$campo] === '') {
                    $validated[$campo] = null;
                }
            }
        }

        return $validated;
    }

    private function datosParaGuardar(Request $request, Personal $personal, array $validated, ?PersonalDocumento $documento = null): array
    {
        $archivoGeneral = $this->guardarArchivo($request, 'archivo', $personal, $documento ? $documento->archivo_path : null);
        $archivoComision = $this->guardarArchivo($request, 'archivo_oficio_comision', $personal, $documento ? $documento->archivo_oficio_comision : null);
        $archivoAsignacion = $this->guardarArchivo($request, 'archivo_oficio_asignacion', $personal, $documento ? $documento->archivo_oficio_asignacion : null);

        $archivoParaMetadatos = $archivoGeneral ?? $archivoComision ?? $archivoAsignacion;

        $data = [
            'personal_id' => $personal->id,
            'documento_tipo_id' => $validated['documento_tipo_id'] ?? ($documento ? $documento->documento_tipo_id : null) ?? $this->tipoDocumentoOficiosId(),
            'numero' => $validated['numero']
                ?? $validated['oficio_asignacion']
                ?? $validated['oficio_comision_secretario']
                ?? ($documento ? $documento->numero : null),
            'fecha_emision' => $validated['fecha_emision']
                ?? $validated['fecha_oficio']
                ?? $validated['fecha_asignacion']
                ?? ($documento ? $documento->fecha_emision : null),
            'fecha_vencimiento' => $validated['fecha_vencimiento'] ?? ($documento ? $documento->fecha_vencimiento : null),
            'activo' => (bool) ($validated['activo'] ?? ($documento ? $documento->activo : null) ?? true),
            'observaciones' => $validated['observaciones'] ?? ($documento ? $documento->observaciones : null),
            'oficio_comision_secretario' => $validated['oficio_comision_secretario'] ?? ($documento ? $documento->oficio_comision_secretario : null),
            'fecha_oficio' => $validated['fecha_oficio'] ?? ($documento ? $documento->fecha_oficio : null),
            'titular_firma_oficio' => $validated['titular_firma_oficio'] ?? ($documento ? $documento->titular_firma_oficio : null),
            'oficio_asignacion' => $validated['oficio_asignacion'] ?? ($documento ? $documento->oficio_asignacion : null),
            'fecha_asignacion' => $validated['fecha_asignacion'] ?? ($documento ? $documento->fecha_asignacion : null),
            'titular_firma_asignacion' => $validated['titular_firma_asignacion'] ?? ($documento ? $documento->titular_firma_asignacion : null),
            'archivo_oficio_comision' => $archivoComision['path'] ?? ($documento ? $documento->archivo_oficio_comision : null),
            'archivo_oficio_asignacion' => $archivoAsignacion['path'] ?? ($documento ? $documento->archivo_oficio_asignacion : null),
        ];

        if ($archivoParaMetadatos) {
            $data['archivo_path'] = $archivoParaMetadatos['path'];
            $data['archivo_nombre'] = $archivoParaMetadatos['nombre'];
            $data['archivo_mime'] = $archivoParaMetadatos['mime'];
            $data['archivo_size'] = $archivoParaMetadatos['tamano'];
            $data['hash_sha256'] = $archivoParaMetadatos['hash'];
        } elseif ($documento) {
            $data['archivo_path'] = $documento->archivo_path;
            $data['archivo_nombre'] = $documento->archivo_nombre;
            $data['archivo_mime'] = $documento->archivo_mime;
            $data['archivo_size'] = $documento->archivo_size;
            $data['hash_sha256'] = $documento->hash_sha256;
        }

        return $data;
    }

    private function guardarArchivo(Request $request, string $campo, Personal $personal, ?string $rutaAnterior = null): ?array
    {
        if (!$request->hasFile($campo)) {
            return null;
        }

        $archivo = $request->file($campo);
        $ruta = $archivo->store('personals/' . $personal->id . '/documentos', 'public');

        $this->eliminarArchivo($rutaAnterior);

        return [
            'path' => $ruta,
            'nombre' => $archivo->getClientOriginalName(),
            'mime' => $archivo->getClientMimeType(),
            'tamano' => $archivo->getSize(),
            'hash' => hash_file('sha256', $archivo->getRealPath()),
        ];
    }

    private function eliminarArchivo(?string $ruta): void
    {
        if ($ruta && Storage::disk('public')->exists($ruta)) {
            Storage::disk('public')->delete($ruta);
        }
    }

    private function tipoDocumentoOficiosId(): int
    {
        $tipo = DocumentoTipo::query()
            ->where('clave', 'OFICIOS_PERSONAL')
            ->first();

        if (!$tipo) {
            $tipo = new DocumentoTipo();
            $tipo->clave = 'OFICIOS_PERSONAL';
            $tipo->nombre = 'Oficios de personal';
            $tipo->requiere_vigencia = false;
            $tipo->dias_vigencia = null;
            $tipo->sensible = true;
            $tipo->activo = true;
            $tipo->save();
        }

        return (int) $tipo->id;
    }
}
