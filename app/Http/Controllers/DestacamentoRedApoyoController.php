<?php

namespace App\Http\Controllers;

use App\Models\Delegacion;
use App\Models\Destacamento;
use App\Models\DestacamentoRedApoyo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DestacamentoRedApoyoController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->only(['q', 'region_id', 'delegacion_id', 'nivel_gobierno', 'activo']);
        $q = trim((string) ($filtros['q'] ?? ''));

        $query = DestacamentoRedApoyo::query()
            ->with(['delegacion.padre', 'destacamento'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($where) use ($q) {
                    $where->where('institucion', 'like', "%{$q}%")
                        ->orWhere('contacto', 'like', "%{$q}%")
                        ->orWhere('cargo', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%")
                        ->orWhere('telefono_secundario', 'like', "%{$q}%")
                        ->orWhere('municipio', 'like', "%{$q}%")
                        ->orWhere('region', 'like', "%{$q}%");
                });
            })
            ->when(!empty($filtros['nivel_gobierno']) && isset(DestacamentoRedApoyo::NIVELES_GOBIERNO[$filtros['nivel_gobierno']]), function ($query) use ($filtros) {
                $query->where('nivel_gobierno', $filtros['nivel_gobierno']);
            })
            ->when(($filtros['activo'] ?? '') !== '', function ($query) use ($filtros) {
                $query->where('activo', (int) $filtros['activo'] ? 1 : 0);
            });

        $this->aplicarFiltroTerritorial($query, $filtros);

        $redApoyos = $query
            ->orderBy('region')
            ->orderBy('orden')
            ->orderBy('nivel_gobierno')
            ->orderBy('institucion')
            ->get();

        return view('admin.settings.directorio_red_apoyo.index', [
            'redApoyos' => $redApoyos,
            'nivelesGobierno' => DestacamentoRedApoyo::NIVELES_GOBIERNO,
            'tiposApoyo' => DestacamentoRedApoyo::TIPOS_APOYO_LABELS,
            'regiones' => $this->regiones(),
            'delegacionesAgrupadas' => $this->delegacionesAgrupadas(),
            'filtros' => $filtros,
        ]);
    }

    public function create()
    {
        return view('admin.settings.directorio_red_apoyo.create', $this->formData(new DestacamentoRedApoyo([
            'activo' => true,
            'nivel_gobierno' => 'Federal',
            'tipo_apoyo' => 'Seguridad publica',
        ])));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data = $this->normalizarData($data);

        $redApoyo = DestacamentoRedApoyo::create($data);

        return redirect()
            ->route('directorio_red_apoyo.show', $redApoyo)
            ->with('success', 'Contacto de red de apoyo registrado correctamente.');
    }

    public function show(DestacamentoRedApoyo $redApoyo)
    {
        $redApoyo->load(['delegacion.padre', 'destacamento']);

        return view('admin.settings.directorio_red_apoyo.show', [
            'redApoyo' => $redApoyo,
        ]);
    }

    public function edit(DestacamentoRedApoyo $redApoyo)
    {
        $redApoyo->load(['delegacion.padre', 'destacamento']);

        return view('admin.settings.directorio_red_apoyo.edit', $this->formData($redApoyo));
    }

    public function update(Request $request, DestacamentoRedApoyo $redApoyo)
    {
        $data = $this->validated($request);
        $redApoyo->update($this->normalizarData($data));

        return redirect()
            ->route('directorio_red_apoyo.show', $redApoyo)
            ->with('success', 'Contacto de red de apoyo actualizado correctamente.');
    }

    public function destroy(DestacamentoRedApoyo $redApoyo)
    {
        $redApoyo->delete();

        return redirect()
            ->route('directorio_red_apoyo.index')
            ->with('success', 'Contacto de red de apoyo eliminado correctamente.');
    }

    private function formData(DestacamentoRedApoyo $redApoyo): array
    {
        return [
            'redApoyo' => $redApoyo,
            'nivelesGobierno' => DestacamentoRedApoyo::NIVELES_GOBIERNO,
            'tiposApoyo' => DestacamentoRedApoyo::TIPOS_APOYO_LABELS,
            'delegacionesAgrupadas' => $this->delegacionesAgrupadas(),
            'destacamentos' => Destacamento::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'region' => ['nullable', 'string', 'max:150'],
            'delegacion_id' => ['nullable', 'integer', 'exists:delegaciones,id'],
            'destacamento_id' => ['nullable', 'integer', 'exists:destacamentos,id'],
            'tipo_apoyo' => ['required', 'string', Rule::in(array_keys(DestacamentoRedApoyo::TIPOS_APOYO))],
            'nivel_gobierno' => ['required', 'string', Rule::in(array_keys(DestacamentoRedApoyo::NIVELES_GOBIERNO))],
            'institucion' => ['required', 'string', 'max:255'],
            'contacto' => ['nullable', 'string', 'max:255'],
            'cargo' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'telefono_secundario' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:999'],
            'activo' => ['nullable', 'boolean'],
        ]);
    }

    private function normalizarData(array $data): array
    {
        $esEstatal = ($data['nivel_gobierno'] ?? null) === 'Estatal';

        $delegacion = $esEstatal && !empty($data['delegacion_id'])
            ? Delegacion::query()->with('padre')->find((int) $data['delegacion_id'])
            : null;

        $destacamento = $esEstatal && !empty($data['destacamento_id'])
            ? Destacamento::query()->find((int) $data['destacamento_id'])
            : null;

        $regional = $delegacion
            ? ($delegacion->padre ?: $delegacion)
            : null;

        $region = $regional
            ? $this->limpiarTexto($regional->nombre)
            : $this->limpiarTexto($data['region'] ?? null);

        $municipio = $this->limpiarTexto($data['municipio'] ?? null)
            ?: ($delegacion ? $this->limpiarTexto($delegacion->municipio) : null)
            ?: ($destacamento ? $this->limpiarTexto($destacamento->municipio) : null);

        return [
            'delegacion_id' => $delegacion ? (int) $delegacion->id : null,
            'destacamento_id' => $destacamento ? (int) $destacamento->id : null,
            'region' => $region,
            'tipo_apoyo' => $data['tipo_apoyo'],
            'nivel_gobierno' => $data['nivel_gobierno'],
            'institucion' => $this->limpiarTexto($data['institucion'] ?? ''),
            'contacto' => $this->limpiarTexto($data['contacto'] ?? null),
            'cargo' => $this->limpiarTexto($data['cargo'] ?? null),
            'telefono' => $this->limpiarTelefono($data['telefono'] ?? null),
            'telefono_secundario' => $this->limpiarTelefono($data['telefono_secundario'] ?? null),
            'direccion' => $this->limpiarTexto($data['direccion'] ?? null),
            'municipio' => $municipio,
            'observaciones' => $this->limpiarTexto($data['observaciones'] ?? null),
            'activo' => (bool) ($data['activo'] ?? false),
            'orden' => (int) ($data['orden'] ?? 0),
        ];
    }

    private function aplicarFiltroTerritorial($query, array $filtros): void
    {
        if (!empty($filtros['delegacion_id'])) {
            $query->where('delegacion_id', (int) $filtros['delegacion_id']);
            return;
        }

        if (empty($filtros['region_id'])) {
            return;
        }

        $regionId = (int) $filtros['region_id'];
        $region = Delegacion::query()->find($regionId);
        $hijasIds = Delegacion::query()
            ->where('delegacion_padre_id', $regionId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = array_merge([$regionId], $hijasIds);

        $query->where(function ($where) use ($ids, $region) {
            $where->whereIn('delegacion_id', $ids);

            if ($region) {
                $where->orWhere('region', $region->nombre);
            }
        });
    }

    private function regiones()
    {
        return Delegacion::query()
            ->whereNull('delegacion_padre_id')
            ->withCount('hijas')
            ->orderBy('nombre')
            ->get();
    }

    private function delegacionesAgrupadas()
    {
        return Delegacion::query()
            ->whereNull('delegacion_padre_id')
            ->with(['hijas' => function ($query) {
                $query->orderBy('nombre');
            }])
            ->orderBy('nombre')
            ->get();
    }

    private function limpiarTexto(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function limpiarTelefono(?string $value): ?string
    {
        $value = preg_replace('/[^\d+()\-\s]/', '', (string) $value);
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
