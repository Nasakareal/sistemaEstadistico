<?php

namespace App\Http\Controllers;

use App\Models\DocumentoTipo;
use App\Models\Personal;
use App\Models\PersonalDocumento;
use App\Services\Documentos\DocumentoArchivoStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PersonalDocumentoController extends Controller
{
    private $archivoStorage;

    public function __construct(DocumentoArchivoStorage $archivoStorage)
    {
        $this->archivoStorage = $archivoStorage;
    }

    public function showSigned(PersonalDocumento $documento, string $archivo)
    {
        [$ruta, $nombre] = $this->archivoParaDescarga($documento, $archivo);

        return $this->archivoStorage->response($ruta, $nombre, 'attachment');
    }

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
        $documentoTipoId = $validated['documento_tipo_id']
            ?? ($documento ? $documento->documento_tipo_id : null)
            ?? $this->tipoDocumentoOficiosId();
        $usaCamposOficio = $this->documentoTipoUsaCamposOficio((int) $documentoTipoId);

        $archivoGeneral = $this->guardarArchivo($request, 'archivo', $personal, $documento ? $documento->archivo_path : null);
        $archivoComision = $usaCamposOficio
            ? $this->guardarArchivo($request, 'archivo_oficio_comision', $personal, $documento ? $documento->archivo_oficio_comision : null)
            : null;
        $archivoAsignacion = $usaCamposOficio
            ? $this->guardarArchivo($request, 'archivo_oficio_asignacion', $personal, $documento ? $documento->archivo_oficio_asignacion : null)
            : null;

        if (!$usaCamposOficio && $documento) {
            $this->eliminarArchivo($documento->archivo_oficio_comision);
            $this->eliminarArchivo($documento->archivo_oficio_asignacion);
        }

        $numeroOficio = $usaCamposOficio
            ? ($validated['oficio_asignacion'] ?? $validated['oficio_comision_secretario'] ?? null)
            : null;
        $fechaOficio = $usaCamposOficio
            ? ($validated['fecha_oficio'] ?? $validated['fecha_asignacion'] ?? null)
            : null;
        $archivoParaMetadatos = $archivoGeneral ?? ($usaCamposOficio ? ($archivoComision ?? $archivoAsignacion) : null);

        $data = [
            'personal_id' => $personal->id,
            'documento_tipo_id' => $documentoTipoId,
            'numero' => $validated['numero']
                ?? $numeroOficio
                ?? ($documento ? $documento->numero : null),
            'fecha_emision' => $validated['fecha_emision']
                ?? $fechaOficio
                ?? ($documento ? $documento->fecha_emision : null),
            'fecha_vencimiento' => $validated['fecha_vencimiento'] ?? ($documento ? $documento->fecha_vencimiento : null),
            'activo' => (bool) ($validated['activo'] ?? ($documento ? $documento->activo : null) ?? true),
            'observaciones' => $validated['observaciones'] ?? ($documento ? $documento->observaciones : null),
            'oficio_comision_secretario' => $usaCamposOficio ? ($validated['oficio_comision_secretario'] ?? ($documento ? $documento->oficio_comision_secretario : null)) : null,
            'fecha_oficio' => $usaCamposOficio ? ($validated['fecha_oficio'] ?? ($documento ? $documento->fecha_oficio : null)) : null,
            'titular_firma_oficio' => $usaCamposOficio ? ($validated['titular_firma_oficio'] ?? ($documento ? $documento->titular_firma_oficio : null)) : null,
            'oficio_asignacion' => $usaCamposOficio ? ($validated['oficio_asignacion'] ?? ($documento ? $documento->oficio_asignacion : null)) : null,
            'fecha_asignacion' => $usaCamposOficio ? ($validated['fecha_asignacion'] ?? ($documento ? $documento->fecha_asignacion : null)) : null,
            'titular_firma_asignacion' => $usaCamposOficio ? ($validated['titular_firma_asignacion'] ?? ($documento ? $documento->titular_firma_asignacion : null)) : null,
            'archivo_oficio_comision' => $usaCamposOficio ? ($archivoComision['path'] ?? ($documento ? $documento->archivo_oficio_comision : null)) : null,
            'archivo_oficio_asignacion' => $usaCamposOficio ? ($archivoAsignacion['path'] ?? ($documento ? $documento->archivo_oficio_asignacion : null)) : null,
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

    private function documentoTipoUsaCamposOficio(int $tipoId): bool
    {
        return DocumentoTipo::query()
            ->whereKey($tipoId)
            ->where('clave', 'OFICIOS_PERSONAL')
            ->exists();
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

    private function archivoParaDescarga(PersonalDocumento $documento, string $archivo): array
    {
        switch ($archivo) {
            case 'general':
                $ruta = $documento->archivo_path;
                $nombre = $documento->archivo_nombre ?: basename((string) $ruta);
                break;
            case 'comision':
                $ruta = $documento->archivo_oficio_comision;
                $nombre = basename((string) $ruta);
                break;
            case 'asignacion':
                $ruta = $documento->archivo_oficio_asignacion;
                $nombre = basename((string) $ruta);
                break;
            default:
                abort(404);
        }

        $ruta = str_replace('\\', '/', trim((string) $ruta));

        if ($ruta === '' || strpos($ruta, '..') !== false || !$this->startsWith($ruta, 'personals/')) {
            abort(404);
        }

        return [$ruta, $nombre ?: basename($ruta)];
    }

    private function startsWith(string $value, string $prefix): bool
    {
        return substr($value, 0, strlen($prefix)) === $prefix;
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
