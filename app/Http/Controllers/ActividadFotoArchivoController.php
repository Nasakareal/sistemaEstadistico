<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\ActividadFoto;
use App\Services\Fotos\ActividadFotoBlobStorage;

class ActividadFotoArchivoController extends Controller
{
    private ActividadFotoBlobStorage $storage;

    public function __construct(ActividadFotoBlobStorage $storage)
    {
        $this->storage = $storage;
    }

    public function show(ActividadFoto $foto, string $tipo = 'original')
    {
        [$blobPath, $localPath] = $this->pathsForFoto($foto, $tipo);

        return $this->storage->response($blobPath, $localPath, $foto->foto_nombre_original ?: basename((string) $localPath));
    }

    public function principal(Actividad $actividad, string $tipo = 'original')
    {
        [$blobPath, $localPath] = $this->pathsForActividad($actividad, $tipo);

        return $this->storage->response($blobPath, $localPath, $actividad->foto_nombre_original ?: basename((string) $localPath));
    }

    private function pathsForFoto(ActividadFoto $foto, string $tipo): array
    {
        $tipo = $tipo === 'thumbnail' ? 'thumbnail' : 'original';

        if ($tipo === 'thumbnail') {
            return [
                $foto->foto_thumbnail_blob_path ?: $foto->foto_blob_path,
                $foto->foto_thumbnail_path ?: $foto->foto_path,
            ];
        }

        return [
            $foto->foto_blob_path ?: $foto->foto_thumbnail_blob_path,
            $foto->foto_path ?: $foto->foto_thumbnail_path,
        ];
    }

    private function pathsForActividad(Actividad $actividad, string $tipo): array
    {
        $tipo = $tipo === 'thumbnail' ? 'thumbnail' : 'original';

        if ($tipo === 'thumbnail') {
            return [
                $actividad->foto_thumbnail_blob_path ?: $actividad->foto_blob_path,
                $actividad->foto_thumbnail_path ?: $actividad->foto_path,
            ];
        }

        return [
            $actividad->foto_blob_path ?: $actividad->foto_thumbnail_blob_path,
            $actividad->foto_path ?: $actividad->foto_thumbnail_path,
        ];
    }
}
