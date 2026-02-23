<?php

namespace App\Http\Controllers;

use App\Models\Hechos;
use App\Models\Dictamen;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\Unidad;
use App\Helpers\StreetNormalizer;

use App\Services\WhatsApp\WhatsAppBot;
use App\Services\WhatsApp\WhatsAppLink;

class HechosController extends Controller
{
    public function index(Request $request)
    {
        $tz = 'America/Mexico_City';

        $fechaSeleccionada = (string) $request->query('fecha', now($tz)->toDateString());

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSeleccionada)) {
            $fechaSeleccionada = now($tz)->toDateString();
        }

        $hechos = Hechos::query()
            ->whereDate('fecha', $fechaSeleccionada)
            ->orderByDesc('hora')
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

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

        $unidadNombre = null;
        if (!empty($usuario->unidad_id)) {
            $u = Unidad::query()->find($usuario->unidad_id);
            $unidadNombre = $u ? $u->nombre : null;
        }

        $validated = $request->validate([
            'folio_c5i' => 'required|string|max:20|unique:hechos,folio_c5i',
            'perito' => 'required|string|max:255',
            'autorizacion_practico' => 'nullable|string|max:255',
            'unidad' => 'required|string|max:50',
            'hora' => 'required|date_format:H:i',
            'fecha' => 'required|date',
            'sector' => 'required|string|in:REVOLUCIÓN,NUEVA ESPAÑA,INDEPENDENCIA,REPÚBLICA,CENTRO',
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
            'colision_camino' => 'required|string|max:255',
            'situacion' => 'required|string|in:RESUELTO,PENDIENTE,TURNADO,REPORTE',
            'oficio_mp' => 'nullable|string|max:255|required_if:situacion,TURNADO',
            'vehiculos_mp' => 'required|integer|min:0',
            'personas_mp' => 'required|integer|min:0',

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

        $validated['checaron_antecedentes'] = $request->has('checaron_antecedentes');

        $situacion = (string)($validated['situacion'] ?? '');
        if (in_array($situacion, ['RESUELTO'], true)) {
            $request->validate([
                'foto_situacion' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            ]);
        }

        $validated['delegacion_id'] = $usuario->delegacion_id ?? null;
        $validated['created_by'] = $usuario->id;
        $validated['unidad_org_id'] = $usuario->unidad_id ?? null;

        if (!empty($unidadNombre)) {
            $validated['unidad'] = $unidadNombre;
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

            if (!empty($dictamen->hecho_id) && (int)$dictamen->hecho_id !== (int)$hecho->id) {
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
        $hecho->load([
            'vehiculos',
            'vehiculos.servicios',
            'dictamen',
        ]);

        return view('hechos.show', compact('hecho'));
    }

    public function edit(Hechos $hecho)
    {
        $usuario = auth()->user();

        if (
            $usuario->id !== $hecho->created_by
            && !$usuario->hasRole('Administrador')
            && !$usuario->hasRole('Superadmin')
            && !$usuario->hasRole('Administrativo')
        ) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para editar este hecho.');
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

        if (
            $usuario->id !== $hecho->created_by
            && !$usuario->hasRole('Administrador')
            && !$usuario->hasRole('Superadmin')
            && !$usuario->hasRole('Administrativo')
        ) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para editar este hecho.');
        }

        $validated = $request->validate([
            'folio_c5i' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hechos')->ignore($hecho->id),
            ],
            'perito' => 'required|string|max:255',
            'autorizacion_practico' => 'nullable|string|max:255',
            'unidad' => 'required|string|max:50',

            'hora' => 'required|date_format:H:i',
            'fecha' => 'required|date',
            'sector' => 'required|string|in:REVOLUCIÓN,NUEVA ESPAÑA,INDEPENDENCIA,REPÚBLICA,CENTRO',
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
            'colision_camino' => 'required|string|max:255',
            'situacion' => 'required|string|in:RESUELTO,PENDIENTE,TURNADO,REPORTE',
            'oficio_mp' => 'nullable|string|max:255|required_if:situacion,TURNADO',
            'vehiculos_mp' => 'required|integer|min:0',
            'personas_mp' => 'required|integer|min:0',

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

        $validated['checaron_antecedentes'] = $request->has('checaron_antecedentes');

        $situacion = (string)($validated['situacion'] ?? '');
        if (in_array($situacion, ['RESUELTO'], true)) {
            $request->validate([
                'foto_situacion' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            ]);
        }

        $validated['updated_by'] = $usuario->id;

        if (!empty($hecho->unidad_org_id) && empty($validated['unidad_org_id'])) {
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
            if ($dictamenActual && (int)$dictamenActual->id !== (int)$dictamenId) {
                $dictamenActual->hecho_id = null;
                $dictamenActual->save();
            }

            $nuevo = Dictamen::query()->findOrFail($dictamenId);

            if (!empty($nuevo->hecho_id) && (int)$nuevo->hecho_id !== (int)$hecho->id) {
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

        if (!$usuario->hasRole('Administrador') && !$usuario->hasRole('Superadmin')) {
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

        $message = WhatsAppLink::textForHecho($hecho);

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
}
