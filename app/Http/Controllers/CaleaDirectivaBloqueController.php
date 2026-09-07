<?php

namespace App\Http\Controllers;

use App\Models\CaleaDirectivaBloque;
use App\Models\CaleaDirectivaSeccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CaleaDirectivaBloqueController extends Controller
{
    public function store(Request $request, $seccion)
    {
        $seccion = CaleaDirectivaSeccion::with('version.directiva')->findOrFail($seccion);

        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:calea_directiva_bloques,id',
            'orden' => 'nullable|integer|min:0|max:65535',
            'tipo' => 'nullable|string|max:100',
            'numero' => 'nullable|string|max:100',
            'titulo' => 'nullable|string|max:500',
            'contenido' => 'required|string',
            'pagina_inicio' => 'nullable|integer|min:1|max:65535',
            'pagina_fin' => 'nullable|integer|min:1|max:65535',
            'buscable' => 'nullable|boolean',
            'citable' => 'nullable|boolean',
        ]);

        $this->validarPaginas($validated);
        $this->validarPadre($validated['parent_id'] ?? null, $seccion->id);

        if (!isset($validated['orden'])) {
            $query = $seccion->bloques();

            if (!empty($validated['parent_id'])) {
                $query->where('parent_id', $validated['parent_id']);
            } else {
                $query->whereNull('parent_id');
            }

            $validated['orden'] = ((int) $query->max('orden')) + 1;
        }

        try {
            $bloque = CaleaDirectivaBloque::create([
                'calea_directiva_seccion_id' => $seccion->id,
                'parent_id' => $validated['parent_id'] ?? null,
                'orden' => $validated['orden'],
                'tipo' => $this->normalizarTexto($validated['tipo'] ?? null),
                'numero' => $this->normalizarTexto($validated['numero'] ?? null),
                'titulo' => $this->normalizarTexto($validated['titulo'] ?? null),
                'contenido' => $this->normalizarTexto($validated['contenido']),
                'pagina_inicio' => $validated['pagina_inicio'] ?? null,
                'pagina_fin' => $validated['pagina_fin'] ?? null,
                'buscable' => $request->has('buscable') ? $request->boolean('buscable') : true,
                'citable' => $request->has('citable') ? $request->boolean('citable') : true,
            ]);

            Log::info('Bloque CALEA creado', [
                'bloque_id' => $bloque->id,
                'seccion_id' => $seccion->id,
                'version_id' => $seccion->calea_directiva_version_id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('settings.calea.secciones.edit', $seccion->id)
                ->with('success', 'Bloque CALEA creado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear bloque CALEA: ' . $e->getMessage(), [
                'seccion_id' => $seccion->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->back()
                ->withErrors('No fue posible crear el bloque CALEA.')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $bloque = CaleaDirectivaBloque::query()
            ->with([
                'seccion.version.directiva',
                'padre',
                'hijos'
            ])
            ->findOrFail($id);

        $idsExcluidos = array_merge(
            [$bloque->id],
            $this->obtenerIdsDescendientes($bloque)
        );

        $bloquesDisponibles = CaleaDirectivaBloque::query()
            ->where('calea_directiva_seccion_id', $bloque->calea_directiva_seccion_id)
            ->whereNotIn('id', $idsExcluidos)
            ->orderBy('orden')
            ->get();

        $tipos = [
            'parrafo' => 'Párrafo',
            'subtitulo' => 'Subtítulo',
            'numeral' => 'Numeral',
            'inciso' => 'Inciso',
            'vineta' => 'Viñeta',
            'procedimiento' => 'Procedimiento',
            'fundamento' => 'Fundamento',
            'nota' => 'Nota',
            'definicion' => 'Definición',
            'advertencia' => 'Advertencia',
            'otro' => 'Otro',
        ];

        return view('admin.settings.calea.bloques.edit', compact(
            'bloque',
            'bloquesDisponibles',
            'tipos'
        ));
    }

    public function update(Request $request, $id)
    {
        $bloque = CaleaDirectivaBloque::with('seccion.version.directiva')->findOrFail($id);

        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:calea_directiva_bloques,id',
            'orden' => 'required|integer|min:0|max:65535',
            'tipo' => 'nullable|string|max:100',
            'numero' => 'nullable|string|max:100',
            'titulo' => 'nullable|string|max:500',
            'contenido' => 'required|string',
            'pagina_inicio' => 'nullable|integer|min:1|max:65535',
            'pagina_fin' => 'nullable|integer|min:1|max:65535',
            'buscable' => 'nullable|boolean',
            'citable' => 'nullable|boolean',
        ]);

        $this->validarPaginas($validated);

        $parentId = $validated['parent_id'] ?? null;

        $this->validarPadre(
            $parentId,
            $bloque->calea_directiva_seccion_id,
            $bloque
        );

        try {
            $bloque->update([
                'parent_id' => $parentId,
                'orden' => $validated['orden'],
                'tipo' => $this->normalizarTexto($validated['tipo'] ?? null),
                'numero' => $this->normalizarTexto($validated['numero'] ?? null),
                'titulo' => $this->normalizarTexto($validated['titulo'] ?? null),
                'contenido' => $this->normalizarTexto($validated['contenido']),
                'pagina_inicio' => $validated['pagina_inicio'] ?? null,
                'pagina_fin' => $validated['pagina_fin'] ?? null,
                'buscable' => $request->boolean('buscable'),
                'citable' => $request->boolean('citable'),
            ]);

            Log::info('Bloque CALEA actualizado', [
                'bloque_id' => $bloque->id,
                'seccion_id' => $bloque->calea_directiva_seccion_id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('settings.calea.bloques.edit', $bloque->id)
                ->with('success', 'Bloque CALEA actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar bloque CALEA: ' . $e->getMessage(), [
                'bloque_id' => $bloque->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->back()
                ->withErrors('No fue posible actualizar el bloque CALEA.')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $bloque = CaleaDirectivaBloque::findOrFail($id);

        $seccionId = $bloque->calea_directiva_seccion_id;
        $titulo = $bloque->titulo;
        $numero = $bloque->numero;

        try {
            $bloque->delete();

            Log::info('Bloque CALEA eliminado', [
                'bloque_id' => $id,
                'seccion_id' => $seccionId,
                'numero' => $numero,
                'titulo' => $titulo,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('settings.calea.secciones.edit', $seccionId)
                ->with('success', 'Bloque CALEA eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar bloque CALEA: ' . $e->getMessage(), [
                'bloque_id' => $id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->back()
                ->withErrors('No fue posible eliminar el bloque CALEA.');
        }
    }

    private function validarPadre(?int $parentId, int $seccionId, ?CaleaDirectivaBloque $bloqueActual = null): void
    {
        if (!$parentId) {
            return;
        }

        $padre = CaleaDirectivaBloque::findOrFail($parentId);

        if ((int) $padre->calea_directiva_seccion_id !== $seccionId) {
            throw ValidationException::withMessages([
                'parent_id' => 'El bloque padre debe pertenecer a la misma sección.',
            ]);
        }

        if ($bloqueActual && (int) $parentId === (int) $bloqueActual->id) {
            throw ValidationException::withMessages([
                'parent_id' => 'Un bloque no puede ser su propio bloque padre.',
            ]);
        }

        if ($bloqueActual) {
            $descendientes = $this->obtenerIdsDescendientes($bloqueActual);

            if (in_array((int) $parentId, $descendientes, true)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'No puedes asignar como padre a un bloque descendiente.',
                ]);
            }
        }
    }

    private function obtenerIdsDescendientes(CaleaDirectivaBloque $bloque): array
    {
        $ids = [];

        $hijos = CaleaDirectivaBloque::where('parent_id', $bloque->id)->get();

        foreach ($hijos as $hijo) {
            $ids[] = (int) $hijo->id;

            $ids = array_merge(
                $ids,
                $this->obtenerIdsDescendientes($hijo)
            );
        }

        return $ids;
    }

    private function validarPaginas(array $validated): void
    {
        $inicio = $validated['pagina_inicio'] ?? null;
        $fin = $validated['pagina_fin'] ?? null;

        if ($inicio && $fin && $fin < $inicio) {
            throw ValidationException::withMessages([
                'pagina_fin' => 'La página final no puede ser menor que la página inicial.',
            ]);
        }
    }

    private function normalizarTexto(?string $texto): ?string
    {
        $texto = trim((string) $texto);

        return $texto === '' ? null : $texto;
    }
}
