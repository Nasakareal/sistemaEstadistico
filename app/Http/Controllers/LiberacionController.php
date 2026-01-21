<?php

namespace App\Http\Controllers;

use App\Models\Liberacion;
use App\Models\Vehiculo;
use App\Models\Hechos;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

class LiberacionController extends Controller
{
    public function publica(Vehiculo $vehiculo)
    {
        $liberacion = Liberacion::where('vehiculo_id', $vehiculo->id)->first();

        if (auth()->check()) {
            if (auth()->user()->area === 'Grúas') {
                return redirect()->route('liberacion.grua.ver', $vehiculo->id);
            } else {
                if ($liberacion) {
                    return redirect()->route('liberacion.detalles', $vehiculo->id);
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

        $qrPath = public_path($liberacion->qr_path);

        if (!file_exists($qrPath)) {
            \Log::error('QR no encontrado. Campo qr_path: ' . $liberacion->qr_path);
            abort(500, 'El archivo QR no se encuentra o es inválido.');
        }

        $qrBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($qrPath));

        $hecho = $liberacion->hecho;

        $pdf = Pdf::loadView('liberaciones.acuse_pdf', [
            'vehiculo'   => $vehiculo,
            'liberacion' => $liberacion,
            'qrBase64'   => $qrBase64,
            'hecho'      => $hecho
        ]);

        return $pdf->download('acuse_liberacion_vehiculo_' . $vehiculo->id . '.pdf');
    }

    public function create(Vehiculo $vehiculo)
    {
        $fechaActual = Carbon::now()->format('Y-m-d');

        return view('liberaciones.create', compact('vehiculo', 'fechaActual'));
    }

    public function store(Request $request, Vehiculo $vehiculo)
    {
        if (is_null($vehiculo->corralon)) {
            return redirect()->back()->withErrors(['corralon' => 'No se puede liberar un vehículo que no está en resguardo (corralón).']);
        }

        $request->validate([
            'fecha_liberacion' => 'required|date',
            'personas_autorizadas' => 'required|string',
            'autoriza' => 'required|string',
            'motivo_liberacion' => 'required|string',
        ]);

        $anio = Carbon::parse($request->fecha_liberacion)->year;

        $folio = Liberacion::whereYear('fecha_liberacion', $anio)->count() + 1;
        $folioFormateado = str_pad($folio, 3, '0', STR_PAD_LEFT);
        $folioAnual = "{$folioFormateado}/{$anio}";

        $hecho = $vehiculo->hechos()->first();
        $hechoId = $hecho ? $hecho->id : null;

        $liberacion = Liberacion::create([
            'vehiculo_id' => $vehiculo->id,
            'hecho_id' => $hechoId,
            'token_unico' => Str::uuid(),
            'fecha_liberacion' => $request->fecha_liberacion,
            'personas_autorizadas' => $request->personas_autorizadas,
            'autoriza' => $request->autoriza,
            'motivo_liberacion' => $request->motivo_liberacion,
            'folio_anual' => $folioAnual,
            'creado_por' => Auth::id(),
        ]);

        return redirect()->route('liberacion.detalles', $vehiculo->id)->with('success', 'Liberación registrada correctamente.');
    }

    public function edit(Vehiculo $vehiculo)
    {
        $liberacion = Liberacion::where('vehiculo_id', $vehiculo->id)->firstOrFail();
        return view('liberaciones.edit', compact('vehiculo', 'liberacion'));
    }

    public function update(Request $request, Vehiculo $vehiculo)
    {
        if (is_null($vehiculo->corralon)) {
            return redirect()->back()->withErrors(['corralon' => 'No se puede liberar un vehículo que no está en resguardo (corralón).']);
        }

        $request->validate([
            'fecha_liberacion' => 'required|date',
            'personas_autorizadas' => 'required|string',
            'autoriza' => 'required|string',
            'motivo_liberacion' => 'required|string',
        ]);

        $liberacion = Liberacion::where('vehiculo_id', $vehiculo->id)->firstOrFail();
        $liberacion->fecha_liberacion = $request->fecha_liberacion;
        $liberacion->personas_autorizadas = $request->personas_autorizadas;
        $liberacion->autoriza = $request->autoriza;
        $liberacion->motivo_liberacion = $request->motivo_liberacion;
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
}
