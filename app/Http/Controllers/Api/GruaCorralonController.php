<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GruaUsuario;
use App\Models\LiberacionCorralon;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GruaCorralonController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->gruaUser($request);
        $estado = mb_strtolower(trim((string) $request->input('estado', 'pendientes')));
        $busqueda = trim((string) $request->input('q', ''));

        if ($estado === 'entregados') {
            $query = LiberacionCorralon::query()
                ->where('grua_id', $user->grua_id)
                ->where('estado', 'ENTREGADO')
                ->with(['vehiculo.hechos', 'vehiculo.servicio.grua'])
                ->when($busqueda !== '', function ($query) use ($busqueda) {
                    $query->whereHas('vehiculo', function ($vehiculo) use ($busqueda) {
                        $this->buscarVehiculo($vehiculo, $busqueda);
                    });
                })
                ->orderByDesc('fecha_entrega')
                ->orderByDesc('id');

            $page = $query->paginate(30);
            $items = collect($page->items())
                ->map(fn (LiberacionCorralon $liberacion) => $this->serializeVehiculo(
                    $liberacion->vehiculo,
                    $liberacion
                ))
                ->values();
        } else {
            $query = $this->vehiculosPendientesQuery((int) $user->grua_id)
                ->with(['hechos', 'servicio.grua', 'liberacionCorralon'])
                ->when($busqueda !== '', function ($query) use ($busqueda) {
                    $query->where(function ($vehiculo) use ($busqueda) {
                        $this->buscarVehiculo($vehiculo, $busqueda);
                    });
                })
                ->orderByDesc('ultimo_servicio_at')
                ->orderByDesc('fecha_inventario_grua')
                ->orderByDesc('updated_at');

            $page = $query->paginate(30);
            $items = collect($page->items())
                ->map(fn (Vehiculo $vehiculo) => $this->serializeVehiculo($vehiculo))
                ->values();
        }

        return response()->json([
            'items' => $items,
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
            'grua' => [
                'id' => $user->grua_id,
                'nombre' => optional($user->grua)->nombre,
            ],
        ]);
    }

    public function show(Request $request, Vehiculo $vehiculo)
    {
        $user = $this->gruaUser($request);
        $this->autorizarVehiculo($vehiculo, $user);

        $vehiculo->load(['hechos', 'servicio.grua', 'liberacionCorralon']);

        return response()->json($this->serializeVehiculo(
            $vehiculo,
            $vehiculo->liberacionCorralon
        ));
    }

    public function entregar(Request $request, Vehiculo $vehiculo)
    {
        $user = $this->gruaUser($request);
        $this->autorizarVehiculo($vehiculo, $user);

        abort_if(
            $vehiculo->liberacionCorralon()
                ->where('estado', 'ENTREGADO')
                ->exists(),
            422,
            'Este vehículo ya fue marcado como entregado.'
        );

        $validated = $request->validate([
            'persona_recibe' => ['required', 'string', 'max:255'],
            'identificacion_recibe' => ['required', 'string', 'max:255'],
            'telefono_recibe' => ['nullable', 'string', 'max:30'],
            'sierse_folio' => ['required', 'string', 'max:120'],
            'condicion_entrega' => ['required', 'in:CONFORME_INVENTARIO,CON_OBSERVACIONES'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'foto_entrega' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'foto_identificacion' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'documento_liberacion' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'memoria_descriptiva' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:15360'],
            'comprobante_pago' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'firma_recibe' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'conformidad_memoria' => ['accepted'],
            'confirmar_entrega' => ['accepted'],
        ]);

        unset($validated['confirmar_entrega']);
        $validated['conformidad_memoria'] = true;
        $validated['telefono_recibe'] = $this->normalizarTelefonoMx(
            $validated['telefono_recibe'] ?? null
        );

        foreach ([
            'foto_entrega',
            'foto_identificacion',
            'documento_liberacion',
            'memoria_descriptiva',
            'comprobante_pago',
            'firma_recibe',
        ] as $campo) {
            if ($request->hasFile($campo)) {
                $validated[$campo] = $request->file($campo)
                    ->store('liberaciones_corralon', 'local');
            }
        }

        $liberacion = DB::transaction(function () use ($request, $vehiculo, $user, $validated) {
            $liberacion = LiberacionCorralon::updateOrCreate(
                ['vehiculo_id' => $vehiculo->id],
                array_merge($validated, [
                    'grua_id' => $user->grua_id,
                    'grua_usuario_id' => $user->id,
                    'estado' => 'ENTREGADO',
                    'fecha_entrega' => now(),
                    'fecha_conformidad' => now(),
                    'ip_entrega' => $request->ip(),
                    'user_agent_entrega' => mb_substr((string) $request->userAgent(), 0, 500),
                ])
            );

            $vehiculo->update(['corralon' => null]);

            return $liberacion;
        });

        $vehiculo->refresh()->load(['hechos', 'servicio.grua']);

        return response()->json([
            'message' => 'Vehículo marcado como entregado.',
            'item' => $this->serializeVehiculo($vehiculo, $liberacion),
        ]);
    }

    public function evidencia(Request $request, LiberacionCorralon $liberacionCorralon, string $campo)
    {
        $user = $this->gruaUser($request);
        abort_unless((int) $liberacionCorralon->grua_id === (int) $user->grua_id, 403);

        abort_unless(in_array($campo, [
            'foto_identificacion',
            'foto_entrega',
            'documento_liberacion',
            'memoria_descriptiva',
            'comprobante_pago',
            'firma_recibe',
        ], true), 404);

        $ruta = $liberacionCorralon->{$campo};
        abort_unless(is_string($ruta) && $ruta !== '', 404);

        $disco = Storage::disk('local')->exists($ruta)
            ? 'local'
            : (Storage::disk('public')->exists($ruta) ? 'public' : null);
        abort_unless($disco, 404);

        return Storage::disk($disco)->response($ruta, basename($ruta));
    }

    private function gruaUser(Request $request): GruaUsuario
    {
        $user = $request->user();
        abort_unless($user instanceof GruaUsuario && $user->activo, 403);
        $user->loadMissing('grua');

        return $user;
    }

    private function vehiculosPendientesQuery(int $gruaId)
    {
        return Vehiculo::query()
            ->select('vehiculos.*')
            ->selectSub(function ($query) {
                $query->from('servicios')
                    ->select('created_at')
                    ->whereColumn('servicios.vehiculo_id', 'vehiculos.id')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->limit(1);
            }, 'ultimo_servicio_at')
            ->whereRaw(
                'COALESCE((SELECT servicios_actuales.grua_id FROM servicios AS servicios_actuales '
                . 'WHERE servicios_actuales.vehiculo_id = vehiculos.id '
                . 'ORDER BY servicios_actuales.created_at DESC, servicios_actuales.id DESC LIMIT 1), '
                . 'vehiculos.grua_id) = ?',
                [$gruaId]
            )
            ->whereNotNull('vehiculos.corralon')
            ->whereRaw("TRIM(vehiculos.corralon) <> ''")
            ->whereRaw(
                "UPPER(TRIM(vehiculos.corralon)) NOT IN ("
                . implode(',', array_fill(0, count(Vehiculo::corralonValoresInvalidos()), '?'))
                . ')',
                Vehiculo::corralonValoresInvalidos()
            )
            ->whereDoesntHave('liberacionCorralon', function ($liberacion) {
                $liberacion->where('estado', 'ENTREGADO');
            });
    }

    private function autorizarVehiculo(Vehiculo $vehiculo, GruaUsuario $user): void
    {
        $gruaServicioActual = $vehiculo->servicios()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('grua_id');
        $gruaAsignada = $gruaServicioActual ?: $vehiculo->grua_id;

        abort_unless((int) $gruaAsignada === (int) $user->grua_id, 403);
    }

    private function buscarVehiculo($query, string $busqueda): void
    {
        $query->where('placas', 'like', '%' . $busqueda . '%')
            ->orWhere('serie', 'like', '%' . $busqueda . '%')
            ->orWhere('marca', 'like', '%' . $busqueda . '%')
            ->orWhere('linea', 'like', '%' . $busqueda . '%')
            ->orWhere('numero_inventario_grua', 'like', '%' . $busqueda . '%');
    }

    private function serializeVehiculo(Vehiculo $vehiculo, ?LiberacionCorralon $liberacion = null): array
    {
        $hecho = $vehiculo->relationLoaded('hechos') ? $vehiculo->hechos->first() : null;
        $servicio = $vehiculo->relationLoaded('servicio') ? $vehiculo->servicio : null;

        return [
            'id' => $vehiculo->id,
            'marca' => $vehiculo->marca,
            'linea' => $vehiculo->linea,
            'modelo' => $vehiculo->modelo,
            'color' => $vehiculo->color,
            'placas' => $vehiculo->placas,
            'serie' => $vehiculo->serie,
            'tipo' => $vehiculo->tipo,
            'numero_inventario_grua' => $vehiculo->numero_inventario_grua,
            'corralon' => $vehiculo->nombreCorralonValido(),
            'fecha_servicio' => $vehiculo->ultimo_servicio_at
                ?? optional($servicio)->created_at
                ?? $vehiculo->fecha_inventario_grua,
            'servicio_descripcion' => optional($servicio)->descripcion,
            'hecho' => $hecho ? [
                'id' => $hecho->id,
                'fecha' => $hecho->fecha,
                'calle' => $hecho->calle,
                'colonia' => $hecho->colonia,
            ] : null,
            'estado' => $liberacion && $liberacion->estado === 'ENTREGADO'
                ? 'ENTREGADO'
                : 'EN_CORRALON',
            'liberacion' => $liberacion ? [
                'id' => $liberacion->id,
                'persona_recibe' => $liberacion->persona_recibe,
                'identificacion_recibe' => $liberacion->identificacion_recibe,
                'sierse_folio' => $liberacion->sierse_folio,
                'condicion_entrega' => $liberacion->condicion_entrega,
                'observaciones' => $liberacion->observaciones,
                'fecha_entrega' => $liberacion->fecha_entrega,
            ] : null,
        ];
    }

    private function normalizarTelefonoMx(?string $telefono): ?string
    {
        if ($telefono === null || trim($telefono) === '') {
            return null;
        }

        $telefono = preg_replace('/\D+/', '', $telefono);
        if ($telefono === '') {
            return null;
        }
        if (strlen($telefono) === 10) {
            return '521' . $telefono;
        }
        if (strlen($telefono) === 12 && str_starts_with($telefono, '52')) {
            return '521' . substr($telefono, 2);
        }

        return $telefono;
    }
}
