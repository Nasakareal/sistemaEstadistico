<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaleaDirectiva;
use App\Models\CaleaDirectivaVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CaleaController extends Controller
{
    public function meta()
    {
        $categorias = CaleaDirectiva::query()
            ->where('activo', true)
            ->whereHas('versiones', function ($query) {
                $query->where('vigente', true)
                    ->where('publicada', true);
            })
            ->whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        $total = CaleaDirectiva::query()
            ->where('activo', true)
            ->whereHas('versiones', function ($query) {
                $query->where('vigente', true)
                    ->where('publicada', true);
            })
            ->count();

        return response()->json([
            'data' => [
                'total_directivas' => $total,
                'categorias' => $categorias,
            ],
        ]);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'categoria' => 'nullable|string|max:255',
        ]);

        $categoria = trim($validated['categoria'] ?? '');

        $directivas = $this->queryDirectivasDisponibles()
            ->when($categoria !== '', function ($query) use ($categoria) {
                $query->where('categoria', $categoria);
            })
            ->with([
                'versionVigente' => function ($query) {
                    $query->where('publicada', true);
                }
            ])
            ->orderBy('nivel_1')
            ->orderBy('nivel_2')
            ->orderBy('nivel_3')
            ->get();

        return response()->json([
            'data' => $directivas->map(function ($directiva) {
                return $this->directivaResumen($directiva);
            })->values(),
            'meta' => [
                'total' => $directivas->count(),
            ],
        ]);
    }

    public function buscar(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'categoria' => 'nullable|string|max:255',
        ]);

        $texto = trim($validated['q'] ?? '');
        $categoria = trim($validated['categoria'] ?? '');

        $directivas = $this->queryDirectivasDisponibles()
            ->when($categoria !== '', function ($query) use ($categoria) {
                $query->where('categoria', $categoria);
            })
            ->when($texto !== '', function ($query) use ($texto) {
                $query->where(function ($busqueda) use ($texto) {
                    $busqueda->where('codigo', 'like', "%{$texto}%")
                        ->orWhere('titulo', 'like', "%{$texto}%")
                        ->orWhere('categoria', 'like', "%{$texto}%")
                        ->orWhere('descripcion', 'like', "%{$texto}%")
                        ->orWhereHas('versiones', function ($versiones) use ($texto) {
                            $versiones->where('vigente', true)
                                ->where('publicada', true)
                                ->where(function ($version) use ($texto) {
                                    $version->where('area_responsable', 'like', "%{$texto}%")
                                        ->orWhere('autoriza', 'like', "%{$texto}%")
                                        ->orWhere('realizado_por', 'like', "%{$texto}%")
                                        ->orWhere('texto_busqueda', 'like', "%{$texto}%")
                                        ->orWhereHas('secciones', function ($secciones) use ($texto) {
                                            $secciones->where(function ($seccion) use ($texto) {
                                                $seccion->where('titulo', 'like', "%{$texto}%")
                                                    ->orWhere('contenido', 'like', "%{$texto}%");
                                            })
                                            ->orWhereHas('bloques', function ($bloques) use ($texto) {
                                                $bloques->where('buscable', true)
                                                    ->where(function ($bloque) use ($texto) {
                                                        $bloque->where('titulo', 'like', "%{$texto}%")
                                                            ->orWhere('contenido', 'like', "%{$texto}%");
                                                    });
                                            });
                                        });
                                });
                        });
                });
            })
            ->with([
                'versionVigente' => function ($query) use ($texto) {
                    $query->where('publicada', true)
                        ->with([
                            'secciones' => function ($secciones) use ($texto) {
                                $secciones->orderBy('orden')
                                    ->with([
                                        'bloques' => function ($bloques) {
                                            $bloques->where('buscable', true)
                                                ->orderBy('orden');
                                        }
                                    ]);
                            }
                        ]);
                }
            ])
            ->orderBy('nivel_1')
            ->orderBy('nivel_2')
            ->orderBy('nivel_3')
            ->get();

        $resultados = $directivas->map(function ($directiva) use ($texto) {
            $resultado = $this->directivaResumen($directiva);

            if ($texto === '') {
                $resultado['coincidencias'] = [];

                return $resultado;
            }

            $coincidencias = [];
            $version = $directiva->versionVigente;

            if ($version) {
                foreach ($version->secciones as $seccion) {
                    if (
                        $this->contieneTexto($seccion->titulo, $texto) ||
                        $this->contieneTexto($seccion->contenido, $texto)
                    ) {
                        $coincidencias[] = [
                            'tipo' => 'seccion',
                            'id' => $seccion->id,
                            'numero' => $seccion->numero,
                            'titulo' => $seccion->titulo,
                            'contenido' => $this->fragmento($seccion->contenido, $texto),
                            'pagina_inicio' => $seccion->pagina_inicio,
                            'pagina_fin' => $seccion->pagina_fin,
                        ];
                    }

                    foreach ($seccion->bloques as $bloque) {
                        if (
                            $this->contieneTexto($bloque->titulo, $texto) ||
                            $this->contieneTexto($bloque->contenido, $texto)
                        ) {
                            $coincidencias[] = [
                                'tipo' => 'bloque',
                                'id' => $bloque->id,
                                'seccion_id' => $seccion->id,
                                'seccion' => $seccion->titulo,
                                'numero' => $bloque->numero,
                                'titulo' => $bloque->titulo,
                                'contenido' => $this->fragmento($bloque->contenido, $texto),
                                'pagina_inicio' => $bloque->pagina_inicio,
                                'pagina_fin' => $bloque->pagina_fin,
                                'citable' => (bool) $bloque->citable,
                            ];
                        }
                    }
                }
            }

            $resultado['coincidencias'] = $coincidencias;

            return $resultado;
        });

        return response()->json([
            'data' => $resultados->values(),
            'meta' => [
                'q' => $texto,
                'categoria' => $categoria ?: null,
                'total' => $resultados->count(),
            ],
        ]);
    }

    public function estudio(Request $request)
    {
        $validated = $request->validate([
            'categoria' => 'nullable|string|max:255',
        ]);

        $categoria = trim($validated['categoria'] ?? '');

        $directivas = $this->queryDirectivasDisponibles()
            ->when($categoria !== '', function ($query) use ($categoria) {
                $query->where('categoria', $categoria);
            })
            ->with([
                'versionVigente' => function ($query) {
                    $query->where('publicada', true)
                        ->with([
                            'secciones' => function ($secciones) {
                                $secciones->orderBy('orden');
                            }
                        ]);
                }
            ])
            ->orderBy('nivel_1')
            ->orderBy('nivel_2')
            ->orderBy('nivel_3')
            ->get();

        $grupos = $directivas
            ->groupBy(function ($directiva) {
                return $directiva->categoria ?: 'Sin categoría';
            })
            ->map(function ($items, $nombreCategoria) {
                return [
                    'categoria' => $nombreCategoria,
                    'directivas' => $items->map(function ($directiva) {
                        $version = $directiva->versionVigente;

                        return [
                            'id' => $directiva->id,
                            'codigo' => $directiva->codigo,
                            'titulo' => $directiva->titulo,
                            'descripcion' => $directiva->descripcion,
                            'version' => $version ? [
                                'id' => $version->id,
                                'numero_version' => $version->numero_version,
                                'nombre_version' => $version->nombre_version,
                                'fecha_revision' => optional($version->fecha_revision)->format('Y-m-d'),
                                'area_responsable' => $version->area_responsable,
                                'pdf_disponible' => !empty($version->archivo_path),
                                'pdf_url' => !empty($version->archivo_path)
                                    ? route('api.calea.versiones.pdf', $version->id)
                                    : null,
                                'secciones' => $version->secciones->map(function ($seccion) {
                                    return [
                                        'id' => $seccion->id,
                                        'orden' => $seccion->orden,
                                        'numero' => $seccion->numero,
                                        'tipo' => $seccion->tipo,
                                        'titulo' => $seccion->titulo,
                                        'contenido' => $seccion->contenido,
                                        'pagina_inicio' => $seccion->pagina_inicio,
                                        'pagina_fin' => $seccion->pagina_fin,
                                    ];
                                })->values(),
                            ] : null,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json([
            'data' => $grupos,
            'meta' => [
                'total_directivas' => $directivas->count(),
                'total_categorias' => $grupos->count(),
            ],
        ]);
    }

    public function show($directiva)
    {
        $directiva = $this->queryDirectivasDisponibles()
            ->whereKey($directiva)
            ->with([
                'versionVigente' => function ($query) {
                    $query->where('publicada', true)
                        ->with([
                            'secciones' => function ($secciones) {
                                $secciones->orderBy('orden')
                                    ->with([
                                        'bloquesRaiz' => function ($bloques) {
                                            $bloques->orderBy('orden')
                                                ->with('hijosRecursivos');
                                        }
                                    ]);
                            }
                        ]);
                }
            ])
            ->firstOrFail();

        $version = $directiva->versionVigente;

        return response()->json([
            'data' => [
                'id' => $directiva->id,
                'codigo' => $directiva->codigo,
                'titulo' => $directiva->titulo,
                'categoria' => $directiva->categoria,
                'descripcion' => $directiva->descripcion,
                'version' => $version ? [
                    'id' => $version->id,
                    'numero_version' => $version->numero_version,
                    'nombre_version' => $version->nombre_version,
                    'fecha_emision' => optional($version->fecha_emision)->format('Y-m-d'),
                    'mes_emision' => $version->mes_emision,
                    'anio_emision' => $version->anio_emision,
                    'fecha_revision' => optional($version->fecha_revision)->format('Y-m-d'),
                    'area_responsable' => $version->area_responsable,
                    'autoriza' => $version->autoriza,
                    'realizado_por' => $version->realizado_por,
                    'leyenda_documento' => $version->leyenda_documento,
                    'numero_paginas' => $version->numero_paginas,
                    'pdf_disponible' => !empty($version->archivo_path),
                    'pdf_url' => !empty($version->archivo_path)
                        ? route('api.calea.versiones.pdf', $version->id)
                        : null,
                    'secciones' => $version->secciones->map(function ($seccion) {
                        return [
                            'id' => $seccion->id,
                            'orden' => $seccion->orden,
                            'numero' => $seccion->numero,
                            'tipo' => $seccion->tipo,
                            'titulo' => $seccion->titulo,
                            'contenido' => $seccion->contenido,
                            'pagina_inicio' => $seccion->pagina_inicio,
                            'pagina_fin' => $seccion->pagina_fin,
                            'bloques' => $seccion->bloquesRaiz
                                ->map(function ($bloque) {
                                    return $this->bloqueApi($bloque);
                                })
                                ->values(),
                        ];
                    })->values(),
                ] : null,
            ],
        ]);
    }

    public function pdf($version)
    {
        $version = CaleaDirectivaVersion::query()
            ->with('directiva')
            ->whereKey($version)
            ->where('vigente', true)
            ->where('publicada', true)
            ->whereHas('directiva', function ($query) {
                $query->where('activo', true);
            })
            ->firstOrFail();

        abort_unless(
            $version->archivo_disk &&
            $version->archivo_path,
            404
        );

        $disk = Storage::disk($version->archivo_disk);

        abort_unless(
            $disk->exists($version->archivo_path),
            404
        );

        $stream = $disk->readStream($version->archivo_path);

        abort_unless(is_resource($stream), 404);

        $nombre = $version->archivo_nombre_original
            ?: 'directiva-calea-' . $version->id . '.pdf';

        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="directiva-calea.pdf"; filename*=UTF-8\'\'' . rawurlencode($nombre),
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($version->archivo_size) {
            $headers['Content-Length'] = $version->archivo_size;
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, $headers);
    }

    private function queryDirectivasDisponibles()
    {
        return CaleaDirectiva::query()
            ->where('activo', true)
            ->whereHas('versiones', function ($query) {
                $query->where('vigente', true)
                    ->where('publicada', true);
            });
    }

    private function directivaResumen(CaleaDirectiva $directiva): array
    {
        $version = $directiva->versionVigente;

        return [
            'id' => $directiva->id,
            'codigo' => $directiva->codigo,
            'titulo' => $directiva->titulo,
            'categoria' => $directiva->categoria,
            'descripcion' => $directiva->descripcion,
            'version' => $version ? [
                'id' => $version->id,
                'numero_version' => $version->numero_version,
                'nombre_version' => $version->nombre_version,
                'fecha_emision' => optional($version->fecha_emision)->format('Y-m-d'),
                'mes_emision' => $version->mes_emision,
                'anio_emision' => $version->anio_emision,
                'fecha_revision' => optional($version->fecha_revision)->format('Y-m-d'),
                'area_responsable' => $version->area_responsable,
                'numero_paginas' => $version->numero_paginas,
                'pdf_disponible' => !empty($version->archivo_path),
                'pdf_url' => !empty($version->archivo_path)
                    ? route('api.calea.versiones.pdf', $version->id)
                    : null,
            ] : null,
        ];
    }

    private function bloqueApi($bloque): array
    {
        return [
            'id' => $bloque->id,
            'parent_id' => $bloque->parent_id,
            'orden' => $bloque->orden,
            'tipo' => $bloque->tipo,
            'numero' => $bloque->numero,
            'titulo' => $bloque->titulo,
            'contenido' => $bloque->contenido,
            'pagina_inicio' => $bloque->pagina_inicio,
            'pagina_fin' => $bloque->pagina_fin,
            'buscable' => (bool) $bloque->buscable,
            'citable' => (bool) $bloque->citable,
            'hijos' => $bloque->hijosRecursivos
                ? $bloque->hijosRecursivos
                    ->map(function ($hijo) {
                        return $this->bloqueApi($hijo);
                    })
                    ->values()
                : [],
        ];
    }

    private function contieneTexto(?string $contenido, string $texto): bool
    {
        if (!$contenido || $texto === '') {
            return false;
        }

        return mb_stripos($contenido, $texto) !== false;
    }

    private function fragmento(?string $contenido, string $texto, int $longitud = 280): ?string
    {
        if (!$contenido) {
            return null;
        }

        $contenido = trim(preg_replace('/\s+/', ' ', $contenido));

        if ($texto === '') {
            return mb_substr($contenido, 0, $longitud);
        }

        $posicion = mb_stripos($contenido, $texto);

        if ($posicion === false) {
            return mb_substr($contenido, 0, $longitud);
        }

        $inicio = max(0, $posicion - 100);
        $fragmento = mb_substr($contenido, $inicio, $longitud);

        if ($inicio > 0) {
            $fragmento = '…' . $fragmento;
        }

        if (($inicio + $longitud) < mb_strlen($contenido)) {
            $fragmento .= '…';
        }

        return $fragmento;
    }
}
