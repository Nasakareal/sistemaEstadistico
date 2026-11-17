<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArchivoAnaquel;
use App\Models\ArchivoBloque;
use App\Models\ArchivoLibro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ArchivoBloqueController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'anaquel_id' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $bloques = ArchivoBloque::with('anaquel.area')
            ->when(isset($filtros['anaquel_id']), fn ($q) => $q->where('anaquel_id', $filtros['anaquel_id']))
            ->orderBy('anaquel_id')->orderBy('fila')->orderBy('columna')
            ->paginate((int) ($filtros['per_page'] ?? 30))->withQueryString();

        $ids = $bloques->getCollection()->pluck('id')->all();
        $ocupaciones = ArchivoLibro::whereIn('bloque_actual_id', $ids)
            ->selectRaw('bloque_actual_id, COUNT(*) as total')
            ->groupBy('bloque_actual_id')->pluck('total', 'bloque_actual_id');
        $reservas = ArchivoLibro::whereIn('bloque_destino_id', $ids)
            ->where(fn ($q) => $q->whereNull('bloque_actual_id')
                ->orWhereColumn('bloque_actual_id', '<>', 'bloque_destino_id'))
            ->selectRaw('bloque_destino_id, COUNT(*) as total')
            ->groupBy('bloque_destino_id')->pluck('total', 'bloque_destino_id');

        $bloques->getCollection()->transform(fn ($bloque) => $this->datosBloque(
            $bloque, false, (int) ($ocupaciones[$bloque->id] ?? 0), (int) ($reservas[$bloque->id] ?? 0)
        ));
        return \App\Support\ApiPaginationResponse::make($bloques);
    }

    public function show(ArchivoBloque $bloque)
    {
        $bloque->load('anaquel.area');
        return response()->json(['data' => $this->datosBloque($bloque, true)]);
    }

    public function porQr(string $uuid)
    {
        $bloque = ArchivoBloque::with('anaquel.area')->where('qr_uuid', $uuid)->firstOrFail();
        return response()->json(['data' => $this->datosBloque($bloque, true)]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate($this->reglas());
        $this->validarCuadricula($datos);
        $datos['qr_uuid'] = (string) Str::uuid();
        $bloque = ArchivoBloque::create($datos);
        $bloque->load('anaquel.area');

        return response()->json(['message' => 'Bloque registrado correctamente.', 'data' => $this->datosBloque($bloque)], 201);
    }

    public function update(Request $request, ArchivoBloque $bloque)
    {
        $entrada = $request->isMethod('patch')
            ? array_merge($bloque->only([
                'anaquel_id', 'codigo', 'fila', 'columna', 'capacidad_libros',
                'orden_apilado', 'activo', 'observaciones',
            ]), $request->all())
            : $request->all();
        $datos = validator($entrada, $this->reglas($bloque))->validate();
        $this->validarCuadricula($datos);

        DB::transaction(function () use ($bloque, $datos) {
            $bloque = ArchivoBloque::whereKey($bloque->id)->lockForUpdate()->firstOrFail();
            $ocupacion = ArchivoLibro::where(fn ($q) => $q->where('bloque_actual_id', $bloque->id)
                ->orWhere('bloque_destino_id', $bloque->id))->count();

            if ($ocupacion && (int) $datos['anaquel_id'] !== (int) $bloque->anaquel_id) {
                throw ValidationException::withMessages(['anaquel_id' => 'No puedes cambiar de anaquel un bloque con libros.']);
            }
            if ($ocupacion > (int) $datos['capacidad_libros']) {
                throw ValidationException::withMessages(['capacidad_libros' => "Hay {$ocupacion} libros almacenados o reservados."]);
            }
            if ($ocupacion && !(bool) $datos['activo']) {
                throw ValidationException::withMessages(['activo' => 'No puedes desactivar un bloque que contiene libros o reservas.']);
            }
            if (!$bloque->qr_uuid) {
                $datos['qr_uuid'] = (string) Str::uuid();
            }
            $bloque->update($datos);
        }, 3);

        $bloque->refresh()->load('anaquel.area');
        return response()->json(['message' => 'Bloque actualizado correctamente.', 'data' => $this->datosBloque($bloque)]);
    }

    private function datosBloque(ArchivoBloque $bloque, bool $conLibros = false, ?int $ocupados = null, ?int $reservados = null): array
    {
        $ocupados ??= ArchivoLibro::where('bloque_actual_id', $bloque->id)->count();
        $reservados ??= ArchivoLibro::where('bloque_destino_id', $bloque->id)
            ->where(fn ($q) => $q->whereNull('bloque_actual_id')
                ->orWhereColumn('bloque_actual_id', '<>', 'bloque_destino_id'))->count();

        $datos = [
            'id' => $bloque->id,
            'anaquel_id' => $bloque->anaquel_id,
            'anaquel' => $bloque->anaquel ? [
                'id' => $bloque->anaquel->id,
                'codigo' => $bloque->anaquel->codigo,
                'area_id' => $bloque->anaquel->area_id,
                'area_nombre' => $bloque->anaquel->area?->nombre,
            ] : null,
            'codigo' => $bloque->codigo,
            'fila' => $bloque->fila,
            'columna' => $bloque->columna,
            'capacidad_libros' => $bloque->capacidad_libros,
            'ocupados' => $ocupados,
            'reservados' => $reservados,
            'disponibles' => max(0, $bloque->capacidad_libros - $ocupados - $reservados),
            'orden_apilado' => $bloque->orden_apilado,
            'qr_uuid' => $bloque->qr_uuid,
            'activo' => $bloque->activo,
            'observaciones' => $bloque->observaciones,
        ];

        if ($conLibros) {
            $datos['libros'] = ArchivoLibro::where('bloque_actual_id', $bloque->id)
                ->orderBy('posicion_actual')->get(['id', 'codigo', 'titulo', 'posicion_actual', 'estado_fisico']);
            $datos['reservas'] = ArchivoLibro::where('bloque_destino_id', $bloque->id)
                ->where(fn ($q) => $q->whereNull('bloque_actual_id')
                    ->orWhereColumn('bloque_actual_id', '<>', 'bloque_destino_id'))
                ->orderBy('posicion_destino')->get(['id', 'codigo', 'titulo', 'posicion_destino']);
        }

        return $datos;
    }

    private function validarCuadricula(array $datos): void
    {
        $anaquel = ArchivoAnaquel::findOrFail($datos['anaquel_id']);
        if ((int) $datos['fila'] > $anaquel->total_filas || (int) $datos['columna'] > $anaquel->total_columnas) {
            throw ValidationException::withMessages(['fila' => 'La fila o columna supera las dimensiones del anaquel.']);
        }
    }

    private function reglas(?ArchivoBloque $bloque = null): array
    {
        $anaquelId = request('anaquel_id', $bloque?->anaquel_id);
        $columna = request('columna', $bloque?->columna);
        return [
            'anaquel_id' => ['required', Rule::exists('archivo_anaqueles', 'id')->where('activo', true)],
            'codigo' => ['required', 'string', 'max:20', Rule::unique('archivo_bloques', 'codigo')
                ->where('anaquel_id', $anaquelId)->ignore($bloque?->id)],
            'fila' => ['required', 'integer', 'between:1,100', Rule::unique('archivo_bloques', 'fila')
                ->where('anaquel_id', $anaquelId)->where('columna', $columna)->ignore($bloque?->id)],
            'columna' => ['required', 'integer', 'between:1,100'],
            'capacidad_libros' => ['required', 'integer', 'between:1,65535'],
            'orden_apilado' => ['required', Rule::in(['arriba_abajo', 'abajo_arriba'])],
            'activo' => ['required', 'boolean'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
