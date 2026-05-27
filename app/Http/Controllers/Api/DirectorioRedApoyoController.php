<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delegacion;
use App\Models\DestacamentoRedApoyo;
use Illuminate\Http\Request;

class DirectorioRedApoyoController extends Controller
{
    public function index(Request $request)
    {
        $query = DestacamentoRedApoyo::query()
            ->with(['delegacion.padre', 'destacamento'])
            ->where('activo', true);

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $query->where(function ($where) use ($q) {
                $where->where('institucion', 'like', "%{$q}%")
                    ->orWhere('contacto', 'like', "%{$q}%")
                    ->orWhere('cargo', 'like', "%{$q}%")
                    ->orWhere('telefono', 'like', "%{$q}%")
                    ->orWhere('telefono_secundario', 'like', "%{$q}%")
                    ->orWhere('municipio', 'like', "%{$q}%")
                    ->orWhere('region', 'like', "%{$q}%");
            });
        }

        $nivelGobierno = (string) $request->query('nivel_gobierno', '');
        if (isset(DestacamentoRedApoyo::NIVELES_GOBIERNO[$nivelGobierno])) {
            $query->where('nivel_gobierno', $nivelGobierno);
        }

        $tipoApoyo = (string) $request->query('tipo_apoyo', '');
        if (isset(DestacamentoRedApoyo::TIPOS_APOYO[$tipoApoyo])) {
            $query->where('tipo_apoyo', $tipoApoyo);
        }

        $this->aplicarFiltroTerritorial($query, $request);

        $limit = $this->clampInt($request->query('limit', 250), 1, 500, 250);

        $items = $query
            ->orderBy('region')
            ->orderBy('orden')
            ->orderBy('nivel_gobierno')
            ->orderBy('institucion')
            ->limit($limit)
            ->get()
            ->map(fn (DestacamentoRedApoyo $redApoyo) => $this->redApoyoToArray($redApoyo))
            ->values();

        $grouped = $items
            ->groupBy(fn (array $item) => $item['region'] ?: 'Sin region')
            ->map(function ($rows, string $region) {
                return [
                    'region' => $region,
                    'items' => $rows->values()->all(),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'data' => $items->all(),
            'grouped_by_region' => $grouped,
            'meta' => [
                'count' => $items->count(),
                'limit' => $limit,
                'filters' => [
                    'q' => $q,
                    'region_id' => $request->query('region_id'),
                    'delegacion_id' => $request->query('delegacion_id'),
                    'nivel_gobierno' => $nivelGobierno ?: null,
                    'tipo_apoyo' => $tipoApoyo ?: null,
                ],
            ],
        ]);
    }

    public function meta()
    {
        return response()->json([
            'regiones' => $this->regiones(),
            'niveles_gobierno' => DestacamentoRedApoyo::NIVELES_GOBIERNO,
            'tipos_apoyo' => DestacamentoRedApoyo::TIPOS_APOYO_LABELS,
        ]);
    }

    public function show(DestacamentoRedApoyo $redApoyo)
    {
        abort_unless($redApoyo->activo, 404);

        $redApoyo->load(['delegacion.padre', 'destacamento']);

        return response()->json([
            'data' => $this->redApoyoToArray($redApoyo),
        ]);
    }

    private function aplicarFiltroTerritorial($query, Request $request): void
    {
        if ($request->filled('delegacion_id')) {
            $query->where('delegacion_id', (int) $request->query('delegacion_id'));
            return;
        }

        if (!$request->filled('region_id')) {
            return;
        }

        $regionId = (int) $request->query('region_id');
        $region = Delegacion::query()->find($regionId);
        $hijasIds = Delegacion::query()
            ->where('delegacion_padre_id', $regionId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $query->where(function ($where) use ($regionId, $hijasIds, $region) {
            $where->whereIn('delegacion_id', array_merge([$regionId], $hijasIds));

            if ($region) {
                $where->orWhere('region', $region->nombre);
            }
        });
    }

    private function redApoyoToArray(DestacamentoRedApoyo $redApoyo): array
    {
        $delegacion = $redApoyo->delegacion;
        $padre = $delegacion ? $delegacion->padre : null;
        $telefonoWhatsApp = $this->telefonoWhatsApp($redApoyo->telefono);
        $telefonoSecundarioWhatsApp = $this->telefonoWhatsApp($redApoyo->telefono_secundario);

        return [
            'id' => (int) $redApoyo->id,
            'region' => $redApoyo->region,
            'nivel_gobierno' => $redApoyo->nivel_gobierno,
            'tipo_apoyo' => $redApoyo->tipo_apoyo,
            'tipo_apoyo_label' => DestacamentoRedApoyo::TIPOS_APOYO_LABELS[$redApoyo->tipo_apoyo] ?? $redApoyo->tipo_apoyo,
            'institucion' => $redApoyo->institucion,
            'contacto' => $redApoyo->contacto,
            'cargo' => $redApoyo->cargo,
            'telefono' => $redApoyo->telefono,
            'telefono_secundario' => $redApoyo->telefono_secundario,
            'telefonos' => array_values(array_filter([
                $redApoyo->telefono,
                $redApoyo->telefono_secundario,
            ])),
            'whatsapp' => [
                'telefono' => $telefonoWhatsApp,
                'url' => $telefonoWhatsApp ? "https://wa.me/{$telefonoWhatsApp}" : null,
                'telefono_secundario' => $telefonoSecundarioWhatsApp,
                'url_secundaria' => $telefonoSecundarioWhatsApp ? "https://wa.me/{$telefonoSecundarioWhatsApp}" : null,
            ],
            'direccion' => $redApoyo->direccion,
            'municipio' => $redApoyo->municipio,
            'observaciones' => $redApoyo->observaciones,
            'orden' => (int) $redApoyo->orden,
            'delegacion' => $delegacion ? [
                'id' => (int) $delegacion->id,
                'clave' => $delegacion->clave,
                'nombre' => $delegacion->nombre,
                'municipio' => $delegacion->municipio,
                'es_hija' => !empty($delegacion->delegacion_padre_id),
                'padre' => $padre ? [
                    'id' => (int) $padre->id,
                    'clave' => $padre->clave,
                    'nombre' => $padre->nombre,
                    'municipio' => $padre->municipio,
                ] : null,
            ] : null,
            'destacamento' => $redApoyo->destacamento ? [
                'id' => (int) $redApoyo->destacamento->id,
                'nombre' => $redApoyo->destacamento->nombre,
            ] : null,
            'updated_at' => optional($redApoyo->updated_at)->toDateTimeString(),
        ];
    }

    private function regiones(): array
    {
        return Delegacion::query()
            ->whereNull('delegacion_padre_id')
            ->with(['hijas' => function ($query) {
                $query->orderBy('nombre');
            }])
            ->orderBy('nombre')
            ->get()
            ->map(function (Delegacion $region) {
                return [
                    'id' => (int) $region->id,
                    'clave' => $region->clave,
                    'nombre' => $region->nombre,
                    'municipio' => $region->municipio,
                    'hijas' => $region->hijas->map(function (Delegacion $hija) {
                        return [
                            'id' => (int) $hija->id,
                            'clave' => $hija->clave,
                            'nombre' => $hija->nombre,
                            'municipio' => $hija->municipio,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function telefonoWhatsApp(?string $telefono): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $telefono);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            return '52' . $digits;
        }

        return $digits;
    }

    private function clampInt($value, int $min, int $max, int $default): int
    {
        if (!is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, (int) $value));
    }
}
