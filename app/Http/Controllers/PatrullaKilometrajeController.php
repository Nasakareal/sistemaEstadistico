<?php

namespace App\Http\Controllers;

use App\Models\Patrulla;
use App\Models\PatrullaKilometraje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PatrullaKilometrajeController extends Controller
{
    public function index(Patrulla $patrulla)
    {
        $kilometrajes = PatrullaKilometraje::query()
            ->where('patrulla_id', $patrulla->id)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        return view('admin.settings.patrullas.kilometrajes.index', compact('patrulla', 'kilometrajes'));
    }

    public function create(Patrulla $patrulla)
    {
        $ultimo = PatrullaKilometraje::query()
            ->where('patrulla_id', $patrulla->id)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->first();

        return view('admin.settings.patrullas.kilometrajes.create', compact('patrulla', 'ultimo'));
    }

    public function store(Request $request, Patrulla $patrulla)
    {
        $validated = $request->validate([
            'fecha' => 'required|date',
            'kilometraje_reportado' => 'required|integer|min:0|max:9999999',
            'observaciones' => 'nullable|string',
        ]);

        try {
            $fecha = $validated['fecha'];
            $kmReportado = (int) $validated['kilometraje_reportado'];

            $existeEnFecha = PatrullaKilometraje::query()
                ->where('patrulla_id', $patrulla->id)
                ->where('fecha', $fecha)
                ->exists();

            if ($existeEnFecha) {
                return back()
                    ->withErrors('Ya existe un registro de kilometraje para esa fecha.')
                    ->withInput();
            }

            $anterior = PatrullaKilometraje::query()
                ->where('patrulla_id', $patrulla->id)
                ->where('fecha', '<', $fecha)
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->first();

            if ($anterior && $kmReportado < (int) $anterior->kilometraje_reportado) {
                return back()
                    ->withErrors('El kilometraje reportado no puede ser menor al último kilometraje anterior registrado.')
                    ->withInput();
            }

            $kmRecorridos = null;
            if ($anterior) {
                $kmRecorridos = $kmReportado - (int) $anterior->kilometraje_reportado;
            }

            $registro = PatrullaKilometraje::create([
                'patrulla_id' => $patrulla->id,
                'fecha' => $fecha,
                'kilometraje_reportado' => $kmReportado,
                'kilometros_recorridos' => $kmRecorridos,
                'usuario_id' => Auth::id(),
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            $siguiente = PatrullaKilometraje::query()
                ->where('patrulla_id', $patrulla->id)
                ->where('fecha', '>', $fecha)
                ->orderBy('fecha')
                ->orderBy('id')
                ->first();

            if ($siguiente) {
                $nuevo = (int) $siguiente->kilometraje_reportado - $kmReportado;
                if ($nuevo < 0) {
                    return redirect()
                        ->route('patrullas.kilometrajes.index', $patrulla->id)
                        ->withErrors('Se guardó el registro, pero quedó inconsistente con un registro posterior. Revisa el orden de capturas.');
                }

                $siguiente->update([
                    'kilometros_recorridos' => $nuevo,
                ]);
            }

            Log::info("Kilometraje creado patrulla {$patrulla->id} fecha {$fecha} km {$kmReportado}");

            return redirect()
                ->route('patrullas.kilometrajes.index', $patrulla->id)
                ->with('success', 'Kilometraje registrado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al registrar kilometraje: ' . $e->getMessage());

            return back()
                ->withErrors('Ocurrió un error al registrar el kilometraje.')
                ->withInput();
        }
    }

    public function edit(Patrulla $patrulla, PatrullaKilometraje $kilometraje)
    {
        if ((int) $kilometraje->patrulla_id !== (int) $patrulla->id) {
            abort(404);
        }

        $anterior = PatrullaKilometraje::query()
            ->where('patrulla_id', $patrulla->id)
            ->where(function ($q) use ($kilometraje) {
                $q->where('fecha', '<', $kilometraje->fecha)
                  ->orWhere(function ($q2) use ($kilometraje) {
                      $q2->where('fecha', $kilometraje->fecha)
                         ->where('id', '<', $kilometraje->id);
                  });
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->first();

        return view('admin.settings.patrullas.kilometrajes.edit', compact('patrulla', 'kilometraje', 'anterior'));
    }

    public function update(Request $request, Patrulla $patrulla, PatrullaKilometraje $kilometraje)
    {
        if ((int) $kilometraje->patrulla_id !== (int) $patrulla->id) {
            abort(404);
        }

        $validated = $request->validate([
            'fecha' => 'required|date',
            'kilometraje_reportado' => 'required|integer|min:0|max:9999999',
            'observaciones' => 'nullable|string',
        ]);

        try {
            $fechaNueva = $validated['fecha'];
            $kmNuevo = (int) $validated['kilometraje_reportado'];

            $duplicado = PatrullaKilometraje::query()
                ->where('patrulla_id', $patrulla->id)
                ->where('fecha', $fechaNueva)
                ->where('id', '<>', $kilometraje->id)
                ->exists();

            if ($duplicado) {
                return back()
                    ->withErrors('Ya existe un registro de kilometraje para esa fecha.')
                    ->withInput();
            }

            $anterior = PatrullaKilometraje::query()
                ->where('patrulla_id', $patrulla->id)
                ->where(function ($q) use ($fechaNueva, $kilometraje) {
                    $q->where('fecha', '<', $fechaNueva)
                      ->orWhere(function ($q2) use ($fechaNueva, $kilometraje) {
                          $q2->where('fecha', $fechaNueva)
                             ->where('id', '<', $kilometraje->id);
                      });
                })
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->first();

            if ($anterior && $kmNuevo < (int) $anterior->kilometraje_reportado) {
                return back()
                    ->withErrors('El kilometraje reportado no puede ser menor al kilometraje anterior.')
                    ->withInput();
            }

            $kmRecorridos = null;
            if ($anterior) {
                $kmRecorridos = $kmNuevo - (int) $anterior->kilometraje_reportado;
            }

            $fechaAntes = $kilometraje->fecha;

            $kilometraje->update([
                'fecha' => $fechaNueva,
                'kilometraje_reportado' => $kmNuevo,
                'kilometros_recorridos' => $kmRecorridos,
                'observaciones' => $validated['observaciones'] ?? null,
                'usuario_id' => Auth::id(),
            ]);

            $siguiente = PatrullaKilometraje::query()
                ->where('patrulla_id', $patrulla->id)
                ->where(function ($q) use ($fechaNueva, $kilometraje) {
                    $q->where('fecha', '>', $fechaNueva)
                      ->orWhere(function ($q2) use ($fechaNueva, $kilometraje) {
                          $q2->where('fecha', $fechaNueva)
                             ->where('id', '>', $kilometraje->id);
                      });
                })
                ->orderBy('fecha')
                ->orderBy('id')
                ->first();

            if ($siguiente) {
                $nuevo = (int) $siguiente->kilometraje_reportado - $kmNuevo;
                if ($nuevo < 0) {
                    return redirect()
                        ->route('patrullas.kilometrajes.index', $patrulla->id)
                        ->withErrors('Se actualizó el registro, pero quedó inconsistente con un registro posterior. Revisa el orden de capturas.');
                }

                $siguiente->update([
                    'kilometros_recorridos' => $nuevo,
                ]);
            }

            if ($fechaAntes !== $fechaNueva) {
                $afectado = PatrullaKilometraje::query()
                    ->where('patrulla_id', $patrulla->id)
                    ->where(function ($q) use ($fechaAntes, $kilometraje) {
                        $q->where('fecha', '>', $fechaAntes)
                          ->orWhere(function ($q2) use ($fechaAntes, $kilometraje) {
                              $q2->where('fecha', $fechaAntes)
                                 ->where('id', '>', $kilometraje->id);
                          });
                    })
                    ->orderBy('fecha')
                    ->orderBy('id')
                    ->first();

                if ($afectado && (int) $afectado->id !== (int) ($siguiente->id ?? 0)) {
                    $prev = PatrullaKilometraje::query()
                        ->where('patrulla_id', $patrulla->id)
                        ->where(function ($q) use ($afectado) {
                            $q->where('fecha', '<', $afectado->fecha)
                              ->orWhere(function ($q2) use ($afectado) {
                                  $q2->where('fecha', $afectado->fecha)
                                     ->where('id', '<', $afectado->id);
                              });
                        })
                        ->orderByDesc('fecha')
                        ->orderByDesc('id')
                        ->first();

                    $nuevoKm = null;
                    if ($prev) {
                        $nuevoKm = (int) $afectado->kilometraje_reportado - (int) $prev->kilometraje_reportado;
                        if ($nuevoKm < 0) {
                            $nuevoKm = null;
                        }
                    }

                    $afectado->update([
                        'kilometros_recorridos' => $nuevoKm,
                    ]);
                }
            }

            Log::info("Kilometraje actualizado patrulla {$patrulla->id} id {$kilometraje->id}");

            return redirect()
                ->route('patrullas.kilometrajes.index', $patrulla->id)
                ->with('success', 'Kilometraje actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar kilometraje: ' . $e->getMessage());

            return back()
                ->withErrors('Ocurrió un error al actualizar el kilometraje.')
                ->withInput();
        }
    }

    public function destroy(Patrulla $patrulla, PatrullaKilometraje $kilometraje)
    {
        if ((int) $kilometraje->patrulla_id !== (int) $patrulla->id) {
            abort(404);
        }

        try {
            $fecha = $kilometraje->fecha;
            $kilometrajeId = $kilometraje->id;

            $anterior = PatrullaKilometraje::query()
                ->where('patrulla_id', $patrulla->id)
                ->where(function ($q) use ($kilometraje) {
                    $q->where('fecha', '<', $kilometraje->fecha)
                      ->orWhere(function ($q2) use ($kilometraje) {
                          $q2->where('fecha', $kilometraje->fecha)
                             ->where('id', '<', $kilometraje->id);
                      });
                })
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->first();

            $siguiente = PatrullaKilometraje::query()
                ->where('patrulla_id', $patrulla->id)
                ->where(function ($q) use ($kilometraje) {
                    $q->where('fecha', '>', $kilometraje->fecha)
                      ->orWhere(function ($q2) use ($kilometraje) {
                          $q2->where('fecha', $kilometraje->fecha)
                             ->where('id', '>', $kilometraje->id);
                      });
                })
                ->orderBy('fecha')
                ->orderBy('id')
                ->first();

            $kilometraje->delete();

            if ($siguiente) {
                $nuevoKm = null;
                if ($anterior) {
                    $diff = (int) $siguiente->kilometraje_reportado - (int) $anterior->kilometraje_reportado;
                    $nuevoKm = $diff >= 0 ? $diff : null;
                }

                $siguiente->update([
                    'kilometros_recorridos' => $nuevoKm,
                ]);
            }

            Log::info("Kilometraje eliminado patrulla {$patrulla->id} id {$kilometrajeId} fecha {$fecha}");

            return redirect()
                ->route('patrullas.kilometrajes.index', $patrulla->id)
                ->with('success', 'Registro eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar kilometraje: ' . $e->getMessage());

            return back()
                ->withErrors('No se pudo eliminar el registro.');
        }
    }
}
