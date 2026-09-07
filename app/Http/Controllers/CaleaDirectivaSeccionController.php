<?php

namespace App\Http\Controllers;

use App\Models\CaleaDirectivaVersion;
use App\Models\CaleaDirectivaSeccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CaleaDirectivaSeccionController extends Controller
{
    public function store(Request $request, $version)
    {
        $version = CaleaDirectivaVersion::with('directiva')->findOrFail($version);

        $validated = $request->validate([
            'orden' => 'nullable|integer|min:0|max:65535',
            'numero' => 'nullable|string|max:50',
            'tipo' => 'nullable|string|max:100',
            'titulo' => 'required|string|max:500',
            'contenido' => 'nullable|string',
            'pagina_inicio' => 'nullable|integer|min:1|max:65535',
            'pagina_fin' => 'nullable|integer|min:1|max:65535',
        ]);

        $this->validarPaginas($validated);

        if (!isset($validated['orden'])) {
            $validated['orden'] = ((int) $version->secciones()->max('orden')) + 1;
        }

        try {
            $seccion = CaleaDirectivaSeccion::create([
                'calea_directiva_version_id' => $version->id,
                'orden' => $validated['orden'],
                'numero' => $this->normalizarTexto($validated['numero'] ?? null),
                'tipo' => $this->normalizarTexto($validated['tipo'] ?? null),
                'titulo' => $this->normalizarTexto($validated['titulo']),
                'contenido' => $this->normalizarTexto($validated['contenido'] ?? null),
                'pagina_inicio' => $validated['pagina_inicio'] ?? null,
                'pagina_fin' => $validated['pagina_fin'] ?? null,
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

        $tipos = [
            'proposito' => 'Propósito',
            'alcance' => 'Alcance',
            'marco_juridico' => 'Marco Jurídico',
            'directiva' => 'Directiva',
            'procedimiento' => 'Procedimiento',
            'definiciones' => 'Definiciones',
            'responsabilidades' => 'Responsabilidades',
            'supervision' => 'Supervisión',
            'anexos' => 'Anexos',
            'otro' => 'Otro',
        ];

        return view('admin.settings.calea.secciones.edit', compact(
            'seccion',
            'tipos'
        ));
    }

    public function update(Request $request, $id)
    {
        $seccion = CaleaDirectivaSeccion::with('version.directiva')->findOrFail($id);

        $validated = $request->validate([
            'orden' => 'required|integer|min:0|max:65535',
            'numero' => 'nullable|string|max:50',
            'tipo' => 'nullable|string|max:100',
            'titulo' => 'required|string|max:500',
            'contenido' => 'nullable|string',
            'pagina_inicio' => 'nullable|integer|min:1|max:65535',
            'pagina_fin' => 'nullable|integer|min:1|max:65535',
        ]);

        $this->validarPaginas($validated);

        try {
            $seccion->update([
                'orden' => $validated['orden'],
                'numero' => $this->normalizarTexto($validated['numero'] ?? null),
                'tipo' => $this->normalizarTexto($validated['tipo'] ?? null),
                'titulo' => $this->normalizarTexto($validated['titulo']),
                'contenido' => $this->normalizarTexto($validated['contenido'] ?? null),
                'pagina_inicio' => $validated['pagina_inicio'] ?? null,
                'pagina_fin' => $validated['pagina_fin'] ?? null,
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
