<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\BitacoraServicioPatrulla;
use App\Models\EntregaRecepcionPatrulla;
use App\Models\Hechos;
use App\Models\Patrulla;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class PatrullaServicioController extends Controller
{
    private const ESTADOS_GENERALES = [
        'bueno',
        'regular',
        'malo',
    ];

    private const ESTADOS_EQUIPO = [
        'bueno',
        'regular',
        'malo',
        'no_aplica',
    ];

    public function disponibles(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        if (!$user->unidad_id && !$user->hasRole('Superadmin')) {
            return response()->json([
                'message' => 'Tu usuario no tiene una unidad asignada.',
                'data' => [],
            ], 200);
        }

        $query = Patrulla::query()
            ->with([
                'unidad',
                'turno',
            ])
            ->where('activa', true)
            ->whereDoesntHave('bitacorasServicio', function ($q) {
                $q->where('estatus', 'abierta');
            });

        $this->aplicarVisibilidadPatrullas($query, $user);

        $patrullas = $query
            ->orderBy('numero_economico')
            ->get()
            ->map(fn (Patrulla $patrulla) => $this->patrullaPayload($patrulla))
            ->values();

        return response()->json([
            'data' => $patrullas,
        ], 200);
    }

    public function miServicio(Request $request)
    {
        $user = $request->user();

        $bitacora = $this->bitacoraAbiertaUsuario($user);

        if (!$bitacora) {
            return response()->json([
                'tiene_servicio' => false,
                'data' => null,
            ], 200);
        }

        $bitacora->load([
            'patrulla.unidad',
            'patrulla.turno',
            'turno',
            'capturadoPor',
        ]);

        $entregaRecepcion = EntregaRecepcionPatrulla::query()
            ->where('patrulla_id', $bitacora->patrulla_id)
            ->where('recibe_user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'tiene_servicio' => true,
            'data' => [
                'patrulla' => $this->patrullaPayload($bitacora->patrulla),
                'bitacora' => $this->bitacoraPayload($bitacora),
                'entrega_recepcion' => $entregaRecepcion
                    ? $this->entregaRecepcionPayload($entregaRecepcion)
                    : null,
            ],
        ], 200);
    }

    public function miBitacora(Request $request)
    {
        $user = $request->user();

        $bitacora = $this->bitacoraAbiertaUsuario($user);

        if (!$bitacora) {
            return response()->json([
                'message' => 'No tienes una bitácora de servicio abierta.',
                'data' => null,
            ], 404);
        }

        $bitacora->load([
            'patrulla.unidad',
            'patrulla.turno',
            'turno',
            'capturadoPor',
        ]);

        [$inicio, $fin] = $this->rangoBitacora($bitacora);

        $actividades = $this->actividadesDeBitacora(
            $bitacora,
            $inicio,
            $fin
        );

        $hechos = $this->hechosDeBitacora(
            $bitacora,
            $inicio,
            $fin
        );

        return response()->json([
            'data' => [
                'patrulla' => $this->patrullaPayload($bitacora->patrulla),
                'bitacora' => $this->bitacoraPayload($bitacora),
                'periodo' => [
                    'inicio' => $inicio->toIso8601String(),
                    'fin' => $fin->toIso8601String(),
                ],
                'totales' => [
                    'servicios' => $actividades->count() + $hechos->count(),
                    'actividades' => $actividades->count(),
                    'hechos' => $hechos->count(),
                ],
                'actividades' => $actividades,
                'hechos' => $hechos,
            ],
        ], 200);
    }

    public function miHistorial(Request $request)
    {
        $user = $request->user();

        $perPage = (int) $request->query('per_page', 20);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $bitacoras = BitacoraServicioPatrulla::query()
            ->where('capturado_por_user_id', $user->id)
            ->with([
                'patrulla.unidad',
                'patrulla.turno',
                'turno',
            ])
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->orderByDesc('id')
            ->paginate($perPage);

        $bitacoras->setCollection(
            $bitacoras->getCollection()
                ->map(fn (BitacoraServicioPatrulla $bitacora) => [
                    'patrulla' => $this->patrullaPayload($bitacora->patrulla),
                    'bitacora' => $this->bitacoraPayload($bitacora),
                ])
        );

        return response()->json($bitacoras, 200);
    }

    public function recibir(Request $request, Patrulla $patrulla)
    {
        $user = $request->user();

        if (!$this->usuarioPuedeUsarPatrulla($user, $patrulla)) {
            return response()->json([
                'message' => 'No puedes recibir una patrulla de otra unidad.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'kilometraje' => 'required|integer|min:0',
            'nivel_combustible' => 'required|numeric|min:0|max:100',

            'estado_carroceria' => [
                'required',
                Rule::in(self::ESTADOS_GENERALES),
            ],

            'estado_interiores' => [
                'required',
                Rule::in(self::ESTADOS_GENERALES),
            ],

            'estado_llantas' => [
                'required',
                Rule::in(self::ESTADOS_GENERALES),
            ],

            'estado_luces' => [
                'required',
                Rule::in(self::ESTADOS_GENERALES),
            ],

            'estado_torreta' => [
                'required',
                Rule::in(self::ESTADOS_EQUIPO),
            ],

            'estado_sirena' => [
                'required',
                Rule::in(self::ESTADOS_EQUIPO),
            ],

            'estado_radio' => [
                'required',
                Rule::in(self::ESTADOS_EQUIPO),
            ],

            'estado_mecanico' => [
                'required',
                Rule::in(self::ESTADOS_GENERALES),
            ],

            'trae_refaccion' => 'nullable|boolean',
            'trae_gato' => 'nullable|boolean',
            'trae_llave_cruz' => 'nullable|boolean',
            'trae_extintor' => 'nullable|boolean',
            'trae_botiquin' => 'nullable|boolean',

            'equipo_adicional' => 'nullable|string|max:5000',
            'danos_existentes' => 'nullable|string|max:5000',
            'novedades' => 'nullable|string|max:5000',
            'observaciones' => 'nullable|string|max:5000',

            'foto_frontal' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'foto_trasera' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'foto_lateral_izquierdo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'foto_lateral_derecho' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'foto_tablero' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse(
                $validator->errors()->toArray()
            );
        }

        $validated = $validator->validated();

        $fotos = [];

        try {
            $fotos = $this->guardarFotosEntregaRecepcion($request);

            $resultado = DB::transaction(function () use (
                $user,
                $patrulla,
                $validated,
                $fotos
            ) {
                $usuario = User::query()
                    ->lockForUpdate()
                    ->find($user->id);

                $unidad = Patrulla::query()
                    ->lockForUpdate()
                    ->find($patrulla->id);

                if (!$usuario || !$unidad) {
                    return [
                        'error' => true,
                        'status' => 404,
                        'message' => 'Usuario o patrulla no encontrados.',
                    ];
                }

                if (!$unidad->activa) {
                    return [
                        'error' => true,
                        'status' => 409,
                        'message' => 'La patrulla está inactiva.',
                    ];
                }

                if (!$this->usuarioPuedeUsarPatrulla($usuario, $unidad)) {
                    return [
                        'error' => true,
                        'status' => 403,
                        'message' => 'La patrulla no pertenece a tu unidad.',
                    ];
                }

                $bitacoraUsuario = BitacoraServicioPatrulla::query()
                    ->where('capturado_por_user_id', $usuario->id)
                    ->where('estatus', 'abierta')
                    ->lockForUpdate()
                    ->first();

                if ($bitacoraUsuario) {
                    return [
                        'error' => true,
                        'status' => 409,
                        'message' => 'Ya tienes una patrulla en servicio.',
                    ];
                }

                $bitacoraPatrulla = BitacoraServicioPatrulla::query()
                    ->where('patrulla_id', $unidad->id)
                    ->where('estatus', 'abierta')
                    ->lockForUpdate()
                    ->first();

                if ($bitacoraPatrulla) {
                    return [
                        'error' => true,
                        'status' => 409,
                        'message' => 'La patrulla ya se encuentra en servicio con otro elemento.',
                    ];
                }

                $ahora = now('America/Mexico_City');

                $pendiente = EntregaRecepcionPatrulla::query()
                    ->where('patrulla_id', $unidad->id)
                    ->whereNull('recibe_user_id')
                    ->where('aceptada_recepcion', false)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if ($pendiente) {
                    $pendiente->update(array_merge([
                        'fecha' => $ahora->toDateString(),
                        'hora_real' => $ahora->format('H:i:s'),

                        'recibe_user_id' => $usuario->id,
                        'recibe_nombre' => $usuario->nombre_completo,

                        'kilometraje' => (int) $validated['kilometraje'],
                        'nivel_combustible' => $validated['nivel_combustible'],

                        'estado_carroceria' => $validated['estado_carroceria'],
                        'estado_interiores' => $validated['estado_interiores'],
                        'estado_llantas' => $validated['estado_llantas'],
                        'estado_luces' => $validated['estado_luces'],
                        'estado_torreta' => $validated['estado_torreta'],
                        'estado_sirena' => $validated['estado_sirena'],
                        'estado_radio' => $validated['estado_radio'],
                        'estado_mecanico' => $validated['estado_mecanico'],

                        'trae_refaccion' => $validated['trae_refaccion'] ?? null,
                        'trae_gato' => $validated['trae_gato'] ?? null,
                        'trae_llave_cruz' => $validated['trae_llave_cruz'] ?? null,
                        'trae_extintor' => $validated['trae_extintor'] ?? null,
                        'trae_botiquin' => $validated['trae_botiquin'] ?? null,

                        'equipo_adicional' => $validated['equipo_adicional'] ?? null,
                        'danos_existentes' => $validated['danos_existentes'] ?? null,
                        'novedades' => $validated['novedades'] ?? null,
                        'observaciones' => $validated['observaciones'] ?? null,

                        'aceptada_recepcion' => true,
                        'recepcion_confirmada_at' => $ahora,
                    ], $fotos));

                    $entregaRecepcion = $pendiente->fresh();
                } else {
                    $ultimaBitacora = BitacoraServicioPatrulla::query()
                        ->where('patrulla_id', $unidad->id)
                        ->where('estatus', 'cerrada')
                        ->orderByDesc('cerrada_at')
                        ->orderByDesc('id')
                        ->first();

                    $entregaUserId = $ultimaBitacora
                        ? $ultimaBitacora->capturado_por_user_id
                        : null;

                    $entregaNombre = $ultimaBitacora
                        ? $ultimaBitacora->capturado_por_nombre
                        : null;

                    if (!$entregaNombre) {
                        $entregaNombre = $unidad->resguardo_nombre
                            ?: 'SIN ENTREGA PREVIA REGISTRADA';
                    }

                    $entregaRecepcion = EntregaRecepcionPatrulla::create(
                        array_merge([
                            'patrulla_id' => $unidad->id,

                            'fecha' => $ahora->toDateString(),
                            'hora_programada' => '07:00:00',
                            'hora_real' => $ahora->format('H:i:s'),

                            'entrega_user_id' => $entregaUserId,
                            'entrega_nombre' => $entregaNombre,

                            'recibe_user_id' => $usuario->id,
                            'recibe_nombre' => $usuario->nombre_completo,

                            'kilometraje' => (int) $validated['kilometraje'],
                            'nivel_combustible' => $validated['nivel_combustible'],

                            'estado_carroceria' => $validated['estado_carroceria'],
                            'estado_interiores' => $validated['estado_interiores'],
                            'estado_llantas' => $validated['estado_llantas'],
                            'estado_luces' => $validated['estado_luces'],
                            'estado_torreta' => $validated['estado_torreta'],
                            'estado_sirena' => $validated['estado_sirena'],
                            'estado_radio' => $validated['estado_radio'],
                            'estado_mecanico' => $validated['estado_mecanico'],

                            'trae_refaccion' => $validated['trae_refaccion'] ?? null,
                            'trae_gato' => $validated['trae_gato'] ?? null,
                            'trae_llave_cruz' => $validated['trae_llave_cruz'] ?? null,
                            'trae_extintor' => $validated['trae_extintor'] ?? null,
                            'trae_botiquin' => $validated['trae_botiquin'] ?? null,

                            'equipo_adicional' => $validated['equipo_adicional'] ?? null,
                            'danos_existentes' => $validated['danos_existentes'] ?? null,
                            'novedades' => $validated['novedades'] ?? null,
                            'observaciones' => $validated['observaciones'] ?? null,

                            'aceptada_entrega' => $ultimaBitacora !== null,
                            'aceptada_recepcion' => true,

                            'entrega_confirmada_at' => $ultimaBitacora
                                ? $ultimaBitacora->cerrada_at
                                : null,

                            'recepcion_confirmada_at' => $ahora,
                        ], $fotos)
                    );
                }

                $bitacora = BitacoraServicioPatrulla::create([
                    'patrulla_id' => $unidad->id,
                    'turno_id' => $usuario->turno_id ?: $unidad->turno_id,

                    'fecha' => $ahora->toDateString(),
                    'hora_inicio' => $ahora->format('H:i:s'),
                    'hora_fin' => null,

                    'capturado_por_user_id' => $usuario->id,
                    'capturado_por_nombre' => $usuario->nombre_completo,

                    'kilometraje_inicio' => (int) $validated['kilometraje'],
                    'kilometraje_fin' => null,

                    'combustible_inicio' => $validated['nivel_combustible'],
                    'combustible_fin' => null,

                    'observaciones' => $validated['observaciones'] ?? null,

                    'estatus' => 'abierta',
                    'cerrada_at' => null,
                ]);

                $usuario->patrulla_id = $unidad->id;
                $usuario->save();

                return [
                    'error' => false,
                    'bitacora_id' => $bitacora->id,
                    'entrega_recepcion_id' => $entregaRecepcion->id,
                ];
            });

            if ($resultado['error']) {
                $this->eliminarFotos($fotos);

                return response()->json([
                    'message' => $resultado['message'],
                ], $resultado['status']);
            }

            $bitacora = BitacoraServicioPatrulla::query()
                ->with([
                    'patrulla.unidad',
                    'patrulla.turno',
                    'turno',
                    'capturadoPor',
                ])
                ->findOrFail($resultado['bitacora_id']);

            $entregaRecepcion = EntregaRecepcionPatrulla::query()
                ->findOrFail($resultado['entrega_recepcion_id']);

            return response()->json([
                'message' => 'Patrulla recibida correctamente.',
                'data' => [
                    'patrulla' => $this->patrullaPayload($bitacora->patrulla),
                    'bitacora' => $this->bitacoraPayload($bitacora),
                    'entrega_recepcion' => $this->entregaRecepcionPayload(
                        $entregaRecepcion
                    ),
                ],
            ], 201);
        } catch (Throwable $e) {
            $this->eliminarFotos($fotos);

            return response()->json([
                'message' => 'No fue posible recibir la patrulla.',
                'errors' => [
                    'patrulla' => [$e->getMessage()],
                ],
            ], 422);
        }
    }

    public function entregar(Request $request, Patrulla $patrulla)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'kilometraje' => 'required|integer|min:0',
            'nivel_combustible' => 'required|numeric|min:0|max:100',
            'novedades' => 'nullable|string|max:5000',
            'observaciones' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse(
                $validator->errors()->toArray()
            );
        }

        $validated = $validator->validated();

        try {
            $resultado = DB::transaction(function () use (
                $user,
                $patrulla,
                $validated
            ) {
                $usuario = User::query()
                    ->lockForUpdate()
                    ->find($user->id);

                $unidad = Patrulla::query()
                    ->lockForUpdate()
                    ->find($patrulla->id);

                if (!$usuario || !$unidad) {
                    return [
                        'error' => true,
                        'status' => 404,
                        'message' => 'Usuario o patrulla no encontrados.',
                    ];
                }

                $bitacora = BitacoraServicioPatrulla::query()
                    ->where('patrulla_id', $unidad->id)
                    ->where('capturado_por_user_id', $usuario->id)
                    ->where('estatus', 'abierta')
                    ->lockForUpdate()
                    ->first();

                if (!$bitacora) {
                    return [
                        'error' => true,
                        'status' => 409,
                        'message' => 'No tienes un servicio abierto con esta patrulla.',
                    ];
                }

                if (
                    $bitacora->kilometraje_inicio !== null
                    && (int) $validated['kilometraje']
                        < (int) $bitacora->kilometraje_inicio
                ) {
                    return [
                        'error' => true,
                        'status' => 422,
                        'message' => 'El kilometraje final no puede ser menor al kilometraje inicial.',
                    ];
                }

                $ahora = now('America/Mexico_City');

                $observaciones = trim(
                    (string) ($validated['observaciones'] ?? '')
                );

                $bitacora->update([
                    'hora_fin' => $ahora->format('H:i:s'),
                    'kilometraje_fin' => (int) $validated['kilometraje'],
                    'combustible_fin' => $validated['nivel_combustible'],
                    'observaciones' => $observaciones !== ''
                        ? $observaciones
                        : $bitacora->observaciones,
                    'estatus' => 'cerrada',
                    'cerrada_at' => $ahora,
                ]);

                $pendienteExistente = EntregaRecepcionPatrulla::query()
                    ->where('patrulla_id', $unidad->id)
                    ->where('entrega_user_id', $usuario->id)
                    ->whereNull('recibe_user_id')
                    ->where('aceptada_recepcion', false)
                    ->lockForUpdate()
                    ->first();

                if ($pendienteExistente) {
                    $entregaRecepcion = $pendienteExistente;

                    $entregaRecepcion->update([
                        'fecha' => $ahora->toDateString(),
                        'hora_real' => $ahora->format('H:i:s'),
                        'kilometraje' => (int) $validated['kilometraje'],
                        'nivel_combustible' => $validated['nivel_combustible'],
                        'novedades' => $validated['novedades'] ?? null,
                        'observaciones' => $validated['observaciones'] ?? null,
                        'aceptada_entrega' => true,
                        'entrega_confirmada_at' => $ahora,
                    ]);
                } else {
                    $entregaRecepcion = EntregaRecepcionPatrulla::create([
                        'patrulla_id' => $unidad->id,

                        'fecha' => $ahora->toDateString(),
                        'hora_programada' => '07:00:00',
                        'hora_real' => $ahora->format('H:i:s'),

                        'entrega_user_id' => $usuario->id,
                        'entrega_nombre' => $usuario->nombre_completo,

                        'recibe_user_id' => null,
                        'recibe_nombre' => 'PENDIENTE DE RECEPCION',

                        'kilometraje' => (int) $validated['kilometraje'],
                        'nivel_combustible' => $validated['nivel_combustible'],

                        'estado_carroceria' => null,
                        'estado_interiores' => null,
                        'estado_llantas' => null,
                        'estado_luces' => null,
                        'estado_torreta' => null,
                        'estado_sirena' => null,
                        'estado_radio' => null,
                        'estado_mecanico' => null,

                        'trae_refaccion' => null,
                        'trae_gato' => null,
                        'trae_llave_cruz' => null,
                        'trae_extintor' => null,
                        'trae_botiquin' => null,

                        'equipo_adicional' => null,
                        'danos_existentes' => null,
                        'novedades' => $validated['novedades'] ?? null,
                        'observaciones' => $validated['observaciones'] ?? null,

                        'aceptada_entrega' => true,
                        'aceptada_recepcion' => false,

                        'entrega_confirmada_at' => $ahora,
                        'recepcion_confirmada_at' => null,
                    ]);
                }

                if ((int) $usuario->patrulla_id === (int) $unidad->id) {
                    $usuario->patrulla_id = null;
                    $usuario->save();
                }

                return [
                    'error' => false,
                    'bitacora_id' => $bitacora->id,
                    'entrega_recepcion_id' => $entregaRecepcion->id,
                ];
            });

            if ($resultado['error']) {
                return response()->json([
                    'message' => $resultado['message'],
                ], $resultado['status']);
            }

            $bitacora = BitacoraServicioPatrulla::query()
                ->with([
                    'patrulla.unidad',
                    'patrulla.turno',
                    'turno',
                    'capturadoPor',
                ])
                ->findOrFail($resultado['bitacora_id']);

            $entregaRecepcion = EntregaRecepcionPatrulla::query()
                ->findOrFail($resultado['entrega_recepcion_id']);

            return response()->json([
                'message' => 'Patrulla entregada correctamente.',
                'data' => [
                    'patrulla' => $this->patrullaPayload($bitacora->patrulla),
                    'bitacora' => $this->bitacoraPayload($bitacora),
                    'entrega_recepcion' => $this->entregaRecepcionPayload(
                        $entregaRecepcion
                    ),
                ],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'No fue posible entregar la patrulla.',
                'errors' => [
                    'patrulla' => [$e->getMessage()],
                ],
            ], 422);
        }
    }

    public function actualizarServicio(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'kilometraje' => 'sometimes|nullable|integer|min:0',
            'nivel_combustible' => 'sometimes|nullable|numeric|min:0|max:100',
            'observaciones' => 'sometimes|nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse(
                $validator->errors()->toArray()
            );
        }

        if (
            !$request->has('kilometraje')
            && !$request->has('nivel_combustible')
            && !$request->has('observaciones')
        ) {
            return response()->json([
                'message' => 'No se enviaron datos para actualizar.',
            ], 422);
        }

        try {
            $resultado = DB::transaction(function () use ($user, $request) {
                $bitacora = BitacoraServicioPatrulla::query()
                    ->where('capturado_por_user_id', $user->id)
                    ->where('estatus', 'abierta')
                    ->lockForUpdate()
                    ->first();

                if (!$bitacora) {
                    return [
                        'error' => true,
                        'status' => 404,
                        'message' => 'No tienes una bitácora abierta.',
                    ];
                }

                $cambios = [];

                if ($request->has('kilometraje')) {
                    $kilometraje = $request->input('kilometraje');

                    if ($kilometraje !== null) {
                        $kilometraje = (int) $kilometraje;

                        if (
                            $bitacora->kilometraje_inicio !== null
                            && $kilometraje < (int) $bitacora->kilometraje_inicio
                        ) {
                            return [
                                'error' => true,
                                'status' => 422,
                                'message' => 'El kilometraje no puede ser menor al kilometraje inicial.',
                            ];
                        }

                        $cambios['kilometraje_fin'] = $kilometraje;
                    }
                }

                if ($request->has('nivel_combustible')) {
                    $cambios['combustible_fin'] =
                        $request->input('nivel_combustible');
                }

                if ($request->has('observaciones')) {
                    $cambios['observaciones'] =
                        $request->input('observaciones');
                }

                if (!empty($cambios)) {
                    $bitacora->update($cambios);
                }

                return [
                    'error' => false,
                    'bitacora_id' => $bitacora->id,
                ];
            });

            if ($resultado['error']) {
                return response()->json([
                    'message' => $resultado['message'],
                ], $resultado['status']);
            }

            $bitacora = BitacoraServicioPatrulla::query()
                ->with([
                    'patrulla.unidad',
                    'patrulla.turno',
                    'turno',
                    'capturadoPor',
                ])
                ->findOrFail($resultado['bitacora_id']);

            return response()->json([
                'message' => 'Servicio actualizado correctamente.',
                'data' => $this->bitacoraPayload($bitacora),
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'No fue posible actualizar el servicio.',
                'errors' => [
                    'servicio' => [$e->getMessage()],
                ],
            ], 422);
        }
    }

    public function registrarKilometraje(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'kilometraje' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse(
                $validator->errors()->toArray()
            );
        }

        $kilometraje = (int) $validator->validated()['kilometraje'];

        try {
            $resultado = DB::transaction(function () use (
                $user,
                $kilometraje
            ) {
                $bitacora = BitacoraServicioPatrulla::query()
                    ->where('capturado_por_user_id', $user->id)
                    ->where('estatus', 'abierta')
                    ->lockForUpdate()
                    ->first();

                if (!$bitacora) {
                    return [
                        'error' => true,
                        'status' => 404,
                        'message' => 'No tienes una bitácora abierta.',
                    ];
                }

                if (
                    $bitacora->kilometraje_inicio !== null
                    && $kilometraje < (int) $bitacora->kilometraje_inicio
                ) {
                    return [
                        'error' => true,
                        'status' => 422,
                        'message' => 'El kilometraje no puede ser menor al kilometraje inicial.',
                    ];
                }

                if (
                    $bitacora->kilometraje_fin !== null
                    && $kilometraje < (int) $bitacora->kilometraje_fin
                ) {
                    return [
                        'error' => true,
                        'status' => 422,
                        'message' => 'El kilometraje no puede ser menor a la última lectura registrada.',
                    ];
                }

                $bitacora->kilometraje_fin = $kilometraje;
                $bitacora->save();

                return [
                    'error' => false,
                    'bitacora_id' => $bitacora->id,
                ];
            });

            if ($resultado['error']) {
                return response()->json([
                    'message' => $resultado['message'],
                ], $resultado['status']);
            }

            $bitacora = BitacoraServicioPatrulla::query()
                ->with([
                    'patrulla.unidad',
                    'turno',
                ])
                ->findOrFail($resultado['bitacora_id']);

            return response()->json([
                'message' => 'Kilometraje registrado correctamente.',
                'data' => [
                    'patrulla_id' => $bitacora->patrulla_id,
                    'numero_economico' => $bitacora->patrulla->numero_economico ?? null,
                    'kilometraje_inicio' => $bitacora->kilometraje_inicio,
                    'kilometraje_actual' => $bitacora->kilometraje_fin,
                    'kilometros_recorridos' => $bitacora->kilometros_recorridos,
                ],
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'No fue posible registrar el kilometraje.',
                'errors' => [
                    'kilometraje' => [$e->getMessage()],
                ],
            ], 422);
        }
    }

    private function bitacoraAbiertaUsuario(User $user): ?BitacoraServicioPatrulla
    {
        return BitacoraServicioPatrulla::query()
            ->where('capturado_por_user_id', $user->id)
            ->where('estatus', 'abierta')
            ->orderByDesc('id')
            ->first();
    }

    private function aplicarVisibilidadPatrullas($query, User $user): void
    {
        if (
            $user->hasRole('Superadmin')
            || (int) ($user->unidad_id ?? 0) === 3
        ) {
            return;
        }

        $query->where('unidad_id', $user->unidad_id);
    }

    private function usuarioPuedeUsarPatrulla(
        User $user,
        Patrulla $patrulla
    ): bool {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ((int) ($user->unidad_id ?? 0) === 3) {
            return true;
        }

        return !empty($user->unidad_id)
            && (int) $user->unidad_id === (int) $patrulla->unidad_id;
    }

    private function rangoBitacora(
        BitacoraServicioPatrulla $bitacora
    ): array {
        $fecha = $bitacora->fecha instanceof \DateTimeInterface
            ? $bitacora->fecha->format('Y-m-d')
            : Carbon::parse($bitacora->fecha)->format('Y-m-d');

        $inicio = Carbon::parse(
            $fecha . ' ' . ($bitacora->hora_inicio ?: '00:00:00'),
            'America/Mexico_City'
        );

        if ($bitacora->cerrada_at) {
            $fin = Carbon::parse(
                $bitacora->cerrada_at,
                'America/Mexico_City'
            );
        } elseif ($bitacora->hora_fin) {
            $fin = Carbon::parse(
                $fecha . ' ' . $bitacora->hora_fin,
                'America/Mexico_City'
            );

            if ($fin->lt($inicio)) {
                $fin->addDay();
            }
        } else {
            $fin = now('America/Mexico_City');
        }

        return [$inicio, $fin];
    }

    private function actividadesDeBitacora(
        BitacoraServicioPatrulla $bitacora,
        Carbon $inicio,
        Carbon $fin
    ) {
        return Actividad::query()
            ->where('created_by', $bitacora->capturado_por_user_id)
            ->whereBetween('fecha', [
                $inicio->toDateString(),
                $fin->toDateString(),
            ])
            ->with([
                'categoria',
                'subcategoria',
            ])
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get()
            ->filter(function (Actividad $actividad) use ($inicio, $fin) {
                $momento = $this->momento(
                    $actividad->fecha,
                    $actividad->hora
                );

                return $momento
                    && $momento->gte($inicio)
                    && $momento->lte($fin);
            })
            ->map(function (Actividad $actividad) {
                return [
                    'id' => $actividad->id,
                    'folio_c5i' => $actividad->folio_c5i,
                    'fecha' => optional($actividad->fecha)->format('Y-m-d'),
                    'hora' => $actividad->hora,
                    'nombre' => $actividad->nombre,
                    'cantidad' => $actividad->cantidad,

                    'categoria' => $actividad->categoria
                        ? [
                            'id' => $actividad->categoria->id,
                            'nombre' => $actividad->categoria->nombre,
                        ]
                        : null,

                    'subcategoria' => $actividad->subcategoria
                        ? [
                            'id' => $actividad->subcategoria->id,
                            'nombre' => $actividad->subcategoria->nombre,
                        ]
                        : null,

                    'lugar' => $actividad->lugar,
                    'municipio' => $actividad->municipio,
                    'motivo' => $actividad->motivo,
                    'narrativa' => $actividad->narrativa,
                    'acciones_realizadas' => $actividad->acciones_realizadas,
                    'observaciones' => $actividad->observaciones,
                    'km_recorridos' => $actividad->km_recorridos,
                ];
            })
            ->values();
    }

    private function hechosDeBitacora(
        BitacoraServicioPatrulla $bitacora,
        Carbon $inicio,
        Carbon $fin
    ) {
        return Hechos::query()
            ->where('created_by', $bitacora->capturado_por_user_id)
            ->whereBetween('fecha', [
                $inicio->toDateString(),
                $fin->toDateString(),
            ])
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get()
            ->filter(function (Hechos $hecho) use ($inicio, $fin) {
                $momento = $this->momento(
                    $hecho->fecha,
                    $hecho->hora
                );

                return $momento
                    && $momento->gte($inicio)
                    && $momento->lte($fin);
            })
            ->map(function (Hechos $hecho) {
                return [
                    'id' => $hecho->id,
                    'folio_c5i' => $hecho->folio_c5i,
                    'fecha' => optional($hecho->fecha)->format('Y-m-d'),
                    'hora' => $hecho->hora,
                    'tipo_hecho' => $hecho->tipo_hecho,
                    'sector' => $hecho->sector,
                    'calle' => $hecho->calle,
                    'colonia' => $hecho->colonia,
                    'entre_calles' => $hecho->entre_calles,
                    'municipio' => $hecho->municipio,
                    'situacion' => $hecho->situacion,
                    'causas' => $hecho->causas,
                    'km_recorridos' => $hecho->km_recorridos,
                ];
            })
            ->values();
    }

    private function momento($fecha, $hora): ?Carbon
    {
        if (!$fecha || !$hora) {
            return null;
        }

        try {
            $fechaTexto = $fecha instanceof \DateTimeInterface
                ? $fecha->format('Y-m-d')
                : Carbon::parse($fecha)->format('Y-m-d');

            return Carbon::parse(
                $fechaTexto . ' ' . $hora,
                'America/Mexico_City'
            );
        } catch (Throwable $e) {
            return null;
        }
    }

    private function patrullaPayload(?Patrulla $patrulla): ?array
    {
        if (!$patrulla) {
            return null;
        }

        $patrulla->loadMissing([
            'unidad',
            'turno',
        ]);

        return [
            'id' => $patrulla->id,
            'numero_economico' => $patrulla->numero_economico,
            'activa' => (bool) $patrulla->activa,
            'tipo' => $patrulla->tipo,
            'marca' => $patrulla->marca,
            'linea' => $patrulla->linea,
            'modelo' => $patrulla->modelo,
            'placas' => $patrulla->placas,
            'serie' => $patrulla->serie,
            'color' => $patrulla->color,
            'descripcion_vehiculo' => $patrulla->descripcion_vehiculo,
            'foto_url' => $patrulla->foto_url,

            'unidad' => $patrulla->unidad
                ? [
                    'id' => $patrulla->unidad->id,
                    'nombre' => $patrulla->unidad->nombre,
                ]
                : null,

            'turno' => $patrulla->turno
                ? [
                    'id' => $patrulla->turno->id,
                    'nombre' => $patrulla->turno->nombre,
                ]
                : null,
        ];
    }

    private function bitacoraPayload(
        BitacoraServicioPatrulla $bitacora
    ): array {
        $bitacora->loadMissing([
            'turno',
            'capturadoPor',
        ]);

        return [
            'id' => $bitacora->id,
            'patrulla_id' => $bitacora->patrulla_id,
            'turno_id' => $bitacora->turno_id,

            'turno' => $bitacora->turno
                ? [
                    'id' => $bitacora->turno->id,
                    'nombre' => $bitacora->turno->nombre,
                ]
                : null,

            'fecha' => optional($bitacora->fecha)->format('Y-m-d'),
            'hora_inicio' => $bitacora->hora_inicio,
            'hora_fin' => $bitacora->hora_fin,

            'capturado_por_user_id' => $bitacora->capturado_por_user_id,
            'capturado_por_nombre' => $bitacora->capturado_por_nombre,

            'kilometraje_inicio' => $bitacora->kilometraje_inicio,
            'kilometraje_fin' => $bitacora->kilometraje_fin,
            'kilometros_recorridos' => $bitacora->kilometros_recorridos,

            'combustible_inicio' => $bitacora->combustible_inicio,
            'combustible_fin' => $bitacora->combustible_fin,

            'observaciones' => $bitacora->observaciones,
            'estatus' => $bitacora->estatus,

            'cerrada_at' => $bitacora->cerrada_at
                ? $bitacora->cerrada_at->toIso8601String()
                : null,

            'created_at' => $bitacora->created_at
                ? $bitacora->created_at->toIso8601String()
                : null,
        ];
    }

    private function entregaRecepcionPayload(
        EntregaRecepcionPatrulla $registro
    ): array {
        return [
            'id' => $registro->id,
            'patrulla_id' => $registro->patrulla_id,

            'fecha' => optional($registro->fecha)->format('Y-m-d'),
            'hora_programada' => $registro->hora_programada,
            'hora_real' => $registro->hora_real,

            'entrega_user_id' => $registro->entrega_user_id,
            'entrega_nombre' => $registro->entrega_nombre,

            'recibe_user_id' => $registro->recibe_user_id,
            'recibe_nombre' => $registro->recibe_nombre,

            'kilometraje' => $registro->kilometraje,
            'nivel_combustible' => $registro->nivel_combustible,

            'estado_carroceria' => $registro->estado_carroceria,
            'estado_interiores' => $registro->estado_interiores,
            'estado_llantas' => $registro->estado_llantas,
            'estado_luces' => $registro->estado_luces,
            'estado_torreta' => $registro->estado_torreta,
            'estado_sirena' => $registro->estado_sirena,
            'estado_radio' => $registro->estado_radio,
            'estado_mecanico' => $registro->estado_mecanico,

            'trae_refaccion' => $registro->trae_refaccion,
            'trae_gato' => $registro->trae_gato,
            'trae_llave_cruz' => $registro->trae_llave_cruz,
            'trae_extintor' => $registro->trae_extintor,
            'trae_botiquin' => $registro->trae_botiquin,

            'equipo_adicional' => $registro->equipo_adicional,
            'danos_existentes' => $registro->danos_existentes,
            'novedades' => $registro->novedades,
            'observaciones' => $registro->observaciones,

            'foto_frontal_url' => $this->storageUrl($registro->foto_frontal),
            'foto_trasera_url' => $this->storageUrl($registro->foto_trasera),
            'foto_lateral_izquierdo_url' => $this->storageUrl(
                $registro->foto_lateral_izquierdo
            ),
            'foto_lateral_derecho_url' => $this->storageUrl(
                $registro->foto_lateral_derecho
            ),
            'foto_tablero_url' => $this->storageUrl($registro->foto_tablero),

            'aceptada_entrega' => (bool) $registro->aceptada_entrega,
            'aceptada_recepcion' => (bool) $registro->aceptada_recepcion,

            'entrega_confirmada_at' => $registro->entrega_confirmada_at
                ? $registro->entrega_confirmada_at->toIso8601String()
                : null,

            'recepcion_confirmada_at' => $registro->recepcion_confirmada_at
                ? $registro->recepcion_confirmada_at->toIso8601String()
                : null,
        ];
    }

    private function guardarFotosEntregaRecepcion(Request $request): array
    {
        $campos = [
            'foto_frontal',
            'foto_trasera',
            'foto_lateral_izquierdo',
            'foto_lateral_derecho',
            'foto_tablero',
        ];

        $guardadas = [];

        foreach ($campos as $campo) {
            if (!$request->hasFile($campo)) {
                continue;
            }

            $guardadas[$campo] = $request
                ->file($campo)
                ->store(
                    'patrullas/entregas_recepciones',
                    'public'
                );
        }

        return $guardadas;
    }

    private function eliminarFotos(array $fotos): void
    {
        foreach ($fotos as $ruta) {
            if (
                $ruta
                && Storage::disk('public')->exists($ruta)
            ) {
                Storage::disk('public')->delete($ruta);
            }
        }
    }

    private function storageUrl(?string $ruta): ?string
    {
        if (!$ruta) {
            return null;
        }

        return Storage::disk('public')->url($ruta);
    }

    private function validationErrorResponse(array $errors)
    {
        return response()->json([
            'message' => 'Los datos enviados no son válidos.',
            'errors' => $errors,
        ], 422);
    }
}
