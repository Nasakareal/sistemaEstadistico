<?php

namespace App\Http\Controllers;

use App\Models\ConstanciaPregunta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConstanciaPreguntaController extends Controller
{
    public const TIPOS_LICENCIA = [
        'GENERAL' => 'General',
        'MOTOCICLISTA' => 'Motociclista',
        'AUTOMOVILISTA' => 'Automovilista',
        'CHOFER' => 'Chofer',
        'SERVICIO_PUBLICO' => 'Servicio Público',
        'PERMISO' => 'Permiso',
    ];

    public const TIPOS_EXAMEN_DESCARGABLES = [
        'MOTOCICLISTA' => 'Motociclista',
        'AUTOMOVILISTA' => 'Automovilista',
        'CHOFER' => 'Chofer',
        'SERVICIO_PUBLICO' => 'Servicio Público',
        'PERMISO' => 'Permiso',
    ];

    public function descargas()
    {
        $conteosPorTipo = ConstanciaPregunta::query()
            ->where('activo', true)
            ->selectRaw('tipo_licencia, COUNT(*) as total')
            ->groupBy('tipo_licencia')
            ->pluck('total', 'tipo_licencia');

        $preguntasGenerales = (int) $conteosPorTipo->get('GENERAL', 0);
        $tiposExamen = collect(self::TIPOS_EXAMEN_DESCARGABLES)
            ->map(fn ($label, $tipo) => [
                'tipo' => $tipo,
                'label' => $label,
                'total' => $preguntasGenerales + (int) $conteosPorTipo->get($tipo, 0),
            ])
            ->values();

        return view('constancia_preguntas.descargas', compact('tiposExamen'));
    }

    public function descargar(string $tipoLicencia)
    {
        abort_unless(array_key_exists($tipoLicencia, self::TIPOS_EXAMEN_DESCARGABLES), 404);

        $preguntas = $this->preguntasParaTipo($tipoLicencia);

        if ($preguntas->count() < 20) {
            return redirect()
                ->route('constancias_manejo.preguntas.descargas')
                ->with('error', 'No se puede descargar el examen de '
                    . self::TIPOS_EXAMEN_DESCARGABLES[$tipoLicencia]
                    . ': sólo hay ' . $preguntas->count() . ' preguntas activas de las 20 necesarias.');
        }

        $tipoLicenciaLabel = self::TIPOS_EXAMEN_DESCARGABLES[$tipoLicencia];
        $logoSrc = $this->imageDataUri(public_path('img/blanco.png')) ?? asset('img/blanco.png');

        return Pdf::loadView('constancia_preguntas.imprimir', [
                'preguntas' => $preguntas,
                'tipoLicencia' => $tipoLicencia,
                'tipoLicenciaLabel' => $tipoLicenciaLabel,
                'logoSrc' => $logoSrc,
                'modoPdf' => true,
            ])
            ->setPaper('letter')
            ->download('examen_' . Str::slug($tipoLicenciaLabel, '_') . '.pdf');
    }

    public function index(Request $request)
    {
        $tipo = $request->query('tipo_licencia');
        $activo = $request->query('activo');

        $query = ConstanciaPregunta::with(['respuestas' => function ($query) {
                $query->orderBy('id');
            }])
            ->withCount('respuestas')
            ->orderBy('tipo_licencia')
            ->orderByDesc('activo')
            ->orderByDesc('id');

        if (array_key_exists($tipo, self::TIPOS_LICENCIA)) {
            $query->where('tipo_licencia', $tipo);
        }

        if ($activo === '1' || $activo === '0') {
            $query->where('activo', (bool) $activo);
        }

        $preguntas = $query->get();

        $conteos = ConstanciaPregunta::query()
            ->where('activo', true)
            ->selectRaw('tipo_licencia, COUNT(*) as total')
            ->groupBy('tipo_licencia')
            ->pluck('total', 'tipo_licencia');

        return view('constancia_preguntas.index', [
            'preguntas' => $preguntas,
            'tiposLicencia' => self::TIPOS_LICENCIA,
            'conteos' => $conteos,
            'tipoSeleccionado' => $tipo,
            'activoSeleccionado' => $activo,
        ]);
    }

    public function create(Request $request)
    {
        $tipo = $request->query('tipo_licencia', 'GENERAL');

        if (!array_key_exists($tipo, self::TIPOS_LICENCIA)) {
            $tipo = 'GENERAL';
        }

        return view('constancia_preguntas.create', [
            'pregunta' => new ConstanciaPregunta([
                'tipo_licencia' => $tipo,
                'activo' => true,
            ]),
            'tiposLicencia' => self::TIPOS_LICENCIA,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $pregunta = DB::transaction(function () use ($data) {
            $pregunta = ConstanciaPregunta::create([
                'pregunta' => $data['pregunta'],
                'tipo_licencia' => $data['tipo_licencia'],
                'activo' => $data['activo'],
            ]);

            $pregunta->respuestas()->createMany($data['respuestas']);

            return $pregunta;
        });

        return redirect()
            ->route('constancias_manejo.preguntas.edit', $pregunta)
            ->with('success', 'Pregunta guardada correctamente.');
    }

    public function edit(ConstanciaPregunta $pregunta)
    {
        $pregunta->load(['respuestas' => function ($query) {
            $query->orderBy('id');
        }]);

        return view('constancia_preguntas.edit', [
            'pregunta' => $pregunta,
            'tiposLicencia' => self::TIPOS_LICENCIA,
        ]);
    }

    public function update(Request $request, ConstanciaPregunta $pregunta)
    {
        $data = $this->validatedData($request);

        DB::transaction(function () use ($pregunta, $data) {
            $pregunta->update([
                'pregunta' => $data['pregunta'],
                'tipo_licencia' => $data['tipo_licencia'],
                'activo' => $data['activo'],
            ]);

            $pregunta->respuestas()->delete();
            $pregunta->respuestas()->createMany($data['respuestas']);
        });

        return redirect()
            ->route('constancias_manejo.preguntas.edit', $pregunta)
            ->with('success', 'Pregunta actualizada correctamente.');
    }

    public function destroy(ConstanciaPregunta $pregunta)
    {
        DB::transaction(function () use ($pregunta) {
            $pregunta->respuestas()->delete();
            $pregunta->delete();
        });

        return redirect()
            ->route('constancias_manejo.preguntas.index')
            ->with('success', 'Pregunta eliminada correctamente.');
    }

    public function imprimir(Request $request)
    {
        $validated = $request->validate([
            'tipo_licencia' => ['required', Rule::in(array_keys(self::TIPOS_LICENCIA))],
        ]);

        $tipoLicencia = $validated['tipo_licencia'];

        $preguntas = $this->preguntasParaTipo($tipoLicencia);

        $logoSrc = $this->imageDataUri(public_path('img/blanco.png')) ?? asset('img/blanco.png');

        return view('constancia_preguntas.imprimir', [
            'preguntas' => $preguntas,
            'tipoLicencia' => $tipoLicencia,
            'tipoLicenciaLabel' => self::TIPOS_LICENCIA[$tipoLicencia],
            'logoSrc' => $logoSrc,
        ]);
    }

    private function preguntasParaTipo(string $tipoLicencia)
    {
        return ConstanciaPregunta::with(['respuestas' => function ($query) {
                $query->orderBy('id');
            }])
            ->where('activo', true)
            ->where(function ($query) use ($tipoLicencia) {
                $query->where('tipo_licencia', $tipoLicencia)
                    ->orWhere('tipo_licencia', 'GENERAL');
            })
            ->inRandomOrder()
            ->limit(20)
            ->get();
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

    private function validatedData(Request $request): array
    {
        $request->validate([
            'pregunta' => ['required', 'string', 'max:2000'],
            'tipo_licencia' => ['required', Rule::in(array_keys(self::TIPOS_LICENCIA))],
            'activo' => ['nullable', 'boolean'],
            'respuestas' => ['required', 'array'],
            'respuesta_correcta' => ['required', 'integer', 'min:0'],
        ]);

        $respuestas = [];
        foreach ($request->input('respuestas', []) as $index => $respuesta) {
            $texto = trim((string) data_get($respuesta, 'respuesta', ''));

            if ($texto === '') {
                continue;
            }

            $respuestas[] = [
                'respuesta' => $texto,
                'es_correcta' => (int) $index === (int) $request->input('respuesta_correcta'),
            ];
        }

        if (count($respuestas) < 2) {
            throw ValidationException::withMessages([
                'respuestas' => 'Captura al menos dos respuestas.',
            ]);
        }

        if (!collect($respuestas)->contains('es_correcta', true)) {
            throw ValidationException::withMessages([
                'respuesta_correcta' => 'Selecciona una respuesta correcta capturada.',
            ]);
        }

        return [
            'pregunta' => trim((string) $request->input('pregunta')),
            'tipo_licencia' => $request->input('tipo_licencia'),
            'activo' => $request->boolean('activo'),
            'respuestas' => $respuestas,
        ];
    }
}


