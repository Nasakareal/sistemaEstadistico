<?php

namespace App\Http\Controllers;

use App\Models\EntregaRecepcionPatrulla;
use App\Models\Patrulla;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class EntregaRecepcionPatrullaController extends Controller
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

    private function validarEntregaPerteneceAPatrulla(
        Patrulla $patrulla,
        EntregaRecepcionPatrulla $entregaRecepcionPatrulla
    ): void {
        abort_unless(
            (int) $entregaRecepcionPatrulla->patrulla_id === (int) $patrulla->id,
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

    private function usuarioDeUnidadOFail(?int $userId, Patrulla $patrulla): ?User
    {
        if (!$userId) {
            return null;
        }

        $usuario = User::query()
            ->whereKey($userId)
            ->where('unidad_id', $patrulla->unidad_id)
            ->first();

        if (!$usuario) {
            throw ValidationException::withMessages([
                'usuario' => 'El usuario seleccionado no pertenece a la misma unidad que la patrulla.',
            ]);
        }

        return $usuario;
    }

    public function index(Patrulla $patrulla)
    {
        $this->validarPatrullaVisible($patrulla);

        $patrulla->load([
            'unidad',
            'turno',
        ]);

        $entregasRecepciones = EntregaRecepcionPatrulla::query()
            ->where('patrulla_id', $patrulla->id)
            ->with([
                'entregaUsuario.personal',
                'recibeUsuario.personal',
            ])
            ->orderByDesc('fecha')
            ->orderByDesc('hora_real')
            ->orderByDesc('id')
            ->paginate(30);

        return view(
            'admin.settings.patrullas.entregas_recepciones.index',
            compact(
                'patrulla',
                'entregasRecepciones'
            )
        );
    }

    public function show(
        Patrulla $patrulla,
        EntregaRecepcionPatrulla $entregaRecepcionPatrulla
    ) {
        $this->validarPatrullaVisible($patrulla);

        $this->validarEntregaPerteneceAPatrulla(
            $patrulla,
            $entregaRecepcionPatrulla
        );

        $patrulla->load([
            'unidad',
            'turno',
        ]);

        $entregaRecepcionPatrulla->load([
            'entregaUsuario.personal',
            'recibeUsuario.personal',
        ]);

        return view(
            'admin.settings.patrullas.entregas_recepciones.show',
            compact(
                'patrulla',
                'entregaRecepcionPatrulla'
            )
        );
    }

    public function edit(
        Patrulla $patrulla,
        EntregaRecepcionPatrulla $entregaRecepcionPatrulla
    ) {
        $this->validarPatrullaVisible($patrulla);

        $this->validarEntregaPerteneceAPatrulla(
            $patrulla,
            $entregaRecepcionPatrulla
        );

        $patrulla->load([
            'unidad',
            'turno',
        ]);

        $entregaRecepcionPatrulla->load([
            'entregaUsuario',
            'recibeUsuario',
        ]);

        $usuarios = $this->usuariosDisponiblesParaPatrulla($patrulla);

        return view(
            'admin.settings.patrullas.entregas_recepciones.edit',
            compact(
                'patrulla',
                'entregaRecepcionPatrulla',
                'usuarios'
            )
        );
    }

    public function update(
        Request $request,
        Patrulla $patrulla,
        EntregaRecepcionPatrulla $entregaRecepcionPatrulla
    ) {
        $this->validarPatrullaVisible($patrulla);

        $this->validarEntregaPerteneceAPatrulla(
            $patrulla,
            $entregaRecepcionPatrulla
        );

        $estadosGenerales = [
            'bueno',
            'regular',
            'malo',
        ];

        $estadosEquipo = [
            'bueno',
            'regular',
            'malo',
            'no_aplica',
        ];

        $validated = $request->validate([
            'fecha' => 'required|date',

            'hora_programada' => [
                'required',
                'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/',
            ],

            'hora_real' => [
                'nullable',
                'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/',
            ],

            'entrega_user_id' => 'nullable|integer|exists:users,id',
            'entrega_nombre' => 'nullable|string|max:150',

            'recibe_user_id' => 'nullable|integer|exists:users,id',
            'recibe_nombre' => 'nullable|string|max:150',

            'kilometraje' => 'nullable|integer|min:0',
            'nivel_combustible' => 'nullable|numeric|min:0|max:100',

            'estado_carroceria' => [
                'nullable',
                Rule::in($estadosGenerales),
            ],

            'estado_interiores' => [
                'nullable',
                Rule::in($estadosGenerales),
            ],

            'estado_llantas' => [
                'nullable',
                Rule::in($estadosGenerales),
            ],

            'estado_luces' => [
                'nullable',
                Rule::in($estadosGenerales),
            ],

            'estado_torreta' => [
                'nullable',
                Rule::in($estadosEquipo),
            ],

            'estado_sirena' => [
                'nullable',
                Rule::in($estadosEquipo),
            ],

            'estado_radio' => [
                'nullable',
                Rule::in($estadosEquipo),
            ],

            'estado_mecanico' => [
                'nullable',
                Rule::in($estadosGenerales),
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

            'aceptada_entrega' => 'nullable|boolean',
            'aceptada_recepcion' => 'nullable|boolean',
        ]);

        try {
            $usuarioEntrega = $this->usuarioDeUnidadOFail(
                isset($validated['entrega_user_id'])
                    ? (int) $validated['entrega_user_id']
                    : null,
                $patrulla
            );

            $usuarioRecibe = $this->usuarioDeUnidadOFail(
                isset($validated['recibe_user_id'])
                    ? (int) $validated['recibe_user_id']
                    : null,
                $patrulla
            );

            if ($usuarioEntrega) {
                $validated['entrega_nombre'] = $usuarioEntrega->nombre_completo;
            } else {
                $validated['entrega_nombre'] = trim(
                    (string) ($validated['entrega_nombre'] ?? '')
                );

                if ($validated['entrega_nombre'] === '') {
                    $validated['entrega_nombre'] = null;
                }
            }

            if ($usuarioRecibe) {
                $validated['recibe_nombre'] = $usuarioRecibe->nombre_completo;
            } else {
                $validated['recibe_nombre'] = trim(
                    (string) ($validated['recibe_nombre'] ?? '')
                );

                if ($validated['recibe_nombre'] === '') {
                    $validated['recibe_nombre'] = null;
                }
            }

            if (
                empty($validated['entrega_user_id'])
                && empty($validated['entrega_nombre'])
            ) {
                throw ValidationException::withMessages([
                    'entrega_nombre' => 'Debe indicar quién entrega la patrulla.',
                ]);
            }

            if (
                empty($validated['recibe_user_id'])
                && empty($validated['recibe_nombre'])
            ) {
                throw ValidationException::withMessages([
                    'recibe_nombre' => 'Debe indicar quién recibe la patrulla.',
                ]);
            }

            if ($request->has('aceptada_entrega')) {
                $validated['aceptada_entrega'] = $request->boolean(
                    'aceptada_entrega'
                );

                $validated['entrega_confirmada_at'] =
                    $validated['aceptada_entrega']
                        ? (
                            $entregaRecepcionPatrulla->entrega_confirmada_at
                            ?: now()
                        )
                        : null;
            }

            if ($request->has('aceptada_recepcion')) {
                $validated['aceptada_recepcion'] = $request->boolean(
                    'aceptada_recepcion'
                );

                $validated['recepcion_confirmada_at'] =
                    $validated['aceptada_recepcion']
                        ? (
                            $entregaRecepcionPatrulla->recepcion_confirmada_at
                            ?: now()
                        )
                        : null;
            }

            $entregaRecepcionPatrulla->update($validated);

            Log::info(
                'Entrega-recepción de patrulla actualizada.',
                [
                    'registro_id' => $entregaRecepcionPatrulla->id,
                    'patrulla_id' => $patrulla->id,
                    'user_id' => optional($this->actor())->id,
                ]
            );

            return redirect()
                ->route(
                    'patrullas.entregas_recepciones.show',
                    [
                        'patrulla' => $patrulla,
                        'entregaRecepcionPatrulla' => $entregaRecepcionPatrulla,
                    ]
                )
                ->with(
                    'success',
                    'La entrega-recepción fue actualizada correctamente.'
                );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error(
                'Error al actualizar entrega-recepción de patrulla: '
                . $e->getMessage(),
                [
                    'registro_id' => $entregaRecepcionPatrulla->id,
                    'patrulla_id' => $patrulla->id,
                    'user_id' => optional($this->actor())->id,
                    'exception' => $e,
                ]
            );

            return redirect()
                ->back()
                ->withErrors(
                    'No fue posible actualizar la entrega-recepción.'
                )
                ->withInput();
        }
    }

    public function destroy(
        Patrulla $patrulla,
        EntregaRecepcionPatrulla $entregaRecepcionPatrulla
    ) {
        $this->validarPatrullaVisible($patrulla);

        $this->validarEntregaPerteneceAPatrulla(
            $patrulla,
            $entregaRecepcionPatrulla
        );

        try {
            $registroId = $entregaRecepcionPatrulla->id;

            $entregaRecepcionPatrulla->delete();

            Log::warning(
                'Entrega-recepción de patrulla eliminada.',
                [
                    'registro_id' => $registroId,
                    'patrulla_id' => $patrulla->id,
                    'user_id' => optional($this->actor())->id,
                ]
            );

            return redirect()
                ->route(
                    'patrullas.entregas_recepciones.index',
                    [
                        'patrulla' => $patrulla,
                    ]
                )
                ->with(
                    'success',
                    'La entrega-recepción fue eliminada correctamente.'
                );
        } catch (Throwable $e) {
            Log::error(
                'Error al eliminar entrega-recepción de patrulla: '
                . $e->getMessage(),
                [
                    'registro_id' => $entregaRecepcionPatrulla->id,
                    'patrulla_id' => $patrulla->id,
                    'user_id' => optional($this->actor())->id,
                    'exception' => $e,
                ]
            );

            return redirect()
                ->back()
                ->withErrors(
                    'No fue posible eliminar la entrega-recepción.'
                );
        }
    }
}
