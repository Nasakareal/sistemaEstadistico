<?php

namespace App\Http\Controllers;

use App\Models\ConstanciaActivacion;
use App\Models\ConstanciaExamen;
use App\Models\ConstanciaExamenSolicitud;
use App\Models\ConstanciaManejo;
use App\Models\ConstanciaModulo;
use App\Services\ConstanciaExamenCuestionarioService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConstanciaExamenSolicitudController extends Controller
{
    private const TIPOS_LICENCIA = [
        'SERVICIO_PUBLICO' => 'Servicio público',
        'AUTOMOVILISTA' => 'Automovilista',
        'CHOFER' => 'Chofer',
        'MOTOCICLISTA' => 'Motociclista',
        'PERMISO' => 'Permiso',
    ];

    private $cuestionarios;

    public function __construct(ConstanciaExamenCuestionarioService $cuestionarios)
    {
        $this->cuestionarios = $cuestionarios;
    }

    public function index(Request $request)
    {
        $query = $this->querySolicitudesDisponibles()
            ->with(['modulo', 'constancia'])
            ->orderByDesc('id');

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->estatus);
        }

        if ($request->filled('modalidad')) {
            $query->where('modalidad', $request->modalidad);
        }

        if ($request->boolean('sin_constancia')) {
            $query->whereNull('constancia_id');
        }

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->buscar);
            $query->where(function ($q) use ($buscar) {
                $q->where('folio_examen', 'like', "%{$buscar}%")
                    ->orWhere('nombre_solicitante', 'like', "%{$buscar}%")
                    ->orWhere('curp', 'like', "%{$buscar}%");
            });
        }

        $solicitudes = $query->paginate(25)->appends($request->query());

        return view('constancias_manejo.examenes.index', [
            'solicitudes' => $solicitudes,
            'tiposLicencia' => self::TIPOS_LICENCIA,
        ]);
    }

    public function create()
    {
        return view('constancias_manejo.examenes.create', [
            'modulos' => $this->queryModulosDisponibles()->get(),
            'tiposLicencia' => self::TIPOS_LICENCIA,
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'sexo' => $this->normalizarSexo($request->input('sexo')),
            'modalidad' => strtoupper((string) $request->input('modalidad', 'LINEA')),
        ]);

        $validated = $request->validate([
            'modulo_id' => ['required', 'integer', 'exists:constancia_modulos,id'],
            'nombre_solicitante' => ['required', 'string', 'max:255'],
            'sexo' => ['required', 'in:HOMBRE,MUJER'],
            'curp' => ['nullable', 'string', 'max:18'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'tipo_licencia' => ['required', 'in:' . implode(',', array_keys(self::TIPOS_LICENCIA))],
            'modalidad' => ['required', 'in:LINEA,IMPRESO'],
        ]);

        $modulo = $this->queryModulosDisponibles()
            ->where('id', $validated['modulo_id'])
            ->first();

        if (!$modulo) {
            return redirect()
                ->route('constancias_manejo.examenes.create')
                ->withInput()
                ->with('error', 'No tienes permiso para generar exámenes en ese módulo.');
        }

        $solicitud = DB::transaction(function () use ($validated, $modulo) {
            $idPreview = (int) (ConstanciaExamenSolicitud::lockForUpdate()->max('id') ?? 0) + 1;

            return ConstanciaExamenSolicitud::create([
                'folio_examen' => 'EX-' . str_pad((string) $idPreview, 6, '0', STR_PAD_LEFT),
                'token' => Str::random(60),
                'modulo_id' => $modulo->id,
                'delegacion_id' => $modulo->delegacion_id,
                'user_id' => auth()->id(),
                'constancia_id' => null,
                'nombre_solicitante' => mb_strtoupper($validated['nombre_solicitante'], 'UTF-8'),
                'sexo' => $validated['sexo'],
                'curp' => !empty($validated['curp']) ? mb_strtoupper($validated['curp'], 'UTF-8') : null,
                'telefono' => $validated['telefono'] ?? null,
                'tipo_licencia' => $validated['tipo_licencia'],
                'modalidad' => $validated['modalidad'],
                'estatus' => 'PENDIENTE',
                'token_expira' => $validated['modalidad'] === 'LINEA'
                    ? Carbon::now('America/Mexico_City')->addMinutes(30)
                    : Carbon::now('America/Mexico_City')->endOfDay(),
            ]);
        });

        return redirect()
            ->route('constancias_manejo.examenes.show', $solicitud)
            ->with('success', 'Examen generado sin consumir constancia.');
    }

    public function show(ConstanciaExamenSolicitud $solicitud)
    {
        $this->authorizeSolicitud($solicitud);

        $solicitud->load(['modulo', 'constancia']);

        $constanciasDisponibles = $this->queryConstanciasDisponibles()
            ->where('estatus', 'IMPRESA_INACTIVA')
            ->whereDoesntHave('examen')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $urlExamen = $this->examenSolicitudUrl($solicitud);
        $tokenVigente = $this->tokenVigente($solicitud);

        return view('constancias_manejo.examenes.show', [
            'solicitud' => $solicitud,
            'constanciasDisponibles' => $constanciasDisponibles,
            'tiposLicencia' => self::TIPOS_LICENCIA,
            'urlExamen' => $urlExamen,
            'qrBase64' => $tokenVigente ? $this->qrDataUri($urlExamen) : null,
            'tokenVigente' => $tokenVigente,
        ]);
    }

    public function descargarPdf(ConstanciaExamenSolicitud $solicitud)
    {
        $this->authorizeSolicitud($solicitud);

        abort_unless($solicitud->modalidad === 'IMPRESO', 404);

        $solicitud->load(['modulo', 'constancia']);
        $preguntas = $this->cuestionarios->generar($solicitud->tipo_licencia, $solicitud->token);

        if ($preguntas->count() < ConstanciaExamenCuestionarioService::TOTAL_PREGUNTAS) {
            return redirect()
                ->route('constancias_manejo.examenes.show', $solicitud)
                ->with('error', 'No hay 20 preguntas activas para este tipo de licencia.');
        }

        $qrUrl = $this->examenSolicitudUrl($solicitud);
        $nombreArchivo = 'examen-' . $solicitud->folio_examen . '.pdf';

        return Pdf::loadView('constancia_preguntas.imprimir', [
                'preguntas' => $preguntas,
                'tipoLicencia' => $solicitud->tipo_licencia,
                'tipoLicenciaLabel' => self::TIPOS_LICENCIA[$solicitud->tipo_licencia] ?? str_replace('_', ' ', $solicitud->tipo_licencia),
                'logoSrc' => $this->imageDataUri(public_path('img/blanco.png')) ?? asset('img/blanco.png'),
                'constancia' => $solicitud,
                'qrUrl' => $qrUrl,
                'qrBase64' => $this->qrDataUri($qrUrl),
                'modoPdf' => true,
            ])
            ->setPaper('letter')
            ->download($nombreArchivo);
    }

    public function capturarImpreso(Request $request, ConstanciaExamenSolicitud $solicitud)
    {
        $this->authorizeSolicitud($solicitud);

        if ($solicitud->modalidad !== 'IMPRESO') {
            return redirect()->route('constancias_manejo.examenes.show', $solicitud)
                ->with('error', 'Este registro no corresponde a examen escrito.');
        }

        if ($solicitud->constancia_id) {
            return redirect()->route('constancias_manejo.examenes.show', $solicitud)
                ->with('error', 'Este examen ya fue asignado a una constancia.');
        }

        $validated = $request->validate([
            'total_preguntas' => ['required', 'integer', 'min:1'],
            'aciertos' => ['required', 'integer', 'min:0'],
            'errores' => ['required', 'integer', 'min:0'],
            'observaciones' => ['nullable', 'string'],
        ]);

        if ((int) $validated['aciertos'] + (int) $validated['errores'] !== (int) $validated['total_preguntas']) {
            return redirect()->route('constancias_manejo.examenes.show', $solicitud)
                ->withInput()
                ->with('error', 'Aciertos y errores no coinciden con el total de preguntas.');
        }

        $total = (int) $validated['total_preguntas'];
        $aciertos = (int) $validated['aciertos'];
        $errores = (int) $validated['errores'];
        $calificacion = round(($aciertos / $total) * 100, 2);

        $solicitud->update([
            'calificacion' => $calificacion,
            'total_preguntas' => $total,
            'aciertos' => $aciertos,
            'errores' => $errores,
            'estatus' => $calificacion >= 80 ? 'APROBADO' : 'REPROBADO',
            'fecha_examen' => Carbon::now('America/Mexico_City'),
            'observaciones' => $validated['observaciones'] ?? null,
        ]);

        return redirect()
            ->route('constancias_manejo.examenes.show', $solicitud)
            ->with('success', 'Resultado de examen escrito guardado.');
    }

    public function activar(Request $request, ConstanciaExamenSolicitud $solicitud)
    {
        $this->authorizeSolicitud($solicitud);

        if ($solicitud->estatus !== 'APROBADO') {
            return redirect()->route('constancias_manejo.examenes.show', $solicitud)
                ->with('error', 'Solo se puede activar una constancia con examen aprobado.');
        }

        if ($solicitud->constancia_id) {
            return redirect()->route('constancias_manejo.examenes.show', $solicitud)
                ->with('error', 'Este examen ya fue asignado a una constancia.');
        }

        $validated = $request->validate([
            'constancia_id' => ['nullable', 'integer', 'exists:constancias_manejo,id'],
            'constancia_qr' => ['nullable', 'string', 'max:500'],
        ]);

        $constancia = null;

        if (!empty($validated['constancia_id'])) {
            $constancia = ConstanciaManejo::find((int) $validated['constancia_id']);
        }

        if (!$constancia && !empty($validated['constancia_qr'])) {
            $token = $this->cleanQrToken($validated['constancia_qr']);
            $constancia = ConstanciaManejo::where('qr_token', $token)->first();
        }

        if (!$constancia) {
            return redirect()->route('constancias_manejo.examenes.show', $solicitud)
                ->withInput()
                ->with('error', 'Constancia impresa no encontrada.');
        }

        $this->authorizeConstancia($constancia);

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return redirect()->route('constancias_manejo.examenes.show', $solicitud)
                ->with('error', 'La constancia impresa no está inactiva.');
        }

        if ($constancia->examen()->exists()) {
            return redirect()->route('constancias_manejo.examenes.show', $solicitud)
                ->with('error', 'Esa constancia ya tiene un examen relacionado.');
        }

        $ahora = Carbon::now('America/Mexico_City');

        DB::transaction(function () use ($constancia, $solicitud, $ahora) {
            $constancia->update([
                'nombre_solicitante' => $solicitud->nombre_solicitante,
                'sexo' => $solicitud->sexo,
                'curp' => $solicitud->curp,
                'telefono' => $solicitud->telefono,
                'tipo_licencia' => $solicitud->tipo_licencia,
                'tipo_examen' => $solicitud->modalidad,
                'estatus' => 'ACTIVA',
                'perito_activador_id' => auth()->id(),
                'fecha_activacion' => $ahora,
                'fecha_expiracion' => $ahora->copy()->addDays(10),
                'acceso_examen_token' => null,
                'acceso_examen_expira' => null,
            ]);

            ConstanciaExamen::create([
                'constancia_id' => $constancia->id,
                'modalidad' => $solicitud->modalidad,
                'calificacion' => $solicitud->calificacion,
                'total_preguntas' => $solicitud->total_preguntas,
                'aciertos' => $solicitud->aciertos,
                'errores' => $solicitud->errores,
                'resultado' => 'APROBADO',
                'capturado_por' => auth()->id(),
                'fecha_examen' => $solicitud->fecha_examen ?: $ahora,
                'observaciones' => $solicitud->observaciones,
            ]);

            $solicitud->update([
                'constancia_id' => $constancia->id,
            ]);

            ConstanciaActivacion::create([
                'constancia_id' => $constancia->id,
                'user_id' => auth()->id(),
                'accion' => 'ACTIVADA',
                'fecha' => $ahora,
                'observaciones' => 'Activada con examen ' . $solicitud->folio_examen,
            ]);
        });

        return redirect()
            ->route('constancias_manejo.show', $constancia)
            ->with('success', 'Constancia activada con examen ' . $solicitud->folio_examen . '.');
    }

    private function querySolicitudesDisponibles()
    {
        return ConstanciaExamenSolicitud::query()
            ->whereIn('modulo_id', $this->queryModulosDisponibles()->pluck('id'));
    }

    private function queryConstanciasDisponibles()
    {
        return ConstanciaManejo::query()
            ->whereIn('modulo_id', $this->queryModulosDisponibles()->pluck('id'));
    }

    private function authorizeSolicitud(ConstanciaExamenSolicitud $solicitud): void
    {
        abort_unless(
            $this->querySolicitudesDisponibles()->whereKey($solicitud->id)->exists(),
            403,
            'No tienes permiso para ver este examen.'
        );
    }

    private function authorizeConstancia(ConstanciaManejo $constancia): void
    {
        abort_unless(
            $this->queryConstanciasDisponibles()->whereKey($constancia->id)->exists(),
            403,
            'No tienes permiso para usar esta constancia.'
        );
    }

    private function queryModulosDisponibles()
    {
        $usuario = auth()->user();

        $query = ConstanciaModulo::where('activo', true)->orderBy('nombre');

        if (!$usuario) {
            return $query->whereRaw('1 = 0');
        }

        if ($usuario->hasRole('Superadmin')) {
            return $query;
        }

        $unidadIds = $this->unidadIdsUsuario($usuario);
        $delegacionIds = $this->delegacionIdsUsuario($usuario);
        $puedeSiniestros = in_array(1, $unidadIds, true);
        $puedeDelegacion = in_array(2, $unidadIds, true) && count($delegacionIds) > 0;

        if (!$puedeSiniestros && !$puedeDelegacion) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($puedeSiniestros, $puedeDelegacion, $delegacionIds) {
            if ($puedeSiniestros) {
                $q->orWhere('tipo', 'SINIESTROS');
            }

            if ($puedeDelegacion) {
                $q->orWhere(function ($delegacion) use ($delegacionIds) {
                    $delegacion->where('tipo', 'DELEGACION')
                        ->whereIn('delegacion_id', $delegacionIds);
                });
            }
        });
    }

    private function unidadIdsUsuario($usuario): array
    {
        return array_values(array_filter([(int) ($usuario->unidad_id ?? 0)]));
    }

    private function delegacionIdsUsuario($usuario): array
    {
        $ids = [(int) ($usuario->delegacion_id ?? 0)];

        try {
            $ids = array_merge(
                $ids,
                DB::table('delegacion_user')
                    ->where('user_id', $usuario->id)
                    ->pluck('delegacion_id')
                    ->map(fn ($id) => (int) $id)
                    ->all()
            );
        } catch (\Throwable $e) {
            // La delegación principal basta en instalaciones sin pivote sincronizado.
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function tokenVigente(ConstanciaExamenSolicitud $solicitud): bool
    {
        return (bool) (
            $solicitud->token
            && $solicitud->token_expira
            && Carbon::now('America/Mexico_City')->lessThanOrEqualTo($solicitud->token_expira)
            && !$solicitud->constancia_id
            && $solicitud->estatus === 'PENDIENTE'
        );
    }

    private function examenSolicitudUrl(ConstanciaExamenSolicitud $solicitud): string
    {
        $path = $solicitud->modalidad === 'IMPRESO'
            ? '/constancias-manejo/examen-escrito/'
            : '/constancias-manejo/examen/';

        return url($path . $solicitud->token);
    }

    private function qrDataUri(string $url): string
    {
        $qrCode = QrCode::create($url)
            ->setSize(210)
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

    private function cleanQrToken(string $raw): string
    {
        $value = trim($raw);

        if ($value === '') {
            return '';
        }

        $path = parse_url($value, PHP_URL_PATH);

        if (is_string($path) && $path !== '') {
            $parts = array_values(array_filter(explode('/', trim($path, '/'))));

            if (count($parts) > 0) {
                return trim(urldecode(end($parts)));
            }
        }

        if (strpos($value, ':') !== false) {
            return trim(substr($value, strrpos($value, ':') + 1));
        }

        return $value;
    }

    private function normalizarSexo($sexo): ?string
    {
        $value = mb_strtoupper(trim((string) $sexo), 'UTF-8');

        if (in_array($value, ['H', 'HOMBRE', 'MASCULINO'], true)) {
            return 'HOMBRE';
        }

        if (in_array($value, ['M', 'MUJER', 'FEMENINO'], true)) {
            return 'MUJER';
        }

        return $value ?: null;
    }
}
