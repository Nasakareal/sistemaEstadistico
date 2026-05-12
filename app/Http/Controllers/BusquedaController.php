<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hechos;
use App\Models\Vehiculo;
use App\Models\Conductor;
use App\Support\HechoAccess;

class BusquedaController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->input('query', ''));
        $origen = $this->origenValido($request->input('origen', 'todos'));

        if (!$query) {
            return view('busqueda.index', [
                'conductores' => collect(),
                'vehiculos' => collect(),
                'hechos' => collect(),
                'query' => null,
                'origen' => $origen,
            ]);
        }

        $like = $this->like($query);
        $usuario = $request->user();
        $limite = 50;

        $conductores = Conductor::query()
            ->with([
                'vehiculos' => function ($vehiculos) use ($usuario, $origen) {
                    $vehiculos->with(['hechos' => function ($hechos) use ($usuario, $origen) {
                        $this->aplicarVisibilidadYOrigen($hechos, $usuario, $origen);
                        $hechos->orderByDesc('fecha')->orderByDesc('id');
                    }]);
                },
            ])
            ->where(function ($q) use ($like) {
                $q->where('nombre', 'LIKE', $like)
                    ->orWhere('telefono', 'LIKE', $like)
                    ->orWhere('domicilio', 'LIKE', $like)
                    ->orWhere('numero_licencia', 'LIKE', $like);
            })
            ->whereHas('vehiculos.hechos', function ($hechos) use ($usuario, $origen) {
                $this->aplicarVisibilidadYOrigen($hechos, $usuario, $origen);
            })
            ->orderBy('nombre')
            ->limit($limite)
            ->get();

        $vehiculos = Vehiculo::query()
            ->with([
                'conductores:id,nombre',
                'hechos' => function ($hechos) use ($usuario, $origen) {
                    $this->aplicarVisibilidadYOrigen($hechos, $usuario, $origen);
                    $hechos->orderByDesc('fecha')->orderByDesc('id');
                },
            ])
            ->where(function ($q) use ($like, $query) {
                $q->where('marca', 'LIKE', $like)
                    ->orWhere('modelo', 'LIKE', $like)
                    ->orWhere('placas', 'LIKE', $like)
                    ->orWhere('serie', 'LIKE', $like);

                $this->orWhereNormalizado($q, 'vehiculos.placas', $query);
                $this->orWhereNormalizado($q, 'vehiculos.serie', $query);
            })
            ->whereHas('hechos', function ($hechos) use ($usuario, $origen) {
                $this->aplicarVisibilidadYOrigen($hechos, $usuario, $origen);
            })
            ->orderByDesc('id')
            ->limit($limite)
            ->get();

        $hechos = Hechos::query()
            ->with(['vehiculos.conductores'])
            ->where(function ($q) use ($query, $like) {
                if (is_numeric($query)) {
                    $q->orWhere('id', $query);
                }

                $q->orWhere('folio_c5i', 'LIKE', $like)
                    ->orWhere('calle', 'LIKE', $like)
                    ->orWhere('colonia', 'LIKE', $like)
                    ->orWhere('municipio', 'LIKE', $like)
                    ->orWhereHas('vehiculos', function ($vehiculos) use ($query, $like) {
                        $vehiculos->where(function ($v) use ($query, $like) {
                            $v->where('placas', 'LIKE', $like)
                                ->orWhere('serie', 'LIKE', $like);

                            $this->orWhereNormalizado($v, 'vehiculos.placas', $query);
                            $this->orWhereNormalizado($v, 'vehiculos.serie', $query);
                        });
                    })
                    ->orWhereHas('vehiculos.conductores', function ($conductores) use ($like) {
                        $conductores->where('nombre', 'LIKE', $like);
                    });
            });

        $this->aplicarVisibilidadYOrigen($hechos, $usuario, $origen);

        $hechos = $hechos
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();

        return view('busqueda.index', compact('conductores', 'vehiculos', 'hechos', 'query', 'origen'));
    }

    private function aplicarVisibilidadYOrigen($query, $usuario, string $origen): void
    {
        HechoAccess::applyVisibilityScope($query, $usuario);

        if ($origen === 'historicos') {
            $query->where('fuente_ubicacion', 'legacy_peritos');
        } elseif ($origen === 'actuales') {
            $query->where(function ($q) {
                $q->whereNull('fuente_ubicacion')
                    ->orWhere('fuente_ubicacion', '<>', 'legacy_peritos');
            });
        }
    }

    private function origenValido($origen): string
    {
        $origen = strtolower(trim((string) $origen));

        return in_array($origen, ['todos', 'actuales', 'historicos'], true) ? $origen : 'todos';
    }

    private function like(string $query): string
    {
        return '%' . addcslashes($query, "%_\\") . '%';
    }

    private function orWhereNormalizado($query, string $columna, string $valor): void
    {
        $normalizado = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $valor));

        if ($normalizado === '') {
            return;
        }

        $query->orWhereRaw(
            "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$columna}, ''), '-', ''), ' ', ''), '.', ''), '/', '')) LIKE ?",
            ['%' . $normalizado . '%']
        );
    }
}
