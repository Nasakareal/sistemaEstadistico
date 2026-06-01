<?php

namespace App\Http\Controllers;

use App\Models\Liberacion;
use App\Models\Vehiculo;
use App\Models\Hechos;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class LiberacionController extends Controller
{
    private const UNIDAD_DELEGACIONES_ID = 2;

    private const AUTORIZADORES_SINIESTROS = [
        'POL. 3° JORGE ARMANDO MORALES PÉREZ',
        'OFICIAL FERNANDO RUBALCAVA RIVERA',
    ];

    private const MOTIVOS_LIBERACION = [
        'Convenio entre particulares' => 'Convenio, entregó documentación',
        'Error de detención' => 'Error de detención',
        'Acreditó propiedad' => 'Acreditó propiedad',
        'Orden del Ministerio Público' => 'Orden del Ministerio Público',
        'Otro' => 'Otro',
    ];

    public function publica(Vehiculo $vehiculo)
    {
        $liberacion = Liberacion::where('vehiculo_id', $vehiculo->id)->first();

        if (Auth::guard('grua')->check()) {
            return redirect()->route('grua.corralon.show', $vehiculo->id);
        }

        if (auth()->check()) {
            if (auth()->user()->area === 'Grúas') {
                return redirect()->route('liberacion.grua.ver', $vehiculo->id);
            } else {
                if ($liberacion) {
                    return redirect()->route('liberacion.detalles', $vehiculo->id);
                }

                if (!$vehiculo->tieneCorralonValido()) {
                    return $this->redirectVehiculoSinCorralon($vehiculo);
                }

                return redirect()->route('liberacion.create', $vehiculo->id);
            }
        }

        return view('liberaciones.publica', compact('vehiculo', 'liberacion'));
    }

    public function desdeToken($token)
    {
        $liberacion = Liberacion::where('token_unico', $token)->firstOrFail();
        $vehiculo = $liberacion->vehiculo;

        if (Auth::guard('grua')->check()) {
            return redirect()->route('grua.corralon.show', $vehiculo->id);
        }

        if (auth()->check()) {
            if (auth()->user()->area === 'Grúas') {
                return redirect()->route('liberacion.grua.ver', $vehiculo->id);
            } else {
                return redirect()->route('liberacion.detalles', $vehiculo->id);
            }
        }

        return view('liberaciones.publica', compact('vehiculo', 'liberacion'));
    }

    public function generarAcuse(Vehiculo $vehiculo)
    {
        $liberacion = Liberacion::where('vehiculo_id', $vehiculo->id)->firstOrFail();
        $liberacion->regenerarQr();
        $liberacion->refresh();

        $qrPath = public_path($liberacion->qr_path);

        if (!file_exists($qrPath)) {
            \Log::error('QR no encontrado. Campo qr_path: ' . $liberacion->qr_path);
            abort(500, 'El archivo QR no se encuentra o es inválido.');
        }

        $qrBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($qrPath));

        $hecho = $liberacion->hecho;

        $servicio = $vehiculo->servicios()
            ->with('grua')
            ->latest()
            ->first();

        $nombreGrua = ($servicio && $servicio->grua)
            ? $servicio->grua->nombre
            : 'NO ESPECIFICADO';


        $pdf = Pdf::loadView('liberaciones.acuse_pdf', [
            'vehiculo'     => $vehiculo,
            'liberacion'   => $liberacion,
            'qrBase64'     => $qrBase64,
            'hecho'        => $hecho,

            'nombreGrua'   => $nombreGrua,
        ]);

        return $pdf->download('acuse_liberacion_vehiculo_' . $vehiculo->id . '.pdf');
    }


    public function create(Vehiculo $vehiculo)
    {
        if (!$vehiculo->tieneCorralonValido()) {
            return $this->redirectVehiculoSinCorralon($vehiculo);
        }

        $fechaActual = Carbon::now()->format('Y-m-d');
        $hecho = $this->hechoDelVehiculo($vehiculo);
        $autorizaOptions = $this->autorizaOptions($hecho);
        $autorizaPlaceholder = $this->autorizaPlaceholder($hecho, $autorizaOptions);
        $motivosLiberacion = self::MOTIVOS_LIBERACION;

        return view('liberaciones.create', compact('vehiculo', 'fechaActual', 'autorizaOptions', 'autorizaPlaceholder', 'motivosLiberacion'));
    }

    public function store(Request $request, Vehiculo $vehiculo)
    {
        if (!$vehiculo->tieneCorralonValido()) {
            return $this->redirectVehiculoSinCorralon($vehiculo);
        }

        $hecho = $this->hechoDelVehiculo($vehiculo);
        $validated = $this->validarLiberacion($request, $hecho);

        $anio = Carbon::parse($validated['fecha_liberacion'])->year;

        $folio = Liberacion::whereYear('fecha_liberacion', $anio)->count() + 1;
        $folioFormateado = str_pad($folio, 3, '0', STR_PAD_LEFT);
        $folioAnual = "{$folioFormateado}/{$anio}";

        $hechoId = $hecho ? $hecho->id : null;

        $liberacion = Liberacion::create([
            'vehiculo_id' => $vehiculo->id,
            'hecho_id' => $hechoId,
            'token_unico' => Str::uuid(),
            'fecha_liberacion' => $validated['fecha_liberacion'],
            'personas_autorizadas' => $validated['personas_autorizadas'],
            'autoriza' => $validated['autoriza'],
            'motivo_liberacion' => $validated['motivo_liberacion'],
            'folio_anual' => $folioAnual,
            'creado_por' => Auth::id(),
        ]);

        return redirect()->route('liberacion.detalles', $vehiculo->id)->with('success', 'Liberación registrada correctamente.');
    }

    public function edit(Vehiculo $vehiculo)
    {
        $liberacion = Liberacion::where('vehiculo_id', $vehiculo->id)->firstOrFail();
        $hecho = $liberacion->hecho ?: $this->hechoDelVehiculo($vehiculo);
        $autorizaOptions = $this->autorizaOptions($hecho);
        $autorizaPlaceholder = $this->autorizaPlaceholder($hecho, $autorizaOptions);
        $motivosLiberacion = self::MOTIVOS_LIBERACION;

        return view('liberaciones.edit', compact('vehiculo', 'liberacion', 'autorizaOptions', 'autorizaPlaceholder', 'motivosLiberacion'));
    }

    public function update(Request $request, Vehiculo $vehiculo)
    {
        if (!$vehiculo->tieneCorralonValido()) {
            return $this->redirectVehiculoSinCorralon($vehiculo);
        }

        $liberacion = Liberacion::where('vehiculo_id', $vehiculo->id)->firstOrFail();
        $hecho = $liberacion->hecho ?: $this->hechoDelVehiculo($vehiculo);
        $validated = $this->validarLiberacion($request, $hecho);

        $liberacion->fecha_liberacion = $validated['fecha_liberacion'];
        $liberacion->personas_autorizadas = $validated['personas_autorizadas'];
        $liberacion->autoriza = $validated['autoriza'];
        $liberacion->motivo_liberacion = $validated['motivo_liberacion'];
        $liberacion->save();

        return redirect()->route('liberacion.detalles', $vehiculo->id)->with('success', 'Liberación actualizada correctamente.');
    }

    public function detalles(Vehiculo $vehiculo)
    {
        $liberacion = Liberacion::where('vehiculo_id', $vehiculo->id)->firstOrFail();
        return view('liberaciones.detalles', compact('vehiculo', 'liberacion'));
    }

    public function verParaGruas(Vehiculo $vehiculo)
    {
        $liberacion = Liberacion::where('vehiculo_id', $vehiculo->id)->firstOrFail();
        return view('liberaciones.grua', compact('vehiculo', 'liberacion'));
    }

    public function storePdfGruas(Request $request, Vehiculo $vehiculo)
    {
        $request->validate([
            'pdf_gruas' => 'required|file|mimes:pdf|max:5120',
        ]);

        $liberacion = Liberacion::where('vehiculo_id', $vehiculo->id)->firstOrFail();

        if ($request->hasFile('pdf_gruas')) {
            $path = $request->file('pdf_gruas')->store('liberaciones/gruas', 'public');
            $liberacion->pdf_gruas = $path;
            $liberacion->save();
        }

        return redirect()->route('liberacion.grua.ver', $vehiculo->id)->with('success', 'Liberación de grúas subida correctamente.');
    }

    private function redirectVehiculoSinCorralon(Vehiculo $vehiculo)
    {
        $mensaje = 'No se puede liberar un vehículo que no está en resguardo en un corralón.';
        $hecho = $vehiculo->hechos()->first();

        if ($hecho) {
            return redirect()
                ->route('hechos.show', $hecho->id)
                ->with('error', $mensaje);
        }

        return redirect()
            ->route('hechos.index')
            ->with('error', $mensaje);
    }

    private function validarLiberacion(Request $request, ?Hechos $hecho): array
    {
        $autorizaOptions = $this->autorizaOptions($hecho);
        $request->merge([
            'motivo_liberacion_otro' => trim((string) $request->input('motivo_liberacion_otro', '')),
        ]);

        $validated = $request->validate([
            'fecha_liberacion' => 'required|date',
            'personas_autorizadas' => 'required|string',
            'autoriza' => ['required', 'string', Rule::in($autorizaOptions)],
            'motivo_liberacion' => ['required', 'string', Rule::in(array_keys(self::MOTIVOS_LIBERACION))],
            'motivo_liberacion_otro' => 'required_if:motivo_liberacion,Otro|nullable|string|max:255',
        ], [
            'autoriza.in' => $this->autorizaValidationMessage($hecho, $autorizaOptions),
            'motivo_liberacion.in' => 'Selecciona un motivo de liberación válido.',
            'motivo_liberacion_otro.required_if' => 'Escribe el motivo de liberación cuando selecciones Otro.',
        ]);

        if ($validated['motivo_liberacion'] === 'Otro') {
            $validated['motivo_liberacion'] = $validated['motivo_liberacion_otro'];
        }

        unset($validated['motivo_liberacion_otro']);

        return $validated;
    }

    private function autorizaOptions(?Hechos $hecho): array
    {
        if ($this->esHechoDelegaciones($hecho)) {
            return $this->delegadosDeHecho($hecho);
        }

        return self::AUTORIZADORES_SINIESTROS;
    }

    private function autorizaPlaceholder(?Hechos $hecho, array $autorizaOptions): string
    {
        if ($this->esHechoDelegaciones($hecho)) {
            return empty($autorizaOptions)
                ? 'No se encontró delegado para esta delegación'
                : 'Seleccione el delegado que autoriza';
        }

        return 'Seleccione un comandante';
    }

    private function autorizaValidationMessage(?Hechos $hecho, array $autorizaOptions): string
    {
        if ($this->esHechoDelegaciones($hecho) && empty($autorizaOptions)) {
            return 'No se encontró un usuario activo con rol Delegado para la delegación que subió el hecho.';
        }

        return 'Selecciona una persona válida para autorizar la liberación.';
    }

    private function esHechoDelegaciones(?Hechos $hecho): bool
    {
        if (!$hecho) {
            return false;
        }

        $hecho->loadMissing('creator');

        $unidadId = (int) ($hecho->unidad_org_id ?: ($hecho->creator->unidad_id ?? 0));

        return $unidadId === self::UNIDAD_DELEGACIONES_ID;
    }

    private function delegadosDeHecho(?Hechos $hecho): array
    {
        if (!$hecho) {
            return [];
        }

        $hecho->loadMissing(['delegacion', 'creator']);

        $delegacionIds = $this->delegacionIdsParaDelegados($hecho);

        if (empty($delegacionIds)) {
            return [];
        }

        return User::query()
            ->where(function ($query) use ($delegacionIds) {
                $query->whereIn('delegacion_id', $delegacionIds)
                    ->orWhereIn('id', function ($subQuery) use ($delegacionIds) {
                        $subQuery->select('user_id')
                            ->from('delegacion_user')
                            ->whereIn('delegacion_id', $delegacionIds);
                    });
            })
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Delegado');
            })
            ->where(function ($query) {
                $query->whereNull('estado')
                    ->orWhereRaw('UPPER(TRIM(estado)) <> ?', ['INACTIVO']);
            })
            ->orderBy('name')
            ->pluck('name')
            ->map(function ($name) {
                return trim((string) $name);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function delegacionIdsParaDelegados(Hechos $hecho): array
    {
        $ids = [];

        if (!empty($hecho->delegacion_id)) {
            $ids[] = (int) $hecho->delegacion_id;
        }

        if ($hecho->delegacion && !empty($hecho->delegacion->delegacion_padre_id)) {
            $ids[] = (int) $hecho->delegacion->delegacion_padre_id;
        }

        if (empty($ids) && $hecho->creator && !empty($hecho->creator->delegacion_id)) {
            $ids[] = (int) $hecho->creator->delegacion_id;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function hechoDelVehiculo(Vehiculo $vehiculo): ?Hechos
    {
        return $vehiculo->hechos()
            ->with(['delegacion', 'creator'])
            ->first();
    }
}
