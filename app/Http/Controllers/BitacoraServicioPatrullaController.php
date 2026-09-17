<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\BitacoraServicioPatrulla;
use App\Models\Hechos;
use App\Models\Patrulla;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class BitacoraServicioPatrullaController extends Controller
{
    private function actor()
    {
        return Auth::user();
    }

    private function actorEsSuperadmin(): bool
    {
        $actor = $this->actor();

        return $actor && $actor->hasRole('Superadmin');
    }

    private function actorTieneVisibilidadGlobal(): bool
    {
        $actor = $this->actor();

        return $this->actorEsSuperadmin()
            || (int) ($actor->unidad_id ?? 0) === 3;
    }

    private function unidadIdActor(): ?int
    {
        return optional($this->actor())->unidad_id;
    }

    private function validarPatrullaVisible(Patrulla $patrulla): void
    {
        if ($this->actorTieneVisibilidadGlobal()) {
            return;
        }

        abort_unless(
            (int) $patrulla->unidad_id === (int) $this->unidadIdActor(),
            403
        );
    }

    private function validarBitacoraPerteneceAPatrulla(
        Patrulla $patrulla,
        BitacoraServicioPatrulla $bitacoraServicioPatrulla
    ): void {
        abort_unless(
            (int) $bitacoraServicioPatrulla->patrulla_id === (int) $patrulla->id,
            404
        );
    }

    private function usuariosDisponiblesParaPatrulla(Patrulla $patrulla)
    {
        return User::query()
            ->where('unidad_id', $patrulla->unidad_id)
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'Superadmin');
            })
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'nombres',
                'apellido_paterno',
                'apellido_materno',
                'email',
                'unidad_id',
            ]);
    }

    private function usuarioDeUnidadOFail(
        ?int $userId,
        Patrulla $patrulla
    ): ?User {
        if (!$userId) {
            return null;
        }

        $usuario = User::query()
            ->whereKey($userId)
            ->where('unidad_id', $patrulla->unidad_id)
            ->first();

        if (!$usuario) {
            throw ValidationException::withMessages([
                'capturado_por_user_id' => 'El usuario seleccionado no pertenece a la misma unidad que la patrulla.',
            ]);
        }

        return $usuario;
    }

    private function rangoBitacora(
        BitacoraServicioPatrulla $bitacoraServicioPatrulla
    ): array {
        $fecha = $bitacoraServicioPatrulla->fecha instanceof \DateTimeInterface
            ? $bitacoraServicioPatrulla->fecha->format('Y-m-d')
            : Carbon::parse($bitacoraServicioPatrulla->fecha)->format('Y-m-d');

        $horaInicio = $bitacoraServicioPatrulla->hora_inicio ?: '00:00:00';

        $inicio = Carbon::parse(
            $fecha . ' ' . $horaInicio
        );

        if ($bitacoraServicioPatrulla->cerrada_at) {
            $fin = Carbon::parse(
                $bitacoraServicioPatrulla->cerrada_at
            );
        } elseif ($bitacoraServicioPatrulla->hora_fin) {
            $fin = Carbon::parse(
                $fecha . ' ' . $bitacoraServicioPatrulla->hora_fin
            );

            if ($fin->lt($inicio)) {
                $fin->addDay();
            }
        } else {
            $fin = now();
        }

        if ($fin->lt($inicio)) {
            $fin = $inicio->copy();
        }

        return [$inicio, $fin];
    }

    private function momentoRegistro($fecha, $hora): ?Carbon
    {
        if (!$fecha || !$hora) {
            return null;
        }

        try {
            $fechaTexto = $fecha instanceof \DateTimeInterface
                ? $fecha->format('Y-m-d')
                : Carbon::parse($fecha)->format('Y-m-d');

            return Carbon::parse(
                $fechaTexto . ' ' . trim((string) $hora)
            );
        } catch (Throwable $e) {
            return null;
        }
    }

    private function actividadesDeBitacora(
        BitacoraServicioPatrulla $bitacoraServicioPatrulla,
        Carbon $inicio,
        Carbon $fin
    ) {
        if (!$bitacoraServicioPatrulla->capturado_por_user_id) {
            return collect();
        }

        return Actividad::query()
            ->where(
                'created_by',
                $bitacoraServicioPatrulla->capturado_por_user_id
            )
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
            ->filter(function ($actividad) use ($inicio, $fin) {
                $momento = $this->momentoRegistro(
                    $actividad->fecha,
                    $actividad->hora
                );

                if (!$momento) {
                    return false;
                }

                return $momento->gte($inicio)
                    && $momento->lte($fin);
            })
            ->values();
    }

    private function hechosDeBitacora(
        BitacoraServicioPatrulla $bitacoraServicioPatrulla,
        Carbon $inicio,
        Carbon $fin
    ) {
        if (!$bitacoraServicioPatrulla->capturado_por_user_id) {
            return collect();
        }

        return Hechos::query()
            ->where(
                'created_by',
                $bitacoraServicioPatrulla->capturado_por_user_id
            )
            ->whereBetween('fecha', [
                $inicio->toDateString(),
                $fin->toDateString(),
            ])
            ->with([
                'unidadOrganizacional',
            ])
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get()
            ->filter(function ($hecho) use ($inicio, $fin) {
                $momento = $this->momentoRegistro(
                    $hecho->fecha,
                    $hecho->hora
                );

                if (!$momento) {
                    return false;
                }

                return $momento->gte($inicio)
                    && $momento->lte($fin);
            })
            ->values();
    }

    public function index(Patrulla $patrulla)
    {
        $this->validarPatrullaVisible($patrulla);

        $patrulla->load([
            'unidad',
            'turno',
        ]);

        $bitacoras = BitacoraServicioPatrulla::query()
            ->where('patrulla_id', $patrulla->id)
            ->with([
                'turno',
                'capturadoPor.personal',
            ])
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->orderByDesc('id')
            ->get();

        return view(
            'admin.settings.patrullas.bitacoras.index',
            compact(
                'patrulla',
                'bitacoras'
            )
        );
    }

    public function show(
        Patrulla $patrulla,
        BitacoraServicioPatrulla $bitacoraServicioPatrulla
    ) {
        $this->validarPatrullaVisible($patrulla);

        $this->validarBitacoraPerteneceAPatrulla(
            $patrulla,
            $bitacoraServicioPatrulla
        );

        $patrulla->load([
            'unidad',
            'turno',
        ]);

        $bitacoraServicioPatrulla->load([
            'turno',
            'capturadoPor.personal',
        ]);

        [$inicio, $fin] = $this->rangoBitacora(
            $bitacoraServicioPatrulla
        );

        $actividades = $this->actividadesDeBitacora(
            $bitacoraServicioPatrulla,
            $inicio,
            $fin
        );

        $hechos = $this->hechosDeBitacora(
            $bitacoraServicioPatrulla,
            $inicio,
            $fin
        );

        $totalActividades = $actividades->count();
        $totalHechos = $hechos->count();

        $totalServicios = $totalActividades + $totalHechos;

        return view(
            'admin.settings.patrullas.bitacoras.show',
            compact(
                'patrulla',
                'bitacoraServicioPatrulla',
                'inicio',
                'fin',
                'actividades',
                'hechos',
                'totalActividades',
                'totalHechos',
                'totalServicios'
            )
        );
    }

    public function edit(
        Patrulla $patrulla,
        BitacoraServicioPatrulla $bitacoraServicioPatrulla
    ) {
        $this->validarPatrullaVisible($patrulla);

        $this->validarBitacoraPerteneceAPatrulla(
            $patrulla,
            $bitacoraServicioPatrulla
        );

        $patrulla->load([
            'unidad',
            'turno',
        ]);

        $bitacoraServicioPatrulla->load([
            'turno',
            'capturadoPor',
        ]);

        $turnos = Turno::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $usuarios = $this->usuariosDisponiblesParaPatrulla(
            $patrulla
        );

        return view(
            'admin.settings.patrullas.bitacoras.edit',
            compact(
                'patrulla',
                'bitacoraServicioPatrulla',
                'turnos',
                'usuarios'
            )
        );
    }

    public function update(
        Request $request,
        Patrulla $patrulla,
        BitacoraServicioPatrulla $bitacoraServicioPatrulla
    ) {
        $this->validarPatrullaVisible($patrulla);

        $this->validarBitacoraPerteneceAPatrulla(
            $patrulla,
            $bitacoraServicioPatrulla
        );

        $validated = $request->validate([
            'turno_id' => 'nullable|integer|exists:turnos,id',
            'fecha' => 'required|date',

            'hora_inicio' => [
                'nullable',
                'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/',
            ],

            'hora_fin' => [
                'nullable',
                'required_if:estatus,cerrada',
                'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/',
            ],

            'capturado_por_user_id' => 'nullable|integer|exists:users,id',
            'capturado_por_nombre' => 'nullable|string|max:150',

            'kilometraje_inicio' => 'nullable|integer|min:0',
            'kilometraje_fin' => 'nullable|integer|min:0',

            'combustible_inicio' => 'nullable|numeric|min:0|max:100',
            'combustible_fin' => 'nullable|numeric|min:0|max:100',

            'observaciones' => 'nullable|string|max:5000',

            'estatus' => [
                'required',
                Rule::in([
                    'abierta',
                    'cerrada',
                ]),
            ],
        ]);

        try {
            $usuario = $this->usuarioDeUnidadOFail(
                isset($validated['capturado_por_user_id'])
                    ? (int) $validated['capturado_por_user_id']
                    : null,
                $patrulla
            );

            if ($usuario) {
                $validated['capturado_por_nombre'] =
                    $usuario->nombre_completo;
            } else {
                $validated['capturado_por_nombre'] = trim(
                    (string) (
                        $validated['capturado_por_nombre']
                        ?? ''
                    )
                );

                if (
                    $validated['capturado_por_nombre'] === ''
                ) {
                    throw ValidationException::withMessages([
                        'capturado_por_nombre' => 'Debe indicar el elemento responsable de la bitácora.',
                    ]);
                }
            }

            if (
                !is_null($validated['kilometraje_inicio'] ?? null)
                && !is_null($validated['kilometraje_fin'] ?? null)
                && (int) $validated['kilometraje_fin']
                    < (int) $validated['kilometraje_inicio']
            ) {
                throw ValidationException::withMessages([
                    'kilometraje_fin' => 'El kilometraje final no puede ser menor al kilometraje inicial.',
                ]);
            }

            if ($validated['estatus'] === 'cerrada') {
                $fecha = Carbon::parse(
                    $validated['fecha']
                )->format('Y-m-d');

                $horaInicio =
                    $validated['hora_inicio']
                    ?: '00:00:00';

                $inicio = Carbon::parse(
                    $fecha . ' ' . $horaInicio
                );

                $cierre = Carbon::parse(
                    $fecha . ' ' . $validated['hora_fin']
                );

                if ($cierre->lt($inicio)) {
                    $cierre->addDay();
                }

                $validated['cerrada_at'] = $cierre;
            } else {
                $validated['hora_fin'] = null;
                $validated['cerrada_at'] = null;
            }

            $bitacoraServicioPatrulla->update(
                $validated
            );

            Log::info(
                'Bitácora de servicio de patrulla actualizada.',
                [
                    'bitacora_id' => $bitacoraServicioPatrulla->id,
                    'patrulla_id' => $patrulla->id,
                    'user_id' => optional($this->actor())->id,
                ]
            );

            return redirect()
                ->route(
                    'patrullas.bitacoras.show',
                    [
                        'patrulla' => $patrulla,
                        'bitacoraServicioPatrulla' =>
                            $bitacoraServicioPatrulla,
                    ]
                )
                ->with(
                    'success',
                    'La bitácora fue actualizada correctamente.'
                );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error(
                'Error al actualizar bitácora de servicio: '
                . $e->getMessage(),
                [
                    'bitacora_id' => $bitacoraServicioPatrulla->id,
                    'patrulla_id' => $patrulla->id,
                    'user_id' => optional($this->actor())->id,
                    'exception' => $e,
                ]
            );

            return redirect()
                ->back()
                ->withErrors(
                    'No fue posible actualizar la bitácora.'
                )
                ->withInput();
        }
    }

    public function destroy(
        Patrulla $patrulla,
        BitacoraServicioPatrulla $bitacoraServicioPatrulla
    ) {
        $this->validarPatrullaVisible($patrulla);

        $this->validarBitacoraPerteneceAPatrulla(
            $patrulla,
            $bitacoraServicioPatrulla
        );

        try {
            $bitacoraId =
                $bitacoraServicioPatrulla->id;

            $bitacoraServicioPatrulla->delete();

            Log::warning(
                'Bitácora de servicio de patrulla eliminada.',
                [
                    'bitacora_id' => $bitacoraId,
                    'patrulla_id' => $patrulla->id,
                    'user_id' => optional($this->actor())->id,
                ]
            );

            return redirect()
                ->route(
                    'patrullas.bitacoras.index',
                    [
                        'patrulla' => $patrulla,
                    ]
                )
                ->with(
                    'success',
                    'La bitácora fue eliminada correctamente.'
                );
        } catch (Throwable $e) {
            Log::error(
                'Error al eliminar bitácora de servicio: '
                . $e->getMessage(),
                [
                    'bitacora_id' =>
                        $bitacoraServicioPatrulla->id,
                    'patrulla_id' => $patrulla->id,
                    'user_id' => optional($this->actor())->id,
                    'exception' => $e,
                ]
            );

            return redirect()
                ->back()
                ->withErrors(
                    'No fue posible eliminar la bitácora.'
                );
        }
    }
}
