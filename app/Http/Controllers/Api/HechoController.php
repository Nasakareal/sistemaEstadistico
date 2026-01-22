<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hechos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HechoController extends Controller
{
    /**
     * GET /hechos
     * Listado (con búsqueda opcional)
     */
    public function index(Request $request)
    {
        try {
            $q = trim((string) $request->query('q', ''));

            $hechos = Hechos::query()
                ->when($q !== '', function ($query) use ($q) {
                    $qq = $this->normalizeString($q);

                    $query->where(function ($w) use ($qq) {
                        $w->where('folio_c5i', 'like', "%{$qq}%")
                          ->orWhere('perito', 'like', "%{$qq}%")
                          ->orWhere('unidad', 'like', "%{$qq}%")
                          ->orWhere('sector', 'like', "%{$qq}%")
                          ->orWhere('municipio', 'like', "%{$qq}%")
                          ->orWhere('tipo_hecho', 'like', "%{$qq}%")
                          ->orWhere('situacion', 'like', "%{$qq}%");
                    });
                })
                ->orderByDesc('id')
                ->paginate(20);

            return $this->ok('Listado de hechos.', $hechos);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al cargar los hechos.', 500);
        }
    }

    /**
     * POST /hechos
     * Crear hecho
     */
    public function store(Request $request)
    {
        try {
            $data = $this->sanitize($request->all());

            $validator = Validator::make(
                $data,
                $this->rulesStore(),
                $this->messages(),
                $this->attributes()
            );

            if ($validator->fails()) {
                return $this->validationFailed($validator->errors()->toArray());
            }

            // Normaliza strings como en tu versión web
            $data = $this->normalizePayloadStrings($validator->validated());

            // Boolean checkbox
            $data['checaron_antecedentes'] = (bool) ($request->input('checaron_antecedentes') ?? false);

            // Auditoría
            $user = $request->user();
            if ($user) {
                $data['created_by'] = $user->id;
            }

            $hecho = Hechos::create($data);

            return $this->created('Hecho creado exitosamente.', $hecho);
        } catch (QueryException $e) {
            return $this->fail('No se pudo guardar el hecho. Verifica los datos e intenta de nuevo.', 500);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error inesperado al registrar el hecho.', 500);
        }
    }

    /**
     * GET /hechos/{hecho}
     * Ver 1 hecho
     */
    public function show($hecho)
    {
        try {
            $h = Hechos::with(['vehiculos', 'lesionados'])->findOrFail($hecho);
            return $this->ok('Hecho encontrado.', $h);
        } catch (ModelNotFoundException $e) {
            return $this->fail('No se encontró el hecho solicitado.', 404);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al consultar el hecho.', 500);
        }
    }

    /**
     * PUT /hechos/{hecho}
     * Actualizar hecho
     */
    public function update(Request $request, $hecho)
    {
        try {
            $h = Hechos::findOrFail($hecho);

            $data = $this->sanitize($request->all());

            $validator = Validator::make(
                $data,
                $this->rulesUpdate($h->id),
                $this->messages(),
                $this->attributes()
            );

            if ($validator->fails()) {
                return $this->validationFailed($validator->errors()->toArray());
            }

            $data = $this->normalizePayloadStrings($validator->validated());

            // Boolean checkbox
            $data['checaron_antecedentes'] = (bool) ($request->input('checaron_antecedentes') ?? false);

            // Auditoría
            $user = $request->user();
            if ($user) {
                $data['updated_by'] = $user->id;
            }

            $h->update($data);

            return $this->ok('Hecho actualizado exitosamente.', $h->fresh());
        } catch (ModelNotFoundException $e) {
            return $this->fail('No se encontró el hecho a actualizar.', 404);
        } catch (QueryException $e) {
            return $this->fail('No se pudo actualizar el hecho. Verifica los datos e intenta de nuevo.', 500);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error inesperado al actualizar el hecho.', 500);
        }
    }

    /**
     * DELETE /hechos/{hecho}
     * Eliminar hecho
     */
    public function destroy(Request $request, $hecho)
    {
        try {
            $h = Hechos::findOrFail($hecho);

            // Si quieres lógica extra de roles, hazlo aquí.
            // Ahorita lo controla tu middleware can:eliminar hechos

            $h->delete();

            return $this->ok('Hecho eliminado exitosamente.', null);
        } catch (ModelNotFoundException $e) {
            return $this->fail('No se encontró el hecho a eliminar.', 404);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al eliminar el hecho.', 500);
        }
    }

    /**
     * POST /hechos/{hecho}/descargo
     * Subir descargo (archivo)
     * Campo esperado: descargo (file)
     */
    public function subirDescargo(Request $request, $hecho)
    {
        try {
            $h = Hechos::findOrFail($hecho);

            $validator = Validator::make(
                $request->all(),
                [
                    'descargo' => ['required', 'file', 'max:10240'], // 10MB
                ],
                [
                    'descargo.required' => 'Debes adjuntar el archivo de descargo.',
                    'descargo.file' => 'El descargo debe ser un archivo.',
                    'descargo.max' => 'El archivo excede el tamaño máximo permitido.',
                ]
            );

            if ($validator->fails()) {
                return $this->validationFailed($validator->errors()->toArray());
            }

            $file = $request->file('descargo');

            // Guarda en storage/app/public/hechos/descargos
            $path = $file->store('hechos/descargos', 'public');

            // Si ya tenía descargo, bórralo
            if (!empty($h->descargo)) {
                try {
                    Storage::disk('public')->delete($h->descargo);
                } catch (Throwable $e) {
                    // no truenes por esto
                }
            }

            $h->descargo = $path;

            $user = $request->user();
            if ($user) {
                $h->updated_by = $user->id;
            }

            $h->save();

            return $this->ok('Descargo subido correctamente.', [
                'descargo' => $path,
                'descargo_url' => Storage::disk('public')->url($path),
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->fail('No se encontró el hecho para subir el descargo.', 404);
        } catch (Throwable $e) {
            return $this->fail('Ocurrió un error al subir el descargo.', 500);
        }
    }

    /* =========================
       VALIDACIONES
    ========================== */

    private function rulesStore(): array
    {
        return [
            'folio_c5i' => ['required', 'string', 'max:20', 'unique:hechos,folio_c5i'],
            'perito' => ['required', 'string', 'max:255'],
            'autorizacion_practico' => ['nullable', 'string', 'max:255'],
            'unidad' => ['required', 'string', 'max:50'],
            'hora' => ['required', 'date_format:H:i'],
            'fecha' => ['required', 'date'],
            'sector' => ['required', 'string', 'in:REVOLUCIÓN,NUEVA ESPAÑA,INDEPENDENCIA,REPÚBLICA,CENTRO'],
            'calle' => ['required', 'string', 'max:255'],
            'colonia' => ['required', 'string', 'max:255'],
            'entre_calles' => ['nullable', 'string', 'max:255'],
            'municipio' => ['required', 'string', 'max:100'],
            'tipo_hecho' => ['required', 'string', 'max:255'],
            'superficie_via' => ['required', 'string', 'max:50'],
            'tiempo' => ['required', 'string', 'in:Día,Noche,Amanecer,Atardecer'],
            'clima' => ['required', 'string', 'in:Bueno,Malo,Nublado,Lluvioso'],
            'condiciones' => ['required', 'string', 'in:Bueno,Regular,Malo'],
            'control_transito' => ['required', 'string', 'max:50'],
            'checaron_antecedentes' => ['nullable', 'boolean'],
            'causas' => ['required', 'string', 'max:255'],
            'colision_camino' => ['required', 'string', 'max:255'],
            'situacion' => ['required', 'string', 'in:RESUELTO,PENDIENTE,TURNADO,REPORTE'],
            'oficio_mp' => ['nullable', 'string', 'max:255', 'required_if:situacion,TURNADO'],
            'vehiculos_mp' => ['required', 'integer', 'min:0'],
            'personas_mp' => ['required', 'integer', 'min:0'],
        ];
    }

    private function rulesUpdate(int $hechoId): array
    {
        return [
            'folio_c5i' => [
                'required',
                'string',
                'max:20',
                Rule::unique('hechos', 'folio_c5i')->ignore($hechoId),
            ],
            'perito' => ['required', 'string', 'max:255'],
            'autorizacion_practico' => ['nullable', 'string', 'max:255'],
            'unidad' => ['required', 'string', 'max:50'],
            'hora' => ['required', 'date_format:H:i'],
            'fecha' => ['required', 'date'],
            'sector' => ['required', 'string', 'in:REVOLUCIÓN,NUEVA ESPAÑA,INDEPENDENCIA,REPÚBLICA,CENTRO'],
            'calle' => ['required', 'string', 'max:255'],
            'colonia' => ['required', 'string', 'max:255'],
            'entre_calles' => ['nullable', 'string', 'max:255'],
            'municipio' => ['required', 'string', 'max:100'],
            'tipo_hecho' => ['required', 'string', 'max:255'],
            'superficie_via' => ['required', 'string', 'max:50'],
            'tiempo' => ['required', 'string', 'in:Día,Noche,Amanecer,Atardecer'],
            'clima' => ['required', 'string', 'in:Bueno,Malo,Nublado,Lluvioso'],
            'condiciones' => ['required', 'string', 'in:Bueno,Regular,Malo'],
            'control_transito' => ['required', 'string', 'max:50'],
            'checaron_antecedentes' => ['nullable', 'boolean'],
            'causas' => ['required', 'string', 'max:255'],
            'colision_camino' => ['required', 'string', 'max:255'],
            'situacion' => ['required', 'string', 'in:RESUELTO,PENDIENTE,TURNADO,REPORTE'],
            'oficio_mp' => ['nullable', 'string', 'max:255', 'required_if:situacion,TURNADO'],
            'vehiculos_mp' => ['required', 'integer', 'min:0'],
            'personas_mp' => ['required', 'integer', 'min:0'],
        ];
    }

    private function messages(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'string' => 'Este campo debe ser texto.',
            'integer' => 'Este campo debe ser un número entero.',
            'max' => 'Este campo no debe exceder :max caracteres.',
            'min' => 'Este campo debe ser al menos :min.',
            'unique' => 'Este valor ya existe.',
            'in' => 'Valor inválido.',
            'date' => 'Fecha inválida.',
            'date_format' => 'Formato inválido.',
            'required_if' => 'Este campo es obligatorio en esta condición.',

            'folio_c5i.required' => 'El folio C5i es obligatorio.',
            'folio_c5i.unique' => 'Ese folio C5i ya existe.',
            'perito.required' => 'El perito es obligatorio.',
            'oficio_mp.required_if' => 'Si la situación es TURNADO, el oficio MP es obligatorio.',
        ];
    }

    private function attributes(): array
    {
        return [
            'folio_c5i' => 'folio C5i',
            'perito' => 'perito',
            'autorizacion_practico' => 'autorización práctico',
            'unidad' => 'unidad',
            'hora' => 'hora',
            'fecha' => 'fecha',
            'sector' => 'sector',
            'calle' => 'calle',
            'colonia' => 'colonia',
            'entre_calles' => 'entre calles',
            'municipio' => 'municipio',
            'tipo_hecho' => 'tipo de hecho',
            'superficie_via' => 'superficie de la vía',
            'tiempo' => 'tiempo',
            'clima' => 'clima',
            'condiciones' => 'condiciones',
            'control_transito' => 'control de tránsito',
            'checaron_antecedentes' => 'checaron antecedentes',
            'causas' => 'causas',
            'colision_camino' => 'colisión/camino',
            'situacion' => 'situación',
            'oficio_mp' => 'oficio MP',
            'vehiculos_mp' => 'vehículos MP',
            'personas_mp' => 'personas MP',
        ];
    }

    /* =========================
       HELPERS
    ========================== */

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

    private function normalizePayloadStrings(array $data): array
    {
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $data[$k] = $this->normalizeString($v);
            }
        }
        return $data;
    }

    private function normalizeString(string $value): string
    {
        return strtoupper($this->removeAccents($value));
    }

    private function removeAccents(string $string): string
    {
        $unwanted_array = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
            'Â' => 'A', 'Ê' => 'E', 'Î' => 'I', 'Ô' => 'O', 'Û' => 'U',
            'Ä' => 'A', 'Ë' => 'E', 'Ï' => 'I', 'Ö' => 'O', 'Ü' => 'U',
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
            'à' => 'A', 'è' => 'E', 'ì' => 'I', 'ò' => 'I', 'ù' => 'U',
            'â' => 'A', 'ê' => 'E', 'î' => 'I', 'ô' => 'O', 'û' => 'U',
            'ä' => 'A', 'ë' => 'E', 'ï' => 'I', 'ö' => 'O', 'ü' => 'U',
            'Ñ' => 'N', 'ñ' => 'N',
            'Ç' => 'C', 'ç' => 'C'
        ];
        return strtr($string, $unwanted_array);
    }

    /* =========================
       RESPUESTAS JSON
    ========================== */

    private function ok(string $message, $data)
    {
        return response()->json([
            'ok' => true,
            'message' => $message,
            'data' => $data,
        ], 200);
    }

    private function created(string $message, $data)
    {
        return response()->json([
            'ok' => true,
            'message' => $message,
            'data' => $data,
        ], 201);
    }

    private function validationFailed(array $errors)
    {
        return response()->json([
            'ok' => false,
            'message' => 'Datos inválidos. Revisa los campos marcados.',
            'errors' => $errors,
        ], 422);
    }

    private function fail(string $message, int $status)
    {
        return response()->json([
            'ok' => false,
            'message' => $message,
        ], $status);
    }
}
