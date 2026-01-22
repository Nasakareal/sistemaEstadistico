<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Throwable;

class VehiculosController extends Controller
{
    /**
     * Lista (opcional, por si lo ocupas).
     */
    public function index(Request $request)
    {
        try {
            $q = trim((string)$request->query('q', ''));

            $vehiculos = Vehiculo::query()
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($qq) use ($q) {
                        $qq->where('niv', 'like', "%{$q}%")
                           ->orWhere('placas', 'like', "%{$q}%");
                    });
                })
                ->orderByDesc('id')
                ->paginate(20);

            return $this->ok('Listado de vehículos.', $vehiculos);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al cargar los vehículos.', 500);
        }
    }

    /**
     * Crear vehículo
     */
    public function store(Request $request)
    {
        try {
            $data = $this->sanitize($request->all());

            $validator = Validator::make(
                $data,
                $this->rules(null),
                $this->messages(),
                $this->attributes()
            );

            if ($validator->fails()) {
                return $this->validationFailed($validator->errors()->toArray());
            }

            $vehiculo = Vehiculo::create($data);

            return $this->created('Vehículo registrado correctamente.', $vehiculo);

        } catch (QueryException $e) {
            // Aquí evitamos soltar el SQL crudo al cliente.
            return $this->fail('No se pudo guardar el vehículo. Verifica los datos e intenta de nuevo.', 500);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error inesperado al registrar el vehículo.', 500);
        }
    }

    /**
     * Ver 1 vehículo
     */
    public function show($id)
    {
        try {
            $vehiculo = Vehiculo::findOrFail($id);
            return $this->ok('Vehículo encontrado.', $vehiculo);
        } catch (ModelNotFoundException $e) {
            return $this->fail('No se encontró el vehículo solicitado.', 404);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al consultar el vehículo.', 500);
        }
    }

    /**
     * Actualizar vehículo
     */
    public function update(Request $request, $id)
    {
        try {
            $vehiculo = Vehiculo::findOrFail($id);

            $data = $this->sanitize($request->all());

            $validator = Validator::make(
                $data,
                $this->rules($vehiculo->id),
                $this->messages(),
                $this->attributes()
            );

            if ($validator->fails()) {
                return $this->validationFailed($validator->errors()->toArray());
            }

            $vehiculo->fill($data);
            $vehiculo->save();

            return $this->ok('Vehículo actualizado correctamente.', $vehiculo);

        } catch (ModelNotFoundException $e) {
            return $this->fail('No se encontró el vehículo a actualizar.', 404);
        } catch (QueryException $e) {
            return $this->fail('No se pudo actualizar el vehículo. Verifica los datos e intenta de nuevo.', 500);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error inesperado al actualizar el vehículo.', 500);
        }
    }

    /**
     * Eliminar vehículo (si aplica)
     */
    public function destroy($id)
    {
        try {
            $vehiculo = Vehiculo::findOrFail($id);
            $vehiculo->delete();

            return $this->ok('Vehículo eliminado correctamente.', null);

        } catch (ModelNotFoundException $e) {
            return $this->fail('No se encontró el vehículo a eliminar.', 404);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al eliminar el vehículo.', 500);
        }
    }

    /**
     * Reglas de validación
     * - NIV: requerido, max 17
     * - Si hay placas: estado_placas requerido
     * - Si no hay placas: estado_placas puede ir null
     */
    private function rules(?int $vehiculoId): array
    {
        // Ajusta/añade reglas según tus columnas reales.
        return [
            // NIV / VIN
            'niv' => ['required', 'string', 'max:17'],

            // Placas (opcional)
            'placas' => ['nullable', 'string', 'max:15'],

            // Estado placas: obligatorio si hay placas
            'estado_placas' => ['nullable', 'string', 'max:60', 'required_with:placas'],

            // Ejemplos comunes (ajusta a tu DB real)
            'marca' => ['nullable', 'string', 'max:60'],
            'submarca' => ['nullable', 'string', 'max:60'],
            'modelo' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:60'],
            'tipo' => ['nullable', 'string', 'max:60'],
        ];
    }

    /**
     * Mensajes claros (lo que verá Flutter).
     */
    private function messages(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'string' => 'Este campo debe ser texto.',
            'integer' => 'Este campo debe ser un número entero.',
            'max' => 'Este campo no debe exceder :max caracteres.',
            'min' => 'Este campo debe ser al menos :min.',
            'required_with' => 'Este campo es obligatorio cuando se captura :values.',

            // Mensajes específicos
            'niv.required' => 'El NIV es obligatorio.',
            'niv.max' => 'El NIV no debe superar 17 caracteres.',
            'estado_placas.required_with' => 'Si capturas placas, también debes capturar el estado de placas.',
        ];
    }

    /**
     * Nombres “humanos” de campos (para que no salga "estado_placas" feo).
     */
    private function attributes(): array
    {
        return [
            'niv' => 'NIV',
            'placas' => 'placas',
            'estado_placas' => 'estado de placas',
            'marca' => 'marca',
            'submarca' => 'submarca',
            'modelo' => 'modelo',
            'color' => 'color',
            'tipo' => 'tipo',
        ];
    }

    /**
     * Limpia strings (trim) y normaliza vacíos a null.
     * Esto ayuda MUCHO a que required_with funcione bien.
     */
    private function sanitize(array $data): array
    {
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $v = trim($v);
                $data[$k] = ($v === '') ? null : $v;
            }
        }
        return $data;
    }

    /**
     * Respuesta estándar OK
     */
    private function ok(string $message, $data)
    {
        return response()->json([
            'ok' => true,
            'message' => $message,
            'data' => $data,
        ], 200);
    }

    /**
     * Respuesta estándar CREATED
     */
    private function created(string $message, $data)
    {
        return response()->json([
            'ok' => true,
            'message' => $message,
            'data' => $data,
        ], 201);
    }

    /**
     * Respuesta estándar para validación (422)
     */
    private function validationFailed(array $errors)
    {
        return response()->json([
            'ok' => false,
            'message' => 'Datos inválidos. Revisa los campos marcados.',
            'errors' => $errors,
        ], 422);
    }

    /**
     * Respuesta estándar de error
     */
    private function fail(string $message, int $status)
    {
        return response()->json([
            'ok' => false,
            'message' => $message,
        ], $status);
    }
}
