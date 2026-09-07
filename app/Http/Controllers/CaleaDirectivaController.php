<?php

namespace App\Http\Controllers;

use App\Models\CaleaDirectiva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CaleaDirectivaController extends Controller
{
    public function index(Request $request)
    {
        if ($request->routeIs('settings.calea.index')) {
            $directivas = CaleaDirectiva::query()
                ->with(['versionVigente'])
                ->withCount('versiones')
                ->orderBy('nivel_1')
                ->orderBy('nivel_2')
                ->orderBy('nivel_3')
                ->get();

            return view('admin.settings.calea.index', compact('directivas'));
        }

        $directivas = CaleaDirectiva::query()
            ->where('activo', true)
            ->whereHas('versiones', function ($query) {
                $query->where('vigente', true)
                    ->where('publicada', true);
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

        $categorias = $directivas
            ->pluck('categoria')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('calea.index', compact('directivas', 'categorias'));
    }

    public function buscar(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'categoria' => 'nullable|string|max:255',
        ]);

        $texto = trim($validated['q'] ?? '');
        $categoria = trim($validated['categoria'] ?? '');

        $directivas = CaleaDirectiva::query()
            ->where('activo', true)
            ->whereHas('versiones', function ($query) {
                $query->where('vigente', true)
                    ->where('publicada', true);
            })
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
                                            $secciones->where('titulo', 'like', "%{$texto}%")
                                                ->orWhere('contenido', 'like', "%{$texto}%")
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
                'versionVigente' => function ($query) {
                    $query->where('publicada', true);
                }
            ])
            ->orderBy('nivel_1')
            ->orderBy('nivel_2')
            ->orderBy('nivel_3')
            ->get();

        $categorias = CaleaDirectiva::query()
            ->where('activo', true)
            ->whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        return view('calea.buscar', compact('directivas', 'texto', 'categoria', 'categorias'));
    }

    public function estudio()
    {
        $directivas = CaleaDirectiva::query()
            ->where('activo', true)
            ->whereHas('versiones', function ($query) {
                $query->where('vigente', true)
                    ->where('publicada', true);
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

        $categorias = $directivas
            ->groupBy(function ($directiva) {
                return $directiva->categoria ?: 'Sin categoría';
            });

        return view('calea.estudio', compact('directivas', 'categorias'));
    }

    public function show($id)
    {
        $directiva = CaleaDirectiva::query()
            ->where('activo', true)
            ->whereKey($id)
            ->whereHas('versiones', function ($query) {
                $query->where('vigente', true)
                    ->where('publicada', true);
            })
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

        return view('calea.show', compact('directiva'));
    }

    public function create()
    {
        $categorias = CaleaDirectiva::query()
            ->whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        return view('admin.settings.calea.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:30',
                'regex:/^\d+\.\d+\.\d+$/',
                'unique:calea_directivas,codigo',
            ],
            'titulo' => 'required|string|max:500',
            'categoria' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'activo' => 'nullable|boolean',
        ]);

        [$nivel1, $nivel2, $nivel3] = array_map('intval', explode('.', $validated['codigo']));

        try {
            $directiva = CaleaDirectiva::create([
                'codigo' => $validated['codigo'],
                'nivel_1' => $nivel1,
                'nivel_2' => $nivel2,
                'nivel_3' => $nivel3,
                'titulo' => trim($validated['titulo']),
                'categoria' => $this->normalizarTexto($validated['categoria'] ?? null),
                'descripcion' => $this->normalizarTexto($validated['descripcion'] ?? null),
                'activo' => array_key_exists('activo', $validated) ? (bool) $validated['activo'] : true,
            ]);

            Log::info('Directiva CALEA creada', [
                'id' => $directiva->id,
                'codigo' => $directiva->codigo,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('settings.calea.index')
                ->with('success', 'Directiva CALEA creada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear directiva CALEA: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('No fue posible crear la directiva CALEA.')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $directiva = CaleaDirectiva::query()
            ->with([
                'versiones' => function ($query) {
                    $query->orderByDesc('numero_version');
                }
            ])
            ->findOrFail($id);

        $categorias = CaleaDirectiva::query()
            ->whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        return view('admin.settings.calea.edit', compact('directiva', 'categorias'));
    }

    public function update(Request $request, $id)
    {
        $directiva = CaleaDirectiva::findOrFail($id);

        $validated = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:30',
                'regex:/^\d+\.\d+\.\d+$/',
                Rule::unique('calea_directivas', 'codigo')->ignore($directiva->id),
            ],
            'titulo' => 'required|string|max:500',
            'categoria' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'activo' => 'nullable|boolean',
        ]);

        [$nivel1, $nivel2, $nivel3] = array_map('intval', explode('.', $validated['codigo']));

        try {
            $directiva->update([
                'codigo' => $validated['codigo'],
                'nivel_1' => $nivel1,
                'nivel_2' => $nivel2,
                'nivel_3' => $nivel3,
                'titulo' => trim($validated['titulo']),
                'categoria' => $this->normalizarTexto($validated['categoria'] ?? null),
                'descripcion' => $this->normalizarTexto($validated['descripcion'] ?? null),
                'activo' => $request->boolean('activo'),
            ]);

            Log::info('Directiva CALEA actualizada', [
                'id' => $directiva->id,
                'codigo' => $directiva->codigo,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('settings.calea.index')
                ->with('success', 'Directiva CALEA actualizada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar directiva CALEA: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('No fue posible actualizar la directiva CALEA.')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $directiva = CaleaDirectiva::findOrFail($id);

            $directiva->activo = false;
            $directiva->save();

            Log::info('Directiva CALEA desactivada', [
                'id' => $directiva->id,
                'codigo' => $directiva->codigo,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('settings.calea.index')
                ->with('success', 'Directiva CALEA desactivada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al desactivar directiva CALEA: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('No fue posible desactivar la directiva CALEA.');
        }
    }

    private function normalizarTexto(?string $texto): ?string
    {
        $texto = trim((string) $texto);

        return $texto === '' ? null : $texto;
    }
}
