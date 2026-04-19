<?php

namespace App\Http\Controllers;

use App\Models\Hechos;
use App\Models\Dictamen;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\Unidad;
use App\Helpers\StreetNormalizer;
use App\Models\Delegacion;
use App\Services\HechoRevisionNotificationService;
use App\Services\WhatsApp\WhatsAppBot;
use App\Services\WhatsApp\WhatsAppLink;
use App\Services\WhatsApp\C5IReport;
use App\Services\WhatsApp\NearestUnit;

class HechosController extends Controller
{
    public function index(Request $request)
    {
        $tz = 'America/Mexico_City';

        $fechaSeleccionada = (string) $request->query('fecha', now($tz)->toDateString());

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSeleccionada)) {
            $fechaSeleccionada = now($tz)->toDateString();
        }

        $usuario = auth()->user();

        $hechosQuery = Hechos::query()
            ->with(['revisadoPor', 'marcadoRelevantePor', 'croquis'])
            ->withCount('lesionados')
            ->whereDate('fecha', $fechaSeleccionada);

        $this->applyHechosVisibilityScope($hechosQuery, $usuario);

        $hechos = $hechosQuery
            ->orderByDesc('hora')
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $hechos->getCollection()->transform(function ($hecho) use ($usuario) {
            $hecho->puede_editar = $this->userCanEditHecho($usuario, $hecho);
            $hecho->tiene_croquis = !is_null($hecho->croquis);
            return $hecho;
        });

        return view('hechos.index', compact('hechos', 'fechaSeleccionada'));
    }

    public function create()
    {
        $dictamenesDisponibles = Dictamen::query()
            ->whereNull('hecho_id')
            ->orderByDesc('anio')
            ->orderByDesc('numero_dictamen')
            ->get();

        return view('hechos.create', compact('dictamenesDisponibles'));
    }

    public function store(Request $request)
    {
        $usuario = auth()->user();

        $esDelegaciones = (int) ($usuario->unidad_id ?? 0) === 2;

        $reglaFolio = $esDelegaciones
            ? 'nullable|string|max:20|unique:hechos,folio_c5i'
            : 'required|string|max:20|unique:hechos,folio_c5i';

        $reglaSector = $esDelegaciones
            ? 'nullable|string|max:100'
            : 'required|string|in:REVOLUCIÓN,NUEVA ESPAÑA,INDEPENDENCIA,REPÚBLICA,CENTRO';

        $validated = $request->validate([
            'folio_c5i' => $reglaFolio,
            'perito' => 'required|string|max:255',
            'autorizacion_practico' => 'nullable|string|max:255',
            'unidad' => 'required|string|max:50',
            'hora' => $usuario->hasRole('Perito') ? 'nullable' : 'required|date_format:H:i',
            'fecha' => 'required|date',
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
            'oficio_mp' => 'nullable|string|max:255|required_if:situacion,TURNADO',
            'vehiculos_mp' => 'required|integer|min:0',
            'personas_mp' => 'required|integer|min:0',
            'danos_patrimoniales' => 'nullable|boolean',
            'propiedades_afectadas' => 'nullable|string|max:255',
            'monto_danos_patrimoniales' => 'nullable|numeric|min:0',
            'dictamen_id' => 'nullable|required_if:situacion,TURNADO|exists:dictamens,id',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'calidad_geo' => 'nullable|string|max:20',
            'nota_geo' => 'nullable|string|max:1000',
            'fuente_ubicacion' => 'nullable|string|max:20',
            'ubicacion_formateada' => 'nullable|string|max:2000',
            'place_id' => 'nullable|string|max:128',
            'foto_lugar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'foto_situacion' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if (empty($validated['folio_c5i'])) {
            $validated['folio_c5i'] = null;
        }

        if ($esDelegaciones && empty($validated['sector'])) {
            $validated['sector'] = 'DELEGACIONES';
        }

        $validated['checaron_antecedentes'] = $request->has('checaron_antecedentes');
        $validated['danos_patrimoniales'] = $request->has('danos_patrimoniales');

        if (!$validated['danos_patrimoniales']) {
            $validated['propiedades_afectadas'] = null;
            $validated['monto_danos_patrimoniales'] = null;
        }

        $situacion = (string)($validated['situacion'] ?? '');
        if (!$esDelegaciones && $situacion === 'RESUELTO') {
            $request->validate([
                'foto_situacion' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            ]);
        }

        $validated['delegacion_id'] = $usuario->delegacion_id ?? null;
        $validated['created_by'] = $usuario->id;
        $validated['unidad_org_id'] = $usuario->unidad_id ?? null;
        $validated['estado_revision'] = 'pendiente';
        $validated['revisado_por'] = null;
        $validated['revisado_at'] = null;
        $validated['observacion_revision'] = null;

        if ($usuario->hasRole('Perito')) {
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

        $dictamenId = $validated['dictamen_id'] ?? null;
        unset($validated['dictamen_id']);

        $hecho = Hechos::create($validated);

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
            'revisadoPor',
            'marcadoRelevantePor',
            'croquis',
        ]);

        $puedeEditar = $this->userCanEditHecho($usuario, $hecho);
        $croquisData = $this->croquisData($hecho->croquis);

        return view('hechos.show', compact('hecho', 'puedeEditar', 'croquisData'));
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

        if (empty($hecho->unidad_org_id) && !empty($usuario->unidad_id)) {
            $updatesSellado['unidad_org_id'] = $usuario->unidad_id;
        }

        if (empty($hecho->delegacion_id) && !empty($usuario->delegacion_id)) {
            $updatesSellado['delegacion_id'] = $usuario->delegacion_id;
        }

        if (!empty($updatesSellado)) {
            $hecho->update($updatesSellado);
            $hecho->refresh();
        }

        $dictamenActual = $hecho->dictamen;

        $dictamenesDisponibles = Dictamen::query()
            ->whereNull('hecho_id')
            ->orderByDesc('anio')
            ->orderByDesc('numero_dictamen')
            ->get();

        if ($dictamenActual) {
            $dictamenesDisponibles = $dictamenesDisponibles->prepend($dictamenActual);
        }

        $dictamenLabel = null;
        if ($dictamenActual) {
            $dictamenLabel = $dictamenActual->numero_dictamen . '/' . $dictamenActual->anio . ' ' . $dictamenActual->nombre_mp;
        }

        return view('hechos.edit', compact('hecho', 'dictamenesDisponibles', 'dictamenActual', 'dictamenLabel'));
    }

    public function update(Request $request, Hechos $hecho)
    {
        $usuario = auth()->user();

        if (!$this->userCanEditHecho($usuario, $hecho)) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para editar este hecho.');
        }

        if (empty($hecho->unidad_org_id) && !empty($usuario->unidad_id)) {
            $hecho->update(['unidad_org_id' => $usuario->unidad_id]);
            $hecho->refresh();
        }

        if (empty($hecho->delegacion_id) && !empty($usuario->delegacion_id)) {
            $hecho->update(['delegacion_id' => $usuario->delegacion_id]);
            $hecho->refresh();
        }

        $quitarFotoLugar = (string) $request->input('quitar_foto_lugar', '0') === '1';
        $quitarFotoSituacion = (string) $request->input('quitar_foto_situacion', '0') === '1';

        $esDelegaciones = (int) ($usuario->unidad_id ?? 0) === 2;

        $reglaFolio = $esDelegaciones
            ? [
                'nullable',
                'string',
                'max:20',
                Rule::unique('hechos', 'folio_c5i')->ignore($hecho->id),
            ]
            : [
                'required',
                'string',
                'max:20',
                Rule::unique('hechos', 'folio_c5i')->ignore($hecho->id),
            ];

        $reglaSector = $esDelegaciones
            ? 'nullable|string|max:100'
            : 'required|string|in:REVOLUCIÓN,NUEVA ESPAÑA,INDEPENDENCIA,REPÚBLICA,CENTRO';

        $validated = $request->validate([
            'folio_c5i' => $reglaFolio,
            'perito' => 'required|string|max:255',
            'autorizacion_practico' => 'nullable|string|max:255',
            'unidad' => 'required|string|max:50',
            'hora' => $usuario->hasRole('Perito') ? 'nullable' : 'required|date_format:H:i',
            'fecha' => 'required|date',
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
            'oficio_mp' => 'nullable|string|max:255|required_if:situacion,TURNADO',
            'vehiculos_mp' => 'required|integer|min:0',
            'personas_mp' => 'required|integer|min:0',
            'danos_patrimoniales' => 'nullable|boolean',
            'propiedades_afectadas' => 'nullable|string|max:255',
            'monto_danos_patrimoniales' => 'nullable|numeric|min:0',
            'dictamen_id' => 'nullable|required_if:situacion,TURNADO|exists:dictamens,id',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'calidad_geo' => 'nullable|string|max:20',
            'nota_geo' => 'nullable|string|max:1000',
            'fuente_ubicacion' => 'nullable|string|max:20',
            'ubicacion_formateada' => 'nullable|string|max:2000',
            'place_id' => 'nullable|string|max:128',
            'foto_lugar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'foto_situacion' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if (empty($validated['folio_c5i'])) {
            $validated['folio_c5i'] = null;
        }

        if ($esDelegaciones && empty($validated['sector'])) {
            $validated['sector'] = 'DELEGACIONES';
        }

        $validated['checaron_antecedentes'] = $request->has('checaron_antecedentes');
        $validated['danos_patrimoniales'] = $request->has('danos_patrimoniales');

        if (!$validated['danos_patrimoniales']) {
            $validated['propiedades_afectadas'] = null;
            $validated['monto_danos_patrimoniales'] = null;
        }

        $situacion = (string) ($validated['situacion'] ?? '');

        if (!$esDelegaciones && $situacion === 'RESUELTO') {
            $hayFotoGuardada = !empty($hecho->foto_situacion) && !$quitarFotoSituacion;

            if (!$hayFotoGuardada && !$request->hasFile('foto_situacion')) {
                return back()
                    ->withErrors(['foto_situacion' => 'La foto de la situación es obligatoria cuando la situación es RESUELTO.'])
                    ->withInput();
            }
        }

        $validated['updated_by'] = $usuario->id;

        if ($usuario->hasRole('Perito')) {
            $validated['hora'] = !empty($hecho->hora)
                ? substr((string) $hecho->hora, 0, 5)
                : substr((string) $hecho->created_at, 11, 5);
        }

        if (empty($hecho->unidad_org_id) && !empty($usuario->unidad_id)) {
            $validated['unidad_org_id'] = $usuario->unidad_id;
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

        $dictamenId = $validated['dictamen_id'] ?? null;
        unset($validated['dictamen_id']);

        $hecho->update($validated);

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

        return redirect()->route('hechos.index')->with('success', 'Hecho actualizado exitosamente.');
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

    private function applyHechosVisibilityScope($query, $usuario): void
    {
        if (!$usuario) {
            $query->whereRaw('1=0');
            return;
        }

        if ($usuario->hasRole('Superadmin')) {
            return;
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        if ($unidadId === 3) {
            return;
        }

        if ($unidadId === 4) {
            $query->where('unidad_org_id', 4);
            return;
        }

        if ($unidadId === 2) {
            $delegacionId = (int) ($usuario->delegacion_id ?? 0);

            if ($delegacionId <= 0) {
                $query->whereRaw('1=0');
                return;
            }

            $esRegional = Delegacion::query()
                ->where('id', $delegacionId)
                ->whereNull('delegacion_padre_id')
                ->exists();

            if ($usuario->hasRole('Subdirector')) {
                if ($esRegional) {
                    $ids = Delegacion::query()
                        ->where('id', $delegacionId)
                        ->orWhere('delegacion_padre_id', $delegacionId)
                        ->pluck('id')
                        ->toArray();

                    $query->whereIn('delegacion_id', $ids);
                } else {
                    $query->where('delegacion_id', $delegacionId);
                }
            } else {
                $query->where('delegacion_id', $delegacionId);
            }

            return;
        }

        if ($unidadId === 1) {
            $query->where('unidad_org_id', 1);
            return;
        }

        if ($unidadId > 0) {
            $query->where('unidad_org_id', $unidadId);
            return;
        }

        $query->whereRaw('1=0');
    }

    private function userCanEditHecho($usuario, Hechos $hecho): bool
    {
        if (!$usuario) {
            return false;
        }

        $q = Hechos::query()->whereKey($hecho->id);
        $this->applyHechosVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            return false;
        }

        if ($usuario->hasRole('Superadmin')) {
            return true;
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        if ($unidadId === 3) {
            return false;
        }

        if (
            $usuario->hasRole('Administrador')
            || $usuario->hasRole('Administrativo')
            || $usuario->hasRole('Jefe de Grupo')
            || $usuario->hasRole('Subdirector')
        ) {
            return true;
        }

        if ($usuario->hasRole('Perito')) {
            $nombreUsuario = strtoupper($this->removeAccents(trim((string) ($usuario->name ?? ''))));
            $nombrePeritoHecho = strtoupper($this->removeAccents(trim((string) ($hecho->perito ?? ''))));

            return $nombreUsuario !== '' && $nombreUsuario === $nombrePeritoHecho;
        }

        if ((int) $usuario->id === (int) $hecho->created_by) {
            return true;
        }

        return false;
    }

    public function seguimiento(Request $request)
    {
        $usuario = auth()->user();

        $periodo = strtoupper($request->get('periodo', 'SEMANA'));
        $situacion = strtoupper($request->get('situacion', 'PENDIENTE'));

        $situacionesValidas = ['PENDIENTE', 'TURNADO', 'RESUELTO'];
        $periodosValidos = ['SEMANA', 'MES', 'ANIO'];

        if (!in_array($situacion, $situacionesValidas)) {
            $situacion = 'PENDIENTE';
        }

        if (!in_array($periodo, $periodosValidos)) {
            $periodo = 'SEMANA';
        }

        $hoy = now();

        $inicioSemana = $hoy->copy()->startOfWeek();
        $finSemana = $hoy->copy()->endOfWeek();

        $inicioMes = $hoy->copy()->startOfMonth();
        $finMes = $hoy->copy()->endOfMonth();

        $inicioAnio = $hoy->copy()->startOfYear();
        $finAnio = $hoy->copy()->endOfYear();

        $querySemanaPendiente = Hechos::query()->whereBetween('fecha', [$inicioSemana->toDateString(), $finSemana->toDateString()])->where('situacion', 'PENDIENTE');
        $this->applyHechosVisibilityScope($querySemanaPendiente, $usuario);

        $querySemanaTurnado = Hechos::query()->whereBetween('fecha', [$inicioSemana->toDateString(), $finSemana->toDateString()])->where('situacion', 'TURNADO');
        $this->applyHechosVisibilityScope($querySemanaTurnado, $usuario);

        $querySemanaResuelto = Hechos::query()->whereBetween('fecha', [$inicioSemana->toDateString(), $finSemana->toDateString()])->where('situacion', 'RESUELTO');
        $this->applyHechosVisibilityScope($querySemanaResuelto, $usuario);

        $queryMesPendiente = Hechos::query()->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()])->where('situacion', 'PENDIENTE');
        $this->applyHechosVisibilityScope($queryMesPendiente, $usuario);

        $queryMesTurnado = Hechos::query()->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()])->where('situacion', 'TURNADO');
        $this->applyHechosVisibilityScope($queryMesTurnado, $usuario);

        $queryMesResuelto = Hechos::query()->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()])->where('situacion', 'RESUELTO');
        $this->applyHechosVisibilityScope($queryMesResuelto, $usuario);

        $queryAnioPendiente = Hechos::query()->whereBetween('fecha', [$inicioAnio->toDateString(), $finAnio->toDateString()])->where('situacion', 'PENDIENTE');
        $this->applyHechosVisibilityScope($queryAnioPendiente, $usuario);

        $queryAnioTurnado = Hechos::query()->whereBetween('fecha', [$inicioAnio->toDateString(), $finAnio->toDateString()])->where('situacion', 'TURNADO');
        $this->applyHechosVisibilityScope($queryAnioTurnado, $usuario);

        $queryAnioResuelto = Hechos::query()->whereBetween('fecha', [$inicioAnio->toDateString(), $finAnio->toDateString()])->where('situacion', 'RESUELTO');
        $this->applyHechosVisibilityScope($queryAnioResuelto, $usuario);

        $conteos = [
            'semana' => [
                'PENDIENTE' => $querySemanaPendiente->count(),
                'TURNADO' => $querySemanaTurnado->count(),
                'RESUELTO' => $querySemanaResuelto->count(),
            ],
            'mes' => [
                'PENDIENTE' => $queryMesPendiente->count(),
                'TURNADO' => $queryMesTurnado->count(),
                'RESUELTO' => $queryMesResuelto->count(),
            ],
            'anio' => [
                'PENDIENTE' => $queryAnioPendiente->count(),
                'TURNADO' => $queryAnioTurnado->count(),
                'RESUELTO' => $queryAnioResuelto->count(),
            ],
        ];

        $query = Hechos::query();

        if ($periodo === 'SEMANA') {
            $query->whereBetween('fecha', [$inicioSemana->toDateString(), $finSemana->toDateString()]);
        } elseif ($periodo === 'MES') {
            $query->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()]);
        } else {
            $query->whereBetween('fecha', [$inicioAnio->toDateString(), $finAnio->toDateString()]);
        }

        $query->where('situacion', $situacion);

        $this->applyHechosVisibilityScope($query, $usuario);

        $hechos = $query->orderByDesc('fecha')->orderByDesc('hora')->paginate(20)->withQueryString();

        return view('hechos.seguimiento', compact(
            'conteos',
            'hechos',
            'periodo',
            'situacion'
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
}
