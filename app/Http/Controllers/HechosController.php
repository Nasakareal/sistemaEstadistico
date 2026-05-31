<?php

namespace App\Http\Controllers;

use App\Models\Hechos;
use App\Models\Dictamen;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\Unidad;
use App\Helpers\StreetNormalizer;
use App\Services\DelegacionesWhatsAppAlertService;
use App\Services\HechoRevisionNotificationService;
use App\Services\IphPuestaDisposicionDocxService;
use App\Services\IphPuestaDisposicionService;
use App\Services\WhatsApp\WhatsAppBot;
use App\Services\WhatsApp\WhatsAppLink;
use App\Services\WhatsApp\C5IReport;
use App\Services\WhatsApp\NearestUnit;
use App\Support\HechoLocationGuard;
use App\Support\HechoAccess;
use App\Support\GruaEditGuard;

class HechosController extends Controller
{
    public function index(Request $request)
    {
        $tz = 'America/Mexico_City';

        $origenFiltro = (string) $request->query('origen', 'todos');

        if (!in_array($origenFiltro, ['todos', 'actuales', 'historicos'], true)) {
            $origenFiltro = 'todos';
        }

        $sinFecha = $request->boolean('sin_fecha') || ($origenFiltro === 'historicos' && !$request->has('fecha'));
        $fechaSeleccionada = $sinFecha ? '' : (string) $request->query('fecha', now($tz)->toDateString());

        if ($fechaSeleccionada !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSeleccionada)) {
            $fechaSeleccionada = now($tz)->toDateString();
            $sinFecha = false;
        }

        $usuario = auth()->user();
        $unidadFiltro = (string) $request->query('unidad_filtro', '');

        $hechosQuery = Hechos::query()
            ->with(['revisadoPor', 'marcadoRelevantePor', 'croquis', 'creator', 'puestaDisposicion'])
            ->withCount([
                'lesionados',
                'vehiculosEnCorralon as vehiculos_corralon_count',
            ]);

        if ($fechaSeleccionada !== '') {
            $hechosQuery->whereDate('fecha', $fechaSeleccionada);
        }

        if ($origenFiltro === 'historicos') {
            $hechosQuery->where('fuente_ubicacion', 'legacy_peritos');
        } elseif ($origenFiltro === 'actuales') {
            $hechosQuery->where(function ($query) {
                $query->whereNull('fuente_ubicacion')
                    ->orWhere('fuente_ubicacion', '<>', 'legacy_peritos');
            });
        }

        $this->applyHechosVisibilityScope($hechosQuery, $usuario);

        if (
            ($usuario->hasRole('Superadmin') || (int) ($usuario->unidad_id ?? 0) === 3) &&
            in_array($unidadFiltro, ['1', '2', '4'], true)
        ) {
            $hechosQuery->where(function ($query) use ($unidadFiltro) {
                HechoAccess::applyUnidadScope($query, (int) $unidadFiltro);
            });
        }

        $hechos = $hechosQuery
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $hechos->getCollection()->transform(function ($hecho) use ($usuario) {
            $hecho->puede_editar = $this->userCanEditHecho($usuario, $hecho);
            $hecho->tiene_croquis = !is_null($hecho->croquis);

            $unidadReal = HechoAccess::effectiveUnidadIdForHecho($hecho);
            $capturaCompleta = $unidadReal === 2
                ? $hecho->capturaCompletaCalculada()
                : (bool) $hecho->captura_completa;

            $hecho->mostrar_captura = $unidadReal === 2;
            $hecho->captura_completa = $capturaCompleta;
            $hecho->faltantes_captura = $unidadReal === 2 ? $hecho->faltantesCapturaTexto() : [];
            $hecho->estado_captura = $unidadReal === 2
                ? ($capturaCompleta ? 'COMPLETO' : 'INCOMPLETO')
                : null;

            return $hecho;
        });

