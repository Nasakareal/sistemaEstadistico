<?php

namespace App\Http\Controllers;

use App\Models\CaleaDirectivaVersion;
use App\Models\CaleaDirectivaSeccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CaleaDirectivaSeccionController extends Controller
{
    public function store(Request $request, $version)
    {
        $version = CaleaDirectivaVersion::with('directiva')->findOrFail($version);

        $validated = $request->validate([
            'numero' => 'required|integer|min:0|max:65535',
            'tipo' => 'required|string|in:' . implode(',', array_keys(CaleaDirectivaSeccion::TIPOS)),
            'contenido' => 'nullable|string',
        ]);

        try {
            $seccion = CaleaDirectivaSeccion::create([
                'calea_directiva_version_id' => $version->id,
                'orden' => (int) $validated['numero'],
                'numero' => (string) $validated['numero'],
                'tipo' => $validated['tipo'],
                'titulo' => CaleaDirectivaSeccion::TIPOS[$validated['tipo']],
                'contenido' => $this->normalizarTexto($validated['contenido'] ?? null),
            ]);

            Log::info('Sección CALEA creada', [
                'seccion_id' => $seccion->id,
                'version_id' => $version->id,
                'directiva_id' => $version->calea_directiva_id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('settings.calea.versiones.edit', $version->id)
                ->with('success', 'Sección CALEA creada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear sección CALEA: ' . $e->getMessage(), [
                'version_id' => $version->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->back()
                ->withErrors('No fue posible crear la sección CALEA.')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $seccion = CaleaDirectivaSeccion::query()
            ->with([
                'version.directiva',
                'bloques' => function ($query) {
                    $query->orderBy('orden');
                }
            ])
            ->findOrFail($id);

        $tipos = CaleaDirectivaSeccion::TIPOS;

        return view('admin.settings.calea.secciones.edit', compact(
            'seccion',
            'tipos'
        ));
    }

    public function update(Request $request, $id)
    {
        $seccion = CaleaDirectivaSeccion::with('version.directiva')->findOrFail($id);

        $validated = $request->validate([
            'numero' => 'required|integer|min:0|max:65535',
            'tipo' => 'required|string|in:' . implode(',', array_keys(CaleaDirectivaSeccion::TIPOS)),
            'contenido' => 'nullable|string',
        ]);

        try {
            $seccion->update([
                'orden' => (int) $validated['numero'],
                'numero' => (string) $validated['numero'],
                'tipo' => $validated['tipo'],
                'titulo' => CaleaDirectivaSeccion::TIPOS[$validated['tipo']],
                'contenido' => $this->normalizarTexto($validated['contenido'] ?? null),
            ]);

            Log::info('Sección CALEA actualizada', [
                'seccion_id' => $seccion->id,
                'version_id' => $seccion->calea_directiva_version_id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('settings.calea.secciones.edit', $seccion->id)
                ->with('success', 'Sección CALEA actualizada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar sección CALEA: ' . $e->getMessage(), [
                'seccion_id' => $seccion->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->back()
                ->withErrors('No fue posible actualizar la sección CALEA.')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $seccion = CaleaDirectivaSeccion::findOrFail($id);

        $versionId = $seccion->calea_directiva_version_id;
        $titulo = $seccion->titulo;

        try {
            $seccion->delete();

            Log::info('Sección CALEA eliminada', [
                'seccion_id' => $id,
                'version_id' => $versionId,
                'titulo' => $titulo,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('settings.calea.versiones.edit', $versionId)
                ->with('success', 'Sección CALEA eliminada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar sección CALEA: ' . $e->getMessage(), [
                'seccion_id' => $id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->back()
                ->withErrors('No fue posible eliminar la sección CALEA.');
        }
    }

    private function normalizarTexto(?string $texto): ?string
    {
        $texto = trim((string) $texto);

        return $texto === '' ? null : $texto;
    }
}
