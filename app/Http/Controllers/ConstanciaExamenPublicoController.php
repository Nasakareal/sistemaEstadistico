<?php

namespace App\Http\Controllers;

use App\Models\ConstanciaExamen;
use App\Models\ConstanciaExamenRespuesta;
use App\Models\ConstanciaExamenSolicitud;
use App\Models\ConstanciaManejo;
use App\Models\ConstanciaPregunta;
use App\Models\ConstanciaRespuesta;
use App\Services\ConstanciaExamenCuestionarioService;
use Carbon\Carbon;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConstanciaExamenPublicoController extends Controller
{
    private const TOTAL_PREGUNTAS = ConstanciaExamenCuestionarioService::TOTAL_PREGUNTAS;

    private $cuestionarios;

    public function __construct(ConstanciaExamenCuestionarioService $cuestionarios)
    {
        $this->cuestionarios = $cuestionarios;
    }

    public function escrito($token)
    {
        $solicitud = ConstanciaExamenSolicitud::with('modulo')
            ->where('token', $token)
            ->where('modalidad', 'IMPRESO')
            ->first();

        if ($solicitud) {
            return $this->imprimirExamenEscritoSolicitud($solicitud, $token);
        }

        $constancia = ConstanciaManejo::where('acceso_examen_token', $token)
            ->where('tipo_examen', 'IMPRESO')
            ->firstOrFail();

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'Esta constancia no está disponible para examen escrito.',
            ]);
        }

        if (!$constancia->acceso_examen_expira || Carbon::now('America/Mexico_City')->greaterThan($constancia->acceso_examen_expira)) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'El QR del examen escrito ya expiró.',
            ]);
        }

        if (!$constancia->nombre_solicitante || !$constancia->sexo || !$constancia->tipo_licencia) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'El evaluador debe capturar nombre completo, sexo y tipo de licencia antes de imprimir el examen escrito.',
            ]);
        }

        $preguntas = $this->preguntasPara($constancia->tipo_licencia, $token);

        if ($preguntas->count() < self::TOTAL_PREGUNTAS) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'No hay 20 preguntas activas para este tipo de licencia.',
            ]);
        }

        $tipoLicenciaLabel = str_replace('_', ' ', $constancia->tipo_licencia);
        $logoSrc = $this->imageDataUri(public_path('img/blanco.png')) ?? asset('img/blanco.png');
        $qrUrl = url('/constancias-manejo/examen-escrito/' . $token);
        $qrBase64 = $this->qrDataUri($qrUrl);

        return view('constancia_preguntas.imprimir', [
            'preguntas' => $preguntas,
            'tipoLicencia' => $constancia->tipo_licencia,
            'tipoLicenciaLabel' => $tipoLicenciaLabel,
            'logoSrc' => $logoSrc,
            'constancia' => $constancia,
            'qrUrl' => $qrUrl,
            'qrBase64' => $qrBase64,
        ]);
    }

    public function iniciar($token)
    {
        $solicitud = ConstanciaExamenSolicitud::where('token', $token)->first();

        if ($solicitud) {
            return $this->iniciarSolicitud($solicitud, $token);
        }

        $constancia = ConstanciaManejo::where('acceso_examen_token', $token)->firstOrFail();

        if ($constancia->tipo_examen === 'IMPRESO') {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'Este QR corresponde a un examen escrito. Escanéalo desde la app para capturar el resultado.',
            ]);
        }

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'Esta constancia no está disponible para examen.',
            ]);
        }

        if (!$constancia->acceso_examen_expira || Carbon::now('America/Mexico_City')->greaterThan($constancia->acceso_examen_expira)) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'El acceso temporal al examen ya expiró.',
            ]);
        }

        if ($constancia->examen && $constancia->examen->resultado === 'APROBADO') {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'Este examen ya fue aprobado. Espere la activación del perito examinador.',
            ]);
        }

        if (!$constancia->nombre_solicitante || !$constancia->sexo || !$constancia->tipo_licencia) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'El perito examinador debe capturar nombre completo, sexo y tipo de licencia antes de iniciar el examen.',
            ]);
        }

        $preguntas = ConstanciaPregunta::with('respuestas')
            ->where('activo', true)
            ->where(function ($query) use ($constancia) {
                $query->where('tipo_licencia', $constancia->tipo_licencia)
                    ->orWhere('tipo_licencia', 'GENERAL');
            })
            ->inRandomOrder()
            ->limit(self::TOTAL_PREGUNTAS)
            ->get();

        if ($preguntas->count() < self::TOTAL_PREGUNTAS) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'No hay 20 preguntas activas para este tipo de licencia. Solicita que se capture completo el banco de preguntas.',
            ]);
        }

        return view('constancias_manejo.examen.iniciar', compact('constancia', 'preguntas', 'token'));
    }

    public function guardar(Request $request, $token)
    {
        $solicitud = ConstanciaExamenSolicitud::where('token', $token)->first();

        if ($solicitud) {
            return $this->guardarSolicitud($request, $solicitud, $token);
        }

        $constancia = ConstanciaManejo::where('acceso_examen_token', $token)->firstOrFail();

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return redirect()->route('constancias_manejo.examen.iniciar', $token);
        }

        if (!$constancia->acceso_examen_expira || Carbon::now('America/Mexico_City')->greaterThan($constancia->acceso_examen_expira)) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'El acceso temporal al examen ya expiró.',
            ]);
        }

        $request->validate([
            'preguntas' => ['required', 'array'],
            'preguntas.*' => ['required', 'integer', 'exists:constancia_preguntas,id'],
            'respuestas' => ['required', 'array'],
            'respuestas.*' => ['required', 'integer', 'exists:constancia_respuestas,id'],
        ]);

        $resultado = DB::transaction(function () use ($request, $constancia) {
            $preguntaIds = array_values(array_unique(array_map('intval', $request->input('preguntas', []))));

            $preguntas = ConstanciaPregunta::with('respuestas')
                ->whereIn('id', $preguntaIds)
                ->where('activo', true)
                ->where(function ($query) use ($constancia) {
                    $query->where('tipo_licencia', $constancia->tipo_licencia)
                        ->orWhere('tipo_licencia', 'GENERAL');
                })
                ->get();

            if ($preguntas->count() !== count($preguntaIds)) {
                throw ValidationException::withMessages([
                    'preguntas' => 'El examen contiene preguntas no disponibles.',
                ]);
            }

            $respuestasPorPregunta = collect($request->input('respuestas', []));
            $respuestasIds = [];

            foreach ($preguntas as $pregunta) {
                $respuestaId = (int) $respuestasPorPregunta->get($pregunta->id);

                if (!$respuestaId) {
                    throw ValidationException::withMessages([
                        'respuestas' => 'Contesta todas las preguntas.',
                    ]);
                }

                $respuestasIds[] = $respuestaId;
            }

            $respuestas = ConstanciaRespuesta::with('pregunta')
                ->whereIn('id', $respuestasIds)
                ->get()
                ->keyBy('id');

            foreach ($preguntas as $pregunta) {
                $respuestaId = (int) $respuestasPorPregunta->get($pregunta->id);
                $respuesta = $respuestas->get($respuestaId);

                if (!$respuesta || (int) $respuesta->pregunta_id !== (int) $pregunta->id) {
                    throw ValidationException::withMessages([
                        'respuestas' => 'Una respuesta no corresponde a su pregunta.',
                    ]);
                }
            }

            $total = $preguntas->count();
            $aciertos = $respuestas->where('es_correcta', true)->count();
            $errores = $total - $aciertos;
            $calificacion = $total > 0 ? round(($aciertos / $total) * 100, 2) : 0;
            $resultado = $calificacion >= 80 ? 'APROBADO' : 'REPROBADO';

            $examen = ConstanciaExamen::updateOrCreate(
                [
                    'constancia_id' => $constancia->id,
                ],
                [
                    'modalidad' => 'LINEA',
                    'calificacion' => $calificacion,
                    'total_preguntas' => $total,
                    'aciertos' => $aciertos,
                    'errores' => $errores,
                    'resultado' => $resultado,
                    'capturado_por' => null,
                    'fecha_examen' => Carbon::now('America/Mexico_City'),
                    'observaciones' => null,
                ]
            );

            ConstanciaExamenRespuesta::where('constancia_examen_id', $examen->id)->delete();

            foreach ($respuestas as $respuesta) {
                ConstanciaExamenRespuesta::create([
                    'constancia_examen_id' => $examen->id,
                    'pregunta_id' => $respuesta->pregunta_id,
                    'respuesta_id' => $respuesta->id,
                    'es_correcta' => $respuesta->es_correcta,
                ]);
            }

            $constancia->update([
                'tipo_examen' => 'LINEA',
                'acceso_examen_token' => $resultado === 'APROBADO' ? null : $constancia->acceso_examen_token,
                'acceso_examen_expira' => $resultado === 'APROBADO' ? null : $constancia->acceso_examen_expira,
            ]);

            return [
                'examen' => $examen,
                'resultado' => $resultado,
            ];
        });

        return view('constancias_manejo.examen.resultado', [
            'constancia' => $constancia,
            'examen' => $resultado['examen'],
        ]);
    }

    private function imprimirExamenEscritoSolicitud(ConstanciaExamenSolicitud $solicitud, string $token)
    {
        if ($solicitud->constancia_id) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'Este examen ya fue asignado a una constancia.',
            ]);
        }

        if (!$solicitud->token_expira || Carbon::now('America/Mexico_City')->greaterThan($solicitud->token_expira)) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'El QR del examen escrito ya expiró.',
            ]);
        }

        if (!$solicitud->nombre_solicitante || !$solicitud->sexo || !$solicitud->tipo_licencia) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'El evaluador debe capturar nombre completo, sexo y tipo de licencia antes de imprimir el examen escrito.',
            ]);
        }

        $preguntas = $this->preguntasPara($solicitud->tipo_licencia, $token);

        if ($preguntas->count() < self::TOTAL_PREGUNTAS) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'No hay 20 preguntas activas para este tipo de licencia.',
            ]);
        }

        $qrUrl = url('/constancias-manejo/examen-escrito/' . $token);

        return view('constancia_preguntas.imprimir', [
            'preguntas' => $preguntas,
            'tipoLicencia' => $solicitud->tipo_licencia,
            'tipoLicenciaLabel' => str_replace('_', ' ', $solicitud->tipo_licencia),
            'logoSrc' => $this->imageDataUri(public_path('img/blanco.png')) ?? asset('img/blanco.png'),
            'constancia' => $solicitud,
            'qrUrl' => $qrUrl,
            'qrBase64' => $this->qrDataUri($qrUrl),
        ]);
    }

    private function iniciarSolicitud(ConstanciaExamenSolicitud $solicitud, string $token)
    {
        if ($solicitud->modalidad === 'IMPRESO') {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'Este QR corresponde a un examen escrito. Escanéalo desde la app para capturar el resultado.',
            ]);
        }

        if ($solicitud->constancia_id) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'Este examen ya fue asignado a una constancia.',
            ]);
        }

        if (!$solicitud->token_expira || Carbon::now('America/Mexico_City')->greaterThan($solicitud->token_expira)) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'El acceso temporal al examen ya expiró.',
            ]);
        }

        if ($solicitud->estatus === 'APROBADO') {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'Este examen ya fue aprobado. Espere la activación del perito examinador.',
            ]);
        }

        if (!$solicitud->nombre_solicitante || !$solicitud->sexo || !$solicitud->tipo_licencia) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'El perito examinador debe capturar nombre completo, sexo y tipo de licencia antes de iniciar el examen.',
            ]);
        }

        $preguntas = $this->preguntasPara($solicitud->tipo_licencia, $token);

        if ($preguntas->count() < self::TOTAL_PREGUNTAS) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'No hay 20 preguntas activas para este tipo de licencia. Solicita que se capture completo el banco de preguntas.',
            ]);
        }

        return view('constancias_manejo.examen.iniciar', [
            'constancia' => $solicitud,
            'preguntas' => $preguntas,
            'token' => $token,
        ]);
    }

    private function guardarSolicitud(Request $request, ConstanciaExamenSolicitud $solicitud, string $token)
    {
        if ($solicitud->constancia_id || $solicitud->modalidad === 'IMPRESO') {
            return redirect()->route('constancias_manejo.examen.iniciar', $token);
        }

        if (!$solicitud->token_expira || Carbon::now('America/Mexico_City')->greaterThan($solicitud->token_expira)) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'El acceso temporal al examen ya expiró.',
            ]);
        }

        $request->validate([
            'preguntas' => ['required', 'array'],
            'preguntas.*' => ['required', 'integer', 'exists:constancia_preguntas,id'],
            'respuestas' => ['required', 'array'],
            'respuestas.*' => ['required', 'integer', 'exists:constancia_respuestas,id'],
        ]);

        $resultado = DB::transaction(function () use ($request, $solicitud) {
            $preguntaIds = array_values(array_unique(array_map('intval', $request->input('preguntas', []))));

            $preguntas = ConstanciaPregunta::with('respuestas')
                ->whereIn('id', $preguntaIds)
                ->where('activo', true)
                ->where(function ($query) use ($solicitud) {
                    $query->where('tipo_licencia', $solicitud->tipo_licencia)
                        ->orWhere('tipo_licencia', 'GENERAL');
                })
                ->get();

            if ($preguntas->count() !== count($preguntaIds)) {
                throw ValidationException::withMessages([
                    'preguntas' => 'El examen contiene preguntas no disponibles.',
                ]);
            }

            $respuestasPorPregunta = collect($request->input('respuestas', []));
            $respuestasIds = [];

            foreach ($preguntas as $pregunta) {
                $respuestaId = (int) $respuestasPorPregunta->get($pregunta->id);

                if (!$respuestaId) {
                    throw ValidationException::withMessages([
                        'respuestas' => 'Contesta todas las preguntas.',
                    ]);
                }

                $respuestasIds[] = $respuestaId;
            }

            $respuestas = ConstanciaRespuesta::with('pregunta')
                ->whereIn('id', $respuestasIds)
                ->get()
                ->keyBy('id');

            foreach ($preguntas as $pregunta) {
                $respuestaId = (int) $respuestasPorPregunta->get($pregunta->id);
                $respuesta = $respuestas->get($respuestaId);

                if (!$respuesta || (int) $respuesta->pregunta_id !== (int) $pregunta->id) {
                    throw ValidationException::withMessages([
                        'respuestas' => 'Una respuesta no corresponde a su pregunta.',
                    ]);
                }
            }

            $total = $preguntas->count();
            $aciertos = $respuestas->where('es_correcta', true)->count();
            $errores = $total - $aciertos;
            $calificacion = $total > 0 ? round(($aciertos / $total) * 100, 2) : 0;
            $estatus = $calificacion >= 80 ? 'APROBADO' : 'REPROBADO';

            $solicitud->update([
                'modalidad' => 'LINEA',
                'calificacion' => $calificacion,
                'total_preguntas' => $total,
                'aciertos' => $aciertos,
                'errores' => $errores,
                'estatus' => $estatus,
                'fecha_examen' => Carbon::now('America/Mexico_City'),
            ]);

            return (object) [
                'modalidad' => 'LINEA',
                'calificacion' => $calificacion,
                'total_preguntas' => $total,
                'aciertos' => $aciertos,
                'errores' => $errores,
                'resultado' => $estatus,
                'fecha_examen' => $solicitud->fecha_examen,
                'observaciones' => null,
            ];
        });

        return view('constancias_manejo.examen.resultado', [
            'constancia' => $solicitud,
            'examen' => $resultado,
        ]);
    }

    private function preguntasPara(string $tipoLicencia, string $semilla)
    {
        return $this->cuestionarios->generar($tipoLicencia, $semilla);
    }

    private function qrDataUri(string $url): string
    {
        $qrCode = QrCode::create($url)
            ->setSize(180)
            ->setMargin(8)
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->setForegroundColor(new Color(15, 23, 42))
            ->setBackgroundColor(new Color(255, 255, 255));

        return 'data:image/png;base64,' . base64_encode((new PngWriter())->write($qrCode)->getString());
    }

    private function imageDataUri(string $path): ?string
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';
        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }
}