        return view('hechos.index', compact('hechos', 'fechaSeleccionada', 'unidadFiltro', 'origenFiltro', 'sinFecha'));
    }

    public function create()
    {
        $usuario = auth()->user();
        $puedeUsarDictamenes = $this->userCanUseDictamenes($usuario);
        $puedeGestionarTotalesEsperados = HechoAccess::canManageTotalesEsperados($usuario);
        $puedeCapturarFechaHora = $this->userCanCaptureFechaHora($usuario);

        $dictamenesDisponibles = $puedeUsarDictamenes
            ? Dictamen::query()
                ->whereNull('hecho_id')
                ->orderByDesc('anio')
                ->orderByDesc('numero_dictamen')
                ->get()
            : collect();

        return view('hechos.create', compact('dictamenesDisponibles', 'puedeUsarDictamenes', 'puedeGestionarTotalesEsperados', 'puedeCapturarFechaHora'));
    }

    public function store(Request $request)
    {
        $usuario = auth()->user();

        if ($this->userCannotCreateHecho($usuario)) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para crear hechos.');
        }

        $usaReglasFlexibles = $this->usaReglasFlexiblesHechos($usuario);
        $puedeCapturarFechaHora = $this->userCanCaptureFechaHora($usuario);
        $puedeUsarDictamenes = $this->userCanUseDictamenes($usuario);
        $puedeGestionarTotalesEsperados = HechoAccess::canManageTotalesEsperados($usuario);

        $reglaFolio = 'nullable|string|max:20|unique:hechos,folio_c5i';

        $reglaSector = $usaReglasFlexibles
            ? 'nullable|string|max:100'
            : 'required|string|in:REVOLUCIÓN,NUEVA ESPAÑA,INDEPENDENCIA,REPÚBLICA,CENTRO';

        $rules = [
            'folio_c5i' => $reglaFolio,
            'perito' => 'required|string|max:255',
            'autorizacion_practico' => 'nullable|string|max:255',
            'unidad' => 'required|string|max:50',
            'hora' => $puedeCapturarFechaHora ? 'required|date_format:H:i' : 'nullable',
            'fecha' => $puedeCapturarFechaHora ? 'required|date' : 'nullable',
            'sector' => $reglaSector,
            'calle' => 'required|string|max:255',
            'colonia' => 'required|string|max:255',
            'entre_calles' => 'nullable|string|max:255',
            'municipio' => 'required|string|max:100',
            'tipo_hecho' => 'required|string|max:255',
            'superficie_via' => 'required|string|max:50',
            'tiempo' => 'required|string|in:Día,Noche,Amanecer,Atardecer',
            'clima' => 'required|string|in:Bueno,Malo,Nublado,Lluvioso',
            'condiciones' => 'required|string|in:Bueno,Regular,Malo',
            'control_transito' => 'required|string|max:50',
            'checaron_antecedentes' => 'nullable|boolean',
            'causas' => 'required|string|max:255',
            'responsable' => 'nullable|string|max:255',
            'colision_camino' => 'required|string|max:255',
            'situacion' => 'required|string|in:RESUELTO,PENDIENTE,TURNADO,REPORTE',
            'oficio_mp' => $puedeUsarDictamenes ? 'nullable|string|max:255|required_if:situacion,TURNADO' : 'nullable|string|max:255',
            'vehiculos_mp' => 'required|integer|min:0',
            'personas_mp' => 'required|integer|min:0',
            'danos_patrimoniales' => 'nullable|boolean',
            'propiedades_afectadas' => 'nullable|string|max:255',
            'monto_danos_patrimoniales' => 'nullable|numeric|min:0',
            'dictamen_id' => $puedeUsarDictamenes ? 'nullable|required_if:situacion,TURNADO|exists:dictamens,id' : 'nullable',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'calidad_geo' => 'nullable|string|max:20',
            'nota_geo' => 'nullable|string|max:1000',
            'fuente_ubicacion' => 'nullable|string|max:20',
            'ubicacion_formateada' => 'nullable|string|max:2000',
            'place_id' => 'nullable|string|max:128',
            'foto_lugar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'foto_situacion' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];

        if ($puedeGestionarTotalesEsperados) {
            $rules['vehiculos_esperados'] = 'required|integer|min:0';
            $rules['conductores_esperados'] = 'required|integer|min:0';
            $rules['lesionados_esperados'] = 'required|integer|min:0';
        }

        $request->validate($this->officeLocationRules());

        $validated = $request->validate($rules);

        if (empty($validated['folio_c5i'])) {
            $validated['folio_c5i'] = null;
        }

        if ($puedeGestionarTotalesEsperados) {
            $vehiculosEsperados = (int) ($validated['vehiculos_esperados'] ?? 0);
            $conductoresEsperados = (int) ($validated['conductores_esperados'] ?? 0);

            if ($conductoresEsperados > $vehiculosEsperados) {
                return back()
                    ->withErrors(['conductores_esperados' => 'Los conductores no pueden ser mayores que los vehículos.'])
                    ->withInput();
            }
        } else {
            $validated['vehiculos_esperados'] = 0;
            $validated['conductores_esperados'] = 0;
            $validated['lesionados_esperados'] = 0;
        }

        if ($usaReglasFlexibles && empty($validated['sector'])) {
            $validated['sector'] = $this->sectorPredeterminadoHechos($usuario);
        }

        $validated['checaron_antecedentes'] = $request->has('checaron_antecedentes');
        $validated['danos_patrimoniales'] = $request->has('danos_patrimoniales');

        if (!$validated['danos_patrimoniales']) {
            $validated['propiedades_afectadas'] = null;
            $validated['monto_danos_patrimoniales'] = null;
        }

        $situacion = (string) ($validated['situacion'] ?? '');

        if (!$puedeUsarDictamenes) {
            $validated['oficio_mp'] = null;
        }

        if ($situacion === 'TURNADO' && (int) ($validated['vehiculos_mp'] ?? 0) === 0 && (int) ($validated['personas_mp'] ?? 0) === 0) {
            return back()
                ->withErrors(['vehiculos_mp' => 'Si la situacion es TURNADO, captura al menos una persona o un vehiculo presentado al MP.'])
                ->withInput();
        }

        if (!$usaReglasFlexibles && in_array($situacion, ['RESUELTO', 'TURNADO'], true)) {
            $request->validate([
                'foto_situacion' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            ]);
        }

        $validated['delegacion_id'] = $usuario->delegacion_id ?? null;
        $validated['created_by'] = $usuario->id;
        $validated['unidad_org_id'] = HechoAccess::effectiveUnidadId($usuario);
        $validated['estado_revision'] = 'pendiente';
        $validated['revisado_por'] = null;
        $validated['revisado_at'] = null;
        $validated['observacion_revision'] = null;

        if (!$puedeCapturarFechaHora) {
            $validated['fecha'] = now('America/Mexico_City')->toDateString();
            $validated['hora'] = now('America/Mexico_City')->format('H:i');
        }

        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = strtoupper($this->removeAccents($value));
            }
        }

        $validated['calle_norm'] = StreetNormalizer::normalize($validated['calle'] ?? null);

        $hasCoords = isset($request->lat, $request->lng) && $request->lat !== null && $request->lng !== null;
        if ($hasCoords && empty($validated['fuente_ubicacion'])) {
            $validated['fuente_ubicacion'] = 'GPS_WEB';
        }

        $dictamenId = $puedeUsarDictamenes ? ($validated['dictamen_id'] ?? null) : null;
        unset($validated['dictamen_id']);

        $hecho = Hechos::create($validated);

        $hecho->actualizarEstadoCaptura();

        app(HechoRevisionNotificationService::class)->notificarJefesDeGrupoPorHechoPendiente($hecho);

        $updates = [];

        if ($request->hasFile('foto_lugar')) {
            $path = $request->file('foto_lugar')->store("hechos/{$hecho->id}", 'public');
            $updates['foto_lugar'] = $path;
        }

        if ($request->hasFile('foto_situacion')) {
            $path = $request->file('foto_situacion')->store("hechos/{$hecho->id}", 'public');
            $updates['foto_situacion'] = $path;
        }

        if (!empty($updates)) {
            $hecho->update($updates);
        }

        if ($situacion === 'TURNADO' && $dictamenId) {
            $dictamen = Dictamen::query()->findOrFail($dictamenId);

            if (!empty($dictamen->hecho_id) && (int) $dictamen->hecho_id !== (int) $hecho->id) {
                return redirect()->route('hechos.edit', $hecho->id)
                    ->withErrors(['dictamen_id' => 'Ese dictamen ya está ligado a otro hecho.'])
                    ->withInput();
            }

            $dictamen->hecho_id = $hecho->id;
            $dictamen->save();
        }

        app(DelegacionesWhatsAppAlertService::class)->notificarHechoTurnado($hecho->fresh());

        if ($this->debeEnviarDelegacionesTurnadoAlShow($hecho, $situacion, $validated)) {
            return redirect()->route('hechos.show', $hecho->id)
                ->with('success', 'Hecho creado como TURNADO. Desde aquí puedes vincular la puesta a disposición.');
        }

        return redirect()->route('hechos.edit', $hecho->id)->with('success', 'Hecho creado exitosamente.');
    }

    public function show(Hechos $hecho)
    {
        $usuario = auth()->user();

        $q = Hechos::query()->whereKey($hecho->id);
        $this->applyHechosVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        $hecho->load([
            'creator',
            'vehiculos',
            'vehiculos.servicios',
            'lesionados',
            'dictamen',
            'puestaDisposicion',
            'revisadoPor',
            'marcadoRelevantePor',
            'croquis',
        ]);

        $puedeEditar = $this->userCanEditHecho($usuario, $hecho);
        $croquisData = $this->croquisData($hecho->croquis);

        return view('hechos.show', compact('hecho', 'puedeEditar', 'croquisData'));
    }

    public function descargarIphPuestaDisposicion(
        Hechos $hecho,
        IphPuestaDisposicionService $iphService,
        IphPuestaDisposicionDocxService $docxService
    )
    {
        $usuario = auth()->user();

        if (!$iphService->puedeGenerar($usuario)) {
            abort(403);
        }

        $q = Hechos::query()->whereKey($hecho->id);
        $this->applyHechosVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        $mapeo = $iphService->mapearDesdeHecho($hecho);
        [$path, $filename] = $docxService->generar($hecho, $mapeo);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    private function croquisData(?\App\Models\Croquis $croquis): array
    {
        if (!$croquis || empty($croquis->json_dibujo)) {
            return [];
        }

        if (is_array($croquis->json_dibujo)) {
            return $croquis->json_dibujo;
        }

        $decoded = json_decode($croquis->json_dibujo, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function edit(Hechos $hecho)
    {
        $usuario = auth()->user();

        if (!$this->userCanEditHecho($usuario, $hecho)) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para editar este hecho.');
        }

        $updatesSellado = [];

        if (empty($hecho->unidad_org_id)) {
            $updatesSellado['unidad_org_id'] = HechoAccess::effectiveUnidadId($usuario);
        }

        if (empty($hecho->delegacion_id) && !empty($usuario->delegacion_id)) {
            $updatesSellado['delegacion_id'] = $usuario->delegacion_id;
        }

        if (!empty($updatesSellado)) {
            $hecho->update($updatesSellado);
            $hecho->refresh();
        }

        $dictamenActual = $hecho->dictamen;

        $puedeUsarDictamenes = $this->userCanUseDictamenes($usuario, $hecho);

        $dictamenesDisponibles = $puedeUsarDictamenes
            ? Dictamen::query()
                ->whereNull('hecho_id')
                ->orderByDesc('anio')
                ->orderByDesc('numero_dictamen')
                ->get()
            : collect();

        if ($dictamenActual) {
            $dictamenesDisponibles = $dictamenesDisponibles->prepend($dictamenActual);
        }

        $dictamenLabel = null;
        if ($dictamenActual) {
            $dictamenLabel = $dictamenActual->numero_dictamen . '/' . $dictamenActual->anio . ' ' . $dictamenActual->nombre_mp;
        }

        $puedeGestionarTotalesEsperados = HechoAccess::canManageTotalesEsperados($usuario, $hecho);
        $puedeCapturarFechaHora = $this->userCanCaptureFechaHora($usuario);
        $puedeEditarCoordenadasManual = $this->userCanEditCoordenadasManual($usuario);

        return view('hechos.edit', compact('hecho', 'dictamenesDisponibles', 'dictamenActual', 'dictamenLabel', 'puedeUsarDictamenes', 'puedeGestionarTotalesEsperados', 'puedeCapturarFechaHora', 'puedeEditarCoordenadasManual'));
    }

    public function update(Request $request, Hechos $hecho)
    {
        $usuario = auth()->user();

        if (!$this->userCanEditHecho($usuario, $hecho)) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para editar este hecho.');
        }

        if (empty($hecho->unidad_org_id)) {
            $hecho->update(['unidad_org_id' => HechoAccess::effectiveUnidadId($usuario)]);
            $hecho->refresh();
        }

        if (empty($hecho->delegacion_id) && !empty($usuario->delegacion_id)) {
            $hecho->update(['delegacion_id' => $usuario->delegacion_id]);
            $hecho->refresh();
        }

        $hechoAntes = clone $hecho;

        $quitarFotoLugar = (string) $request->input('quitar_foto_lugar', '0') === '1';
        $quitarFotoSituacion = (string) $request->input('quitar_foto_situacion', '0') === '1';

        $usaReglasFlexibles = $this->usaReglasFlexiblesHechos($usuario, $hecho);
        $puedeCapturarFechaHora = $this->userCanCaptureFechaHora($usuario);
        $puedeUsarDictamenes = $this->userCanUseDictamenes($usuario, $hecho);
        $puedeGestionarTotalesEsperados = HechoAccess::canManageTotalesEsperados($usuario, $hecho);
        $puedeEditarCoordenadasManual = $this->userCanEditCoordenadasManual($usuario);

        if ($puedeEditarCoordenadasManual && $request->has('coordenadas_manual')) {
            $this->normalizarCoordenadasManualRequest($request, $hecho);
        }

        $reglaFolio = [
            'nullable',
            'string',
            'max:20',
            Rule::unique('hechos', 'folio_c5i')->ignore($hecho->id),
        ];

        $reglaSector = $usaReglasFlexibles
            ? 'nullable|string|max:100'
            : 'required|string|in:REVOLUCIÓN,NUEVA ESPAÑA,INDEPENDENCIA,REPÚBLICA,CENTRO';

        $rules = [
            'folio_c5i' => $reglaFolio,
            'perito' => 'required|string|max:255',
            'autorizacion_practico' => 'nullable|string|max:255',
            'unidad' => 'required|string|max:50',
            'hora' => $puedeCapturarFechaHora ? 'required|date_format:H:i' : 'nullable',
            'fecha' => $puedeCapturarFechaHora ? 'required|date' : 'nullable',
            'sector' => $reglaSector,
            'calle' => 'required|string|max:255',
            'colonia' => 'required|string|max:255',
            'entre_calles' => 'nullable|string|max:255',
            'municipio' => 'required|string|max:100',
            'tipo_hecho' => 'required|string|max:255',
            'superficie_via' => 'required|string|max:50',
            'tiempo' => 'required|string|in:Día,Noche,Amanecer,Atardecer',
            'clima' => 'required|string|in:Bueno,Malo,Nublado,Lluvioso',
            'condiciones' => 'required|string|in:Bueno,Regular,Malo',
            'control_transito' => 'required|string|max:50',
            'checaron_antecedentes' => 'nullable|boolean',
            'causas' => 'required|string|max:255',
            'responsable' => 'nullable|string|max:255',
            'colision_camino' => 'required|string|max:255',
            'situacion' => 'required|string|in:RESUELTO,PENDIENTE,TURNADO,REPORTE',
            'oficio_mp' => $puedeUsarDictamenes ? 'nullable|string|max:255|required_if:situacion,TURNADO' : 'nullable|string|max:255',
            'vehiculos_mp' => 'required|integer|min:0',
            'personas_mp' => 'required|integer|min:0',
            'danos_patrimoniales' => 'nullable|boolean',
            'propiedades_afectadas' => 'nullable|string|max:255',
            'monto_danos_patrimoniales' => 'nullable|numeric|min:0',
            'dictamen_id' => $puedeUsarDictamenes ? 'nullable|required_if:situacion,TURNADO|exists:dictamens,id' : 'nullable',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'calidad_geo' => 'nullable|string|max:20',
            'nota_geo' => 'nullable|string|max:1000',
            'fuente_ubicacion' => 'nullable|string|max:20',
            'ubicacion_formateada' => 'nullable|string|max:2000',
            'place_id' => 'nullable|string|max:128',
            'foto_lugar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'foto_situacion' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];

        if ($puedeGestionarTotalesEsperados) {
            $rules['vehiculos_esperados'] = 'required|integer|min:0';
            $rules['conductores_esperados'] = 'required|integer|min:0';
            $rules['lesionados_esperados'] = 'required|integer|min:0';
        }

        $request->validate($this->officeLocationRules());

        $validated = $request->validate($rules);

        if ($puedeGestionarTotalesEsperados) {
            $vehiculosEsperados = (int) ($validated['vehiculos_esperados'] ?? 0);
            $conductoresEsperados = (int) ($validated['conductores_esperados'] ?? 0);

            if ($conductoresEsperados > $vehiculosEsperados) {
                return back()
                    ->withErrors(['conductores_esperados' => 'Los conductores no pueden ser mayores que los vehículos.'])
                    ->withInput();
            }
        } else {
            unset(
                $validated['vehiculos_esperados'],
                $validated['conductores_esperados'],
                $validated['lesionados_esperados']
            );
        }

        if (empty($validated['folio_c5i'])) {
            $validated['folio_c5i'] = null;
        }

        if ($usaReglasFlexibles && empty($validated['sector'])) {
            $validated['sector'] = $this->sectorPredeterminadoHechos($usuario, $hecho);
        }

        $validated['checaron_antecedentes'] = $request->has('checaron_antecedentes');
        $validated['danos_patrimoniales'] = $request->has('danos_patrimoniales');

        if (!$validated['danos_patrimoniales']) {
            $validated['propiedades_afectadas'] = null;
            $validated['monto_danos_patrimoniales'] = null;
        }

        $situacion = (string) ($validated['situacion'] ?? '');

        if (!$puedeUsarDictamenes) {
            $validated['oficio_mp'] = null;
        }

        if ($situacion === 'TURNADO' && (int) ($validated['vehiculos_mp'] ?? 0) === 0 && (int) ($validated['personas_mp'] ?? 0) === 0) {
            return back()
                ->withErrors(['vehiculos_mp' => 'Si la situacion es TURNADO, captura al menos una persona o un vehiculo presentado al MP.'])
                ->withInput();
        }

        if (!$usaReglasFlexibles && in_array($situacion, ['RESUELTO', 'TURNADO'], true)) {
            $hayFotoGuardada = !empty($hecho->foto_situacion) && !$quitarFotoSituacion;

            if (!$hayFotoGuardada && !$request->hasFile('foto_situacion')) {
                return back()
                    ->withErrors(['foto_situacion' => 'La foto de la situación es obligatoria cuando la situación es RESUELTO o TURNADO.'])
                    ->withInput();
            }
        }

        $validated['updated_by'] = $usuario->id;

        if (!$puedeCapturarFechaHora) {
            $validated['fecha'] = !empty($hecho->fecha)
                ? \Carbon\Carbon::parse($hecho->fecha)->toDateString()
                : substr((string) $hecho->created_at, 0, 10);

            $validated['hora'] = !empty($hecho->hora)
                ? substr((string) $hecho->hora, 0, 5)
                : substr((string) $hecho->created_at, 11, 5);
        }

        if (empty($hecho->unidad_org_id)) {
            $validated['unidad_org_id'] = HechoAccess::effectiveUnidadId($usuario);
        } elseif (!empty($hecho->unidad_org_id)) {
            $validated['unidad_org_id'] = $hecho->unidad_org_id;
        }

        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = strtoupper($this->removeAccents($value));
            }
        }

        $validated['calle_norm'] = StreetNormalizer::normalize($validated['calle'] ?? null);

        $hasCoords = isset($request->lat, $request->lng) && $request->lat !== null && $request->lng !== null;
        if ($hasCoords && empty($validated['fuente_ubicacion'])) {
            $validated['fuente_ubicacion'] = 'GPS_WEB';
        }

        if ($quitarFotoLugar) {
            if (!empty($hecho->foto_lugar) && Storage::disk('public')->exists($hecho->foto_lugar)) {
                Storage::disk('public')->delete($hecho->foto_lugar);
            }
            $validated['foto_lugar'] = null;
        }

        if ($quitarFotoSituacion) {
            if (!empty($hecho->foto_situacion) && Storage::disk('public')->exists($hecho->foto_situacion)) {
                Storage::disk('public')->delete($hecho->foto_situacion);
            }
            $validated['foto_situacion'] = null;
        }

        if ($request->hasFile('foto_lugar')) {
            if (!empty($hecho->foto_lugar) && Storage::disk('public')->exists($hecho->foto_lugar)) {
                Storage::disk('public')->delete($hecho->foto_lugar);
            }
            $validated['foto_lugar'] = $request->file('foto_lugar')->store("hechos/{$hecho->id}", 'public');
        }

        if ($request->hasFile('foto_situacion')) {
            if (!empty($hecho->foto_situacion) && Storage::disk('public')->exists($hecho->foto_situacion)) {
                Storage::disk('public')->delete($hecho->foto_situacion);
            }
            $validated['foto_situacion'] = $request->file('foto_situacion')->store("hechos/{$hecho->id}", 'public');
        }

        $dictamenId = $puedeUsarDictamenes ? ($validated['dictamen_id'] ?? null) : null;
        unset($validated['dictamen_id']);

        $hecho->update($validated);

        $hecho->actualizarEstadoCaptura();

        $dictamenActual = $hecho->dictamen;

        if ($situacion === 'TURNADO' && $dictamenId) {
            if ($dictamenActual && (int) $dictamenActual->id !== (int) $dictamenId) {
                $dictamenActual->hecho_id = null;
                $dictamenActual->save();
            }

            $nuevo = Dictamen::query()->findOrFail($dictamenId);

            if (!empty($nuevo->hecho_id) && (int) $nuevo->hecho_id !== (int) $hecho->id) {
                return redirect()->route('hechos.edit', $hecho->id)
                    ->withErrors(['dictamen_id' => 'Ese dictamen ya está ligado a otro hecho.'])
                    ->withInput();
            }

            $nuevo->hecho_id = $hecho->id;
            $nuevo->save();
        } else {
            if ($dictamenActual) {
                $dictamenActual->hecho_id = null;
                $dictamenActual->save();
            }
        }

        $hecho->refresh();
        $alertService = app(DelegacionesWhatsAppAlertService::class);

        if ($alertService->debeNotificarNuevaPuestaHecho($hechoAntes, $hecho)) {
            $alertService->notificarHechoTurnado($hecho);
        }

        if ($this->debeEnviarDelegacionesTurnadoAlShow($hecho, $situacion, $validated)) {
            return redirect()->route('hechos.show', $hecho->id)
                ->with('success', 'Hecho actualizado como TURNADO. Desde aquí puedes vincular la puesta a disposición.');
        }

        return redirect()->route('hechos.index')->with('success', 'Hecho actualizado exitosamente.');
    }

    private function debeEnviarDelegacionesTurnadoAlShow(Hechos $hecho, string $situacion, array $data): bool
    {
        if (HechoAccess::effectiveUnidadIdForHecho($hecho) !== 2) {
            return false;
        }

        if (strtoupper(trim($situacion)) !== 'TURNADO') {
            return false;
        }

        return (int) ($data['vehiculos_mp'] ?? 0) > 0
            || (int) ($data['personas_mp'] ?? 0) > 0;
    }

    public function destroy(Hechos $hecho)
    {
        $usuario = auth()->user();

        if (!$usuario) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para eliminar este hecho.');
        }

        $q = Hechos::query()->whereKey($hecho->id);
        $this->applyHechosVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        if (
            !$usuario->hasRole('Superadmin') &&
            !(
                $usuario->hasRole('Administrador')
                && in_array($unidadId, [1, 2, 4], true)
            )
        ) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para eliminar este hecho.');
        }

        if ($unidadId === 3) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para eliminar este hecho.');
        }

        $hecho->loadMissing('vehiculos');

        if (
            GruaEditGuard::locksHecho($usuario, $hecho)
            && $hecho->vehiculos->contains(fn ($vehiculo) => GruaEditGuard::vehicleHasGruaData($vehiculo))
        ) {
            return redirect()->route('hechos.index')
                ->with('error', 'Este hecho tiene grúa o corralón bloqueado. Solicita autorización de un Administrador.');
        }

        try {
            if (!empty($hecho->foto_lugar) && Storage::disk('public')->exists($hecho->foto_lugar)) {
                Storage::disk('public')->delete($hecho->foto_lugar);
            }

            if (!empty($hecho->foto_situacion) && Storage::disk('public')->exists($hecho->foto_situacion)) {
                Storage::disk('public')->delete($hecho->foto_situacion);
            }

            $dictamenActual = $hecho->dictamen;
            if ($dictamenActual) {
                $dictamenActual->hecho_id = null;
                $dictamenActual->save();
            }

            $hecho->vehiculos()->detach();
            $hecho->lesionados()->delete();

            if ($hecho->croquis) {
                $hecho->croquis->delete();
            }

            $hecho->delete();

            return redirect()->route('hechos.index')->with('success', 'Hecho eliminado exitosamente.');
        } catch (\Throwable $e) {
            return redirect()->route('hechos.index')->with(
                'error',
                'No se pudo eliminar el hecho. Revisa relaciones/llaves foráneas relacionadas. Error: ' . $e->getMessage()
            );
        }
    }

    private function removeAccents($string)
    {
        $unwanted_array = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
            'Â' => 'A', 'Ê' => 'E', 'Î' => 'I', 'Ô' => 'O', 'Û' => 'U',
            'Ä' => 'A', 'Ë' => 'E', 'Ï' => 'I', 'Ö' => 'O', 'Ü' => 'U',
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
            'à' => 'A', 'è' => 'E', 'ì' => 'I', 'ò' => 'O', 'ù' => 'U',
            'â' => 'A', 'ê' => 'E', 'î' => 'I', 'ô' => 'O', 'û' => 'U',
            'ä' => 'A', 'ë' => 'A', 'ï' => 'I', 'ö' => 'O', 'ü' => 'U',
            'Ñ' => 'N', 'ñ' => 'N',
            'Ç' => 'C', 'ç' => 'C'
        ];

        return strtr($string, $unwanted_array);
    }

    public function sendWhatsapp(Hechos $hecho)
    {
        $user = auth()->user();

        if (!$user || !$user->can('ver hechos')) {
            abort(403);
        }

        if (!is_null($hecho->whatsapp_sent_at)) {
            return redirect()->back()->with('info', 'Este hecho ya fue compartido por WhatsApp.');
        }

        $hecho->load(['vehiculos']);

        $card = WhatsAppLink::textForHecho($hecho);

        $gmaps = C5IReport::googleMapsLinkFromHecho($hecho);

        $recoText = "RECOMENDACIÓN: NO DISPONIBLE (SIN COORDENADAS).";
        if (is_numeric($hecho->lat) && is_numeric($hecho->lng)) {
            $r = NearestUnit::recommendForCoords((float)$hecho->lat, (float)$hecho->lng, 3);
            $recoText = NearestUnit::recommendationText($r);
        }

        $messageParts = [];
        $messageParts[] = $card;
        $messageParts[] = "";
        if ($gmaps) $messageParts[] = $gmaps;
        $messageParts[] = $recoText;

        $message = implode("\n", $messageParts);

        $media = [];

        if (!empty($hecho->foto_lugar)) {
            $media[] = asset('storage/' . ltrim($hecho->foto_lugar, '/'));
        }

        if (!empty($hecho->foto_situacion)) {
            $media[] = asset('storage/' . ltrim($hecho->foto_situacion, '/'));
        }

        foreach ($hecho->vehiculos as $v) {
            if (!empty($v->fotos)) {
                $media[] = asset('storage/' . ltrim($v->fotos, '/'));
            }
        }

        $chatId = (string) env('WHATSAPP_DEFAULT_CHAT_ID');

        $resp = WhatsAppBot::sendToChat($chatId, $message, $media);

        if (!($resp['ok'] ?? false)) {
            return redirect()->back()->with('error', 'No se pudo enviar a WhatsApp.');
        }

        $hecho->whatsapp_sent_at = now();
        $hecho->whatsapp_chat_id = $chatId;
        $hecho->whatsapp_message_id = (string) ($resp['id'] ?? '');
        $hecho->save();

        return redirect()->back()->with('success', 'Hecho compartido por WhatsApp.');
    }

    private function officeLocationRules(): array
    {
        return [
            'lat' => function ($attribute, $value, $fail) {
                if (HechoLocationGuard::isBlockedOfficeLocation($value, request()->input('lng'))) {
                    $fail(HechoLocationGuard::OFFICE_MESSAGE);
                }
            },
            'lng' => function ($attribute, $value, $fail) {
                if (HechoLocationGuard::isBlockedOfficeLocation(request()->input('lat'), $value)) {
                    $fail(HechoLocationGuard::OFFICE_MESSAGE);
                }
            },
        ];
    }

    private function normalizarCoordenadasManualRequest(Request $request, ?Hechos $hecho = null): void
    {
        $request->validate([
            'coordenadas_manual' => [
                'nullable',
                'string',
                'max:100',
                function ($attribute, $value, $fail) {
                    $texto = trim((string) $value);

                    if ($texto !== '' && !$this->parsearCoordenadasManual($texto)) {
                        $fail('Captura las coordenadas en formato latitud, longitud.');
                    }
                },
            ],
        ]);

        $texto = trim((string) $request->input('coordenadas_manual', ''));

        if ($texto === '') {
            $request->merge([
                'lat' => null,
                'lng' => null,
                'calidad_geo' => null,
                'fuente_ubicacion' => null,
            ]);
            return;
        }

        $coordenadas = $this->parsearCoordenadasManual($texto);

        if (!$coordenadas) {
            return;
        }

        $lat = number_format($coordenadas['lat'], 7, '.', '');
        $lng = number_format($coordenadas['lng'], 7, '.', '');
        $latActual = $hecho && is_numeric($hecho->lat) ? number_format((float) $hecho->lat, 7, '.', '') : null;
        $lngActual = $hecho && is_numeric($hecho->lng) ? number_format((float) $hecho->lng, 7, '.', '') : null;

        if ($hecho && $latActual === $lat && $lngActual === $lng) {
            $request->merge([
                'lat' => $lat,
                'lng' => $lng,
                'calidad_geo' => $hecho->calidad_geo,
                'fuente_ubicacion' => $hecho->fuente_ubicacion,
            ]);
            return;
        }

        $fuenteSolicitada = strtoupper(trim((string) $request->input('fuente_ubicacion', '')));

        $request->merge([
            'lat' => $lat,
            'lng' => $lng,
            'calidad_geo' => $fuenteSolicitada === 'GPS_WEB' ? $request->input('calidad_geo') : null,
            'fuente_ubicacion' => $fuenteSolicitada === 'GPS_WEB' ? 'GPS_WEB' : 'MANUAL_WEB',
        ]);
    }

    private function parsearCoordenadasManual(string $texto): ?array
    {
        $texto = trim($texto);

        if ($texto === '') {
            return null;
        }

        $partes = preg_split('/\s*,\s*|\s+/', $texto);

        if (!$partes || count($partes) !== 2) {
            return null;
        }

        $lat = str_replace(',', '.', trim((string) $partes[0]));
        $lng = str_replace(',', '.', trim((string) $partes[1]));

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    private function applyHechosVisibilityScope($query, $usuario): void
    {
        HechoAccess::applyVisibilityScope($query, $usuario);
    }

    private function userCanEditHecho($usuario, Hechos $hecho): bool
    {
        return HechoAccess::canEdit($usuario, $hecho);
    }

    private function scopeHechosUnidad($query, int $unidadId): void
    {
        HechoAccess::applyUnidadScope($query, $unidadId);
    }

    private function aplicarFiltroCapturaIncompletaDelegaciones($query): void
    {
        $query->where(function ($q) {
            $q->where('captura_completa', false)
                ->orWhereNull('captura_completa')
                ->orWhereRaw('COALESCE(vehiculos_capturados, 0) < COALESCE(vehiculos_esperados, 0)')
                ->orWhereRaw('COALESCE(conductores_capturados, 0) < COALESCE(conductores_esperados, 0)')
                ->orWhereRaw('COALESCE(lesionados_capturados, 0) < COALESCE(lesionados_esperados, 0)');
        });
    }

    public function seguimiento(Request $request)
    {
        $usuario = auth()->user();

        $esUsuarioDelegaciones = $usuario && (int) ($usuario->unidad_id ?? 0) === 2;
        $periodoDefault = $esUsuarioDelegaciones ? 'ANIO' : 'SEMANA';
        $situacionDefault = $esUsuarioDelegaciones ? 'TODOS' : 'PENDIENTE';
        $puedeMostrarTodasSituaciones = $esUsuarioDelegaciones;

        $periodo = strtoupper($request->get('periodo', $periodoDefault));
        $situacion = strtoupper($request->get('situacion', $situacionDefault));
        $unidadFiltro = (string) $request->get('unidad_filtro', '');
        $puedeFiltrarUnidad = $usuario
            && ($usuario->hasRole('Superadmin') || (int) ($usuario->unidad_id ?? 0) === 3);
        $unidadesFiltro = [
            '1' => 'Siniestros',
            '2' => 'Delegaciones',
            '4' => 'Carreteras',
        ];

        $situacionesValidas = ['PENDIENTE', 'TURNADO', 'RESUELTO', 'FALTA_COMPLETAR'];
        if ($puedeMostrarTodasSituaciones) {
            $situacionesValidas[] = 'TODOS';
        }

        $periodosValidos = ['SEMANA', 'MES', 'ANIO'];

        if (!in_array($situacion, $situacionesValidas, true)) {
            $situacion = $situacionDefault;
        }

        if (!in_array($periodo, $periodosValidos, true)) {
            $periodo = $periodoDefault;
        }

        if (!$puedeFiltrarUnidad || !array_key_exists($unidadFiltro, $unidadesFiltro)) {
            $unidadFiltro = '';
        }

        $aplicarFiltroUnidad = function ($query) use ($puedeFiltrarUnidad, $unidadFiltro) {
            if (!$puedeFiltrarUnidad || $unidadFiltro === '') {
                return;
            }

            $this->scopeHechosUnidad($query, (int) $unidadFiltro);
        };

        $hoy = now('America/Mexico_City');

        $inicioSemana = $hoy->copy()->startOfWeek();
        $finSemana = $hoy->copy()->endOfWeek();

        $inicioMes = $hoy->copy()->startOfMonth();
        $finMes = $hoy->copy()->endOfMonth();

        $inicioAnio = $hoy->copy()->startOfYear();
        $finAnio = $hoy->copy()->endOfYear();

        $crearQueryConteo = function ($inicio, $fin, $situacionConteo) use ($usuario, $aplicarFiltroUnidad) {
            $query = Hechos::query()
                ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
                ->where('situacion', $situacionConteo);

            $this->applyHechosVisibilityScope($query, $usuario);
            $aplicarFiltroUnidad($query);

            return $query;
        };

        $crearQueryTodos = function ($inicio, $fin) use ($usuario, $aplicarFiltroUnidad) {
            $query = Hechos::query()
                ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()]);

            $this->applyHechosVisibilityScope($query, $usuario);
            $aplicarFiltroUnidad($query);

            return $query;
        };

        $crearQueryFaltaCompletar = function ($inicio, $fin) use ($usuario, $puedeFiltrarUnidad, $unidadFiltro) {
            $query = Hechos::query()
                ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()]);

            $this->applyHechosVisibilityScope($query, $usuario);

            if ($puedeFiltrarUnidad && $unidadFiltro !== '' && $unidadFiltro !== '2') {
                $query->whereRaw('1=0');
            } else {
                $this->scopeHechosUnidad($query, 2);
                $this->aplicarFiltroCapturaIncompletaDelegaciones($query);
            }

            return $query;
        };

        $conteos = [
            'semana' => [
                'TODOS' => $crearQueryTodos($inicioSemana, $finSemana)->count(),
                'PENDIENTE' => $crearQueryConteo($inicioSemana, $finSemana, 'PENDIENTE')->count(),
                'TURNADO' => $crearQueryConteo($inicioSemana, $finSemana, 'TURNADO')->count(),
                'RESUELTO' => $crearQueryConteo($inicioSemana, $finSemana, 'RESUELTO')->count(),
                'FALTA_COMPLETAR' => $crearQueryFaltaCompletar($inicioSemana, $finSemana)->count(),
            ],
            'mes' => [
                'TODOS' => $crearQueryTodos($inicioMes, $finMes)->count(),
                'PENDIENTE' => $crearQueryConteo($inicioMes, $finMes, 'PENDIENTE')->count(),
                'TURNADO' => $crearQueryConteo($inicioMes, $finMes, 'TURNADO')->count(),
                'RESUELTO' => $crearQueryConteo($inicioMes, $finMes, 'RESUELTO')->count(),
                'FALTA_COMPLETAR' => $crearQueryFaltaCompletar($inicioMes, $finMes)->count(),
            ],
            'anio' => [
                'TODOS' => $crearQueryTodos($inicioAnio, $finAnio)->count(),
                'PENDIENTE' => $crearQueryConteo($inicioAnio, $finAnio, 'PENDIENTE')->count(),
                'TURNADO' => $crearQueryConteo($inicioAnio, $finAnio, 'TURNADO')->count(),
                'RESUELTO' => $crearQueryConteo($inicioAnio, $finAnio, 'RESUELTO')->count(),
                'FALTA_COMPLETAR' => $crearQueryFaltaCompletar($inicioAnio, $finAnio)->count(),
            ],
        ];

        $query = Hechos::query()
            ->with(['creator', 'unidadOrganizacional', 'delegacion', 'puestaDisposicion'])
            ->withCount(['vehiculosEnCorralon as vehiculos_corralon_count']);

        if ($periodo === 'SEMANA') {
            $query->whereBetween('fecha', [$inicioSemana->toDateString(), $finSemana->toDateString()]);
        } elseif ($periodo === 'MES') {
            $query->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()]);
        } else {
            $query->whereBetween('fecha', [$inicioAnio->toDateString(), $finAnio->toDateString()]);
        }

        $this->applyHechosVisibilityScope($query, $usuario);

        if ($situacion === 'FALTA_COMPLETAR') {
            if ($puedeFiltrarUnidad && $unidadFiltro !== '' && $unidadFiltro !== '2') {
                $query->whereRaw('1=0');
            } else {
                $this->scopeHechosUnidad($query, 2);
                $this->aplicarFiltroCapturaIncompletaDelegaciones($query);
            }
        } elseif ($situacion === 'TODOS') {
            $aplicarFiltroUnidad($query);
        } else {
            $query->where('situacion', $situacion);
            $aplicarFiltroUnidad($query);
        }

        $hechos = $query
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->paginate(20)
            ->withQueryString();

        $hechos->getCollection()->transform(function ($hecho) {
            $unidadReal = HechoAccess::effectiveUnidadIdForHecho($hecho);
            $capturaCompleta = $unidadReal === 2
                ? $hecho->capturaCompletaCalculada()
                : (bool) $hecho->captura_completa;

            $hecho->mostrar_captura = $unidadReal === 2;
            $hecho->captura_completa = $capturaCompleta;
            $hecho->faltantes_captura = $unidadReal === 2 ? $hecho->faltantesCapturaTexto() : [];
            $hecho->estado_captura = $unidadReal === 2
                ? ($capturaCompleta ? 'COMPLETO' : 'INCOMPLETO')
                : null;

            return $hecho;
        });

        return view('hechos.seguimiento', compact(
            'conteos',
            'hechos',
            'periodo',
            'situacion',
            'unidadFiltro',
            'puedeFiltrarUnidad',
            'unidadesFiltro',
            'puedeMostrarTodasSituaciones'
        ));
    }

    public function marcarRelevante(Hechos $hecho)
    {
        $usuario = auth()->user();

        $q = Hechos::query()->whereKey($hecho->id);
        $this->applyHechosVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        if (!$this->userCanMarkRelevante($usuario, $hecho)) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para marcar este hecho como relevante.');
        }

        $hecho->update([
            'es_relevante' => true,
            'marcado_relevante_por' => $usuario->id,
            'marcado_relevante_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Hecho marcado como relevante.');
    }

    public function desmarcarRelevante(Hechos $hecho)
    {
        $usuario = auth()->user();

        $q = Hechos::query()->whereKey($hecho->id);
        $this->applyHechosVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        if (!$this->userCanMarkRelevante($usuario, $hecho)) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para desmarcar este hecho como relevante.');
        }

        $hecho->update([
            'es_relevante' => false,
            'marcado_relevante_por' => null,
            'marcado_relevante_at' => null,
        ]);

        return redirect()->back()->with('success', 'Hecho desmarcado como relevante.');
    }

    private function userCanMarkRelevante($usuario, Hechos $hecho): bool
    {
        if ((int) ($usuario->unidad_id ?? 0) === 3 && !$usuario->hasRole('Superadmin')) {
            return false;
        }

        if (
            $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Administrador')
            || $usuario->hasRole('Subdirector')
        ) {
            $q = Hechos::query()->whereKey($hecho->id);
            $this->applyHechosVisibilityScope($q, $usuario);
            return $q->exists();
        }

        return false;
    }

    public function pendientesRevision(Request $request)
    {
        $usuario = auth()->user();

        $hechosQuery = Hechos::query()
            ->with(['creator', 'revisadoPor', 'marcadoRelevantePor'])
            ->where('estado_revision', 'pendiente')
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->orderByDesc('created_at');

        $this->applyHechosVisibilityScope($hechosQuery, $usuario);

        $hechos = $hechosQuery->paginate(30)->withQueryString();

        return view('hechos.pendientes_revision', compact('hechos'));
    }

    public function countPendientesRevision()
    {
        $usuario = auth()->user();

        $hechosQuery = Hechos::query()
            ->where('estado_revision', 'pendiente');

        $this->applyHechosVisibilityScope($hechosQuery, $usuario);

        return response()->json([
            'count' => $hechosQuery->count(),
        ]);
    }

    public function aprobarRevision(Hechos $hecho)
    {
        $usuario = auth()->user();

        $q = Hechos::query()->whereKey($hecho->id);
        $this->applyHechosVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        if ((int) ($usuario->unidad_id ?? 0) === 3 && !$usuario->hasRole('Superadmin')) {
            return redirect()->route('hechos.show', $hecho->id)->with('error', 'No tienes permiso para revisar este hecho.');
        }

        if (
            !$usuario->hasRole('Superadmin') &&
            !$usuario->hasRole('Administrador') &&
            !$usuario->hasRole('Subdirector') &&
            !$usuario->hasRole('Jefe de Grupo')
        ) {
            return redirect()->route('hechos.show', $hecho->id)->with('error', 'No tienes permiso para revisar este hecho.');
        }

        if ($usuario->hasRole('Jefe de Grupo')) {
            $hecho->loadMissing('creator');

            if (!$hecho->creator || (int)$hecho->creator->turno_id !== (int)$usuario->turno_id) {
                return redirect()->route('hechos.show', $hecho->id)->with('error', 'No puedes revisar hechos de otro turno.');
            }
        }

        if ($hecho->estado_revision !== 'pendiente') {
            return redirect()->route('hechos.show', $hecho->id)->with('info', 'Este hecho ya fue revisado.');
        }

        $hecho->update([
            'estado_revision' => 'aprobado',
            'revisado_por' => $usuario->id,
            'revisado_at' => now(),
        ]);

        return redirect()->route('hechos.show', $hecho->id)->with('success', 'Hecho aprobado correctamente.');
    }

    public function rechazarRevision(Hechos $hecho)
    {
        $usuario = auth()->user();

        $q = Hechos::query()->whereKey($hecho->id);
        $this->applyHechosVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        if ((int) ($usuario->unidad_id ?? 0) === 3 && !$usuario->hasRole('Superadmin')) {
            return redirect()->route('hechos.show', $hecho->id)->with('error', 'No tienes permiso para revisar este hecho.');
        }

        if (
            !$usuario->hasRole('Superadmin') &&
            !$usuario->hasRole('Administrador') &&
            !$usuario->hasRole('Subdirector') &&
            !$usuario->hasRole('Jefe de Grupo')
        ) {
            return redirect()->route('hechos.show', $hecho->id)->with('error', 'No tienes permiso para revisar este hecho.');
        }

        if ($usuario->hasRole('Jefe de Grupo')) {
            $hecho->loadMissing('creator');

            if (!$hecho->creator || (int)$hecho->creator->turno_id !== (int)$usuario->turno_id) {
                return redirect()->route('hechos.show', $hecho->id)->with('error', 'No puedes revisar hechos de otro turno.');
            }
        }

        if ($hecho->estado_revision !== 'pendiente') {
            return redirect()->route('hechos.show', $hecho->id)->with('info', 'Este hecho ya fue revisado.');
        }

        $hecho->update([
            'estado_revision' => 'rechazado',
            'revisado_por' => $usuario->id,
            'revisado_at' => now(),
        ]);

        return redirect()->route('hechos.show', $hecho->id)->with('success', 'Hecho rechazado correctamente.');
    }

    public function compartirNativo(Hechos $hecho)
    {
        $user = auth()->user();

        if (!$user || !$user->can('ver hechos')) {
            abort(403);
        }

        $q = Hechos::query()->whereKey($hecho->id);
        $this->applyHechosVisibilityScope($q, $user);

        if (!$q->exists()) {
            abort(404);
        }

        $hecho->load(['vehiculos']);

        $message = WhatsAppLink::textForHecho($hecho);

        $fechaMostrar = !empty($hecho->fecha)
            ? \Carbon\Carbon::parse($hecho->fecha)->format('Y-m-d')
            : '';

        $horaMostrar = !empty($hecho->hora)
            ? substr((string) $hecho->hora, 0, 5)
            : '';

        $ubicacionCorta = trim((string) ($hecho->calle ?? ''));
        if (!empty($hecho->colonia)) {
            $ubicacionCorta .= ', col. ' . trim((string) $hecho->colonia);
        }

        $message = preg_replace(
            '/\b\d{4}-\d{2}-\d{2}\s+00:00:00\s+\d{2}:\d{2}:\d{2}\s+Hrs\./u',
            trim($fechaMostrar . ' ' . $horaMostrar) . ' Hrs.',
            $message,
            1
        );

        if (!empty($ubicacionCorta)) {
            $message = preg_replace(
                '/Guardia Civil toma conocimiento en .*?\./u',
                'Guardia Civil toma conocimiento en ' . $ubicacionCorta . '.',
                $message,
                1
            );
        }

        $fotos = [];

        if (!empty($hecho->foto_lugar)) {
            $fotos[] = asset('storage/' . ltrim($hecho->foto_lugar, '/'));
        }

        if (!empty($hecho->foto_situacion)) {
            $fotos[] = asset('storage/' . ltrim($hecho->foto_situacion, '/'));
        }

        foreach ($hecho->vehiculos as $v) {
            if (!empty($v->fotos)) {
                $fotos[] = asset('storage/' . ltrim($v->fotos, '/'));
            }
        }

        $fotos = array_values(array_unique(array_filter($fotos)));

        return response()->json([
            'texto' => trim($message),
            'foto' => $fotos[0] ?? null,
            'fotos' => $fotos,
        ]);
    }

    private function userCannotCreateHecho($usuario): bool
    {
        if (!$usuario) {
            return true;
        }

        return (int) ($usuario->unidad_id ?? 0) === 2
            && $usuario->hasRole('Administrativo');
    }

    private function userCanCaptureFechaHora($usuario): bool
    {
        if (!$usuario) {
            return false;
        }

        return $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Administrador')
            || $usuario->hasRole('Subdirector');
    }

    private function userCanUseDictamenes($usuario, ?Hechos $hecho = null): bool
    {
        if (!$usuario) {
            return false;
        }

        $unidadId = $hecho
            ? HechoAccess::effectiveUnidadIdForHecho($hecho)
            : HechoAccess::effectiveUnidadId($usuario);

        return $unidadId === 1;
    }

    private function userCanEditCoordenadasManual($usuario): bool
    {
        if (!$usuario) {
            return false;
        }

        return !$usuario->hasAnyRole(['Delegado', 'Perito', 'Policia', 'Policía']);
    }

    private function usaReglasFlexiblesHechos($usuario, ?Hechos $hecho = null): bool
    {
        $unidadId = $hecho
            ? HechoAccess::effectiveUnidadIdForHecho($hecho)
            : HechoAccess::effectiveUnidadId($usuario);

        return in_array($unidadId, [2, 4], true);
    }

    private function sectorPredeterminadoHechos($usuario, ?Hechos $hecho = null): string
    {
        $unidadId = $hecho
            ? HechoAccess::effectiveUnidadIdForHecho($hecho)
            : HechoAccess::effectiveUnidadId($usuario);

        return $unidadId === 4
            ? 'PROTECCION A CARRETERAS'
            : 'DELEGACIONES';
    }
}
