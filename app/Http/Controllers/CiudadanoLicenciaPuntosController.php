<?php

namespace App\Http\Controllers;

use App\Models\LicenciaPuntoCuenta;
use App\Models\LicenciaPuntoCursoMaterial;
use App\Models\LicenciaPuntoCursoParticipante;
use App\Services\BigBlueButtonService;
use App\Services\LicenciaPuntosService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CiudadanoLicenciaPuntosController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $cuentas = $this->cuentasDelUsuario($user)
            ->withCount(['movimientos'])
            ->orderBy('numero_licencia')
            ->get();

        $participantes = $this->participantesDelUsuario($user)
            ->with(['curso.instructor', 'cuenta'])
            ->limit(8)
            ->get();

        $resumen = $this->resumenGeneral($cuentas);

        return view('ciudadano.licencias_puntos.index', compact('cuentas', 'participantes', 'resumen'));
    }

    public function storeLicencia(Request $request, LicenciaPuntosService $service)
    {
        $validated = $request->validate([
            'numero_licencia' => ['required', 'string', 'max:80'],
            'curp' => ['required', 'string', 'size:18'],
        ]);

        $numero = $service->normalizarLicencia((string) $validated['numero_licencia']);
        $curp = $this->normalizarCurp((string) $validated['curp']);

        $cuenta = LicenciaPuntoCuenta::query()
            ->where('numero_licencia', $numero)
            ->where('curp', $curp)
            ->first();

        if (!$cuenta) {
            throw ValidationException::withMessages([
                'numero_licencia' => 'No encontramos una licencia con esos datos. Revisa el número y la CURP capturada.',
            ]);
        }

        $request->user()->licenciasPuntos()->syncWithoutDetaching([
            $cuenta->id => [
                'verified_at' => now(),
                'last_accessed_at' => now(),
            ],
        ]);

        DB::table('licencia_punto_cuenta_user')
            ->where('user_id', $request->user()->id)
            ->where('cuenta_id', $cuenta->id)
            ->update([
                'verified_at' => now(),
                'last_accessed_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('ciudadano.licencias_puntos.show', $cuenta)
            ->with('success', 'Licencia vinculada correctamente.');
    }

    public function show(Request $request, LicenciaPuntoCuenta $cuenta)
    {
        $this->autorizarCuenta($request, $cuenta);
        $this->registrarAcceso($request, $cuenta);

        $cuenta->load('conductor');

        $movimientos = $cuenta->movimientos()
            ->with('infraccion')
            ->whereNotIn('tipo', ['notificacion_whatsapp'])
            ->orderByDesc('fecha_movimiento')
            ->orderByDesc('id')
            ->paginate(20);

        $participantes = $this->participantesParaCuenta($cuenta)
            ->with(['curso.instructor', 'curso.materiales' => function ($query) {
                $query->where('activo', true)->orderBy('orden')->orderBy('id');
            }, 'movimiento'])
            ->orderByDesc('id')
            ->get();

        $stats = $this->resumenCuenta($cuenta);

        return view('ciudadano.licencias_puntos.show', compact('cuenta', 'movimientos', 'participantes', 'stats'));
    }

    public function destroyLicencia(Request $request, LicenciaPuntoCuenta $cuenta)
    {
        $this->autorizarCuenta($request, $cuenta);
        $request->user()->licenciasPuntos()->detach($cuenta->id);

        return redirect()
            ->route('ciudadano.licencias_puntos.index')
            ->with('success', 'Licencia desvinculada de tu cuenta.');
    }

    public function cursos(Request $request)
    {
        $participantes = $this->participantesDelUsuario($request->user())
            ->with(['curso.instructor', 'curso.materiales' => function ($query) {
                $query->where('activo', true)->orderBy('orden')->orderBy('id');
            }, 'cuenta', 'movimiento'])
            ->paginate(15);

        return view('ciudadano.licencias_puntos.cursos', compact('participantes'));
    }

    public function aula(Request $request, LicenciaPuntoCursoParticipante $participante, BigBlueButtonService $bbb)
    {
        $this->autorizarParticipante($request, $participante);
        $participante->loadMissing('curso');

        $curso = $participante->curso;
        abort_unless($curso && $curso->clase_en_vivo && $curso->bbb_create_time, 404);

        try {
            return redirect()->away($bbb->attendeeJoinUrl($curso, $participante));
        } catch (RuntimeException $e) {
            abort(409, $e->getMessage());
        }
    }

    private function cuentasDelUsuario($user)
    {
        return $user->licenciasPuntos();
    }

    private function participantesDelUsuario($user)
    {
        $cuentas = $this->cuentasDelUsuario($user)->get(['licencia_punto_cuentas.id', 'licencia_punto_cuentas.numero_licencia']);

        return LicenciaPuntoCursoParticipante::query()
            ->select('licencia_punto_curso_participantes.*')
            ->join('licencia_punto_cursos', 'licencia_punto_cursos.id', '=', 'licencia_punto_curso_participantes.curso_id')
            ->where(function ($query) use ($cuentas) {
                $ids = $cuentas->pluck('id')->all();
                $numeros = $cuentas->pluck('numero_licencia')->filter()->all();

                if ($ids) {
                    $query->whereIn('licencia_punto_curso_participantes.cuenta_id', $ids);
                }

                if ($numeros) {
                    $method = $ids ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('licencia_punto_curso_participantes.numero_licencia', $numeros);
                }

                if (!$ids && !$numeros) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->orderByDesc('licencia_punto_cursos.fecha_inicio')
            ->orderByDesc('licencia_punto_curso_participantes.id');
    }

    private function participantesParaCuenta(LicenciaPuntoCuenta $cuenta)
    {
        return LicenciaPuntoCursoParticipante::query()
            ->where(function ($query) use ($cuenta) {
                $query->where('cuenta_id', $cuenta->id)
                    ->orWhere('numero_licencia', $cuenta->numero_licencia);
            });
    }

    private function autorizarCuenta(Request $request, LicenciaPuntoCuenta $cuenta): void
    {
        abort_unless(
            $request->user()
            && $this->cuentasDelUsuario($request->user())->whereKey($cuenta->id)->exists(),
            404
        );
    }

    private function autorizarParticipante(Request $request, LicenciaPuntoCursoParticipante $participante): void
    {
        $cuentas = $this->cuentasDelUsuario($request->user())->get(['licencia_punto_cuentas.id', 'licencia_punto_cuentas.numero_licencia']);
        $ids = $cuentas->pluck('id')->map(fn ($id) => (int) $id)->all();
        $numeros = $cuentas->pluck('numero_licencia')->filter()->all();

        abort_unless(
            in_array((int) $participante->cuenta_id, $ids, true)
            || in_array((string) $participante->numero_licencia, $numeros, true),
            404
        );
    }

    private function registrarAcceso(Request $request, LicenciaPuntoCuenta $cuenta): void
    {
        DB::table('licencia_punto_cuenta_user')
            ->where('user_id', $request->user()->id)
            ->where('cuenta_id', $cuenta->id)
            ->update([
                'last_accessed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function resumenGeneral($cuentas): array
    {
        return [
            'licencias' => $cuentas->count(),
            'saldo_total' => $cuentas->sum(fn ($cuenta) => (int) $cuenta->saldo_actual),
            'saldo_maximo' => $cuentas->count() * LicenciaPuntoCuenta::SALDO_MAXIMO,
            'en_alerta' => $cuentas->filter(fn ($cuenta) => in_array($cuenta->nivel_saldo, ['advertencia', 'critico', 'agotado'], true))->count(),
        ];
    }

    private function resumenCuenta(LicenciaPuntoCuenta $cuenta): array
    {
        $perdidos = (int) abs($cuenta->movimientos()->where('puntos', '<', 0)->sum('puntos'));
        $ganados = (int) $cuenta->movimientos()->where('puntos', '>', 0)->sum('puntos');
        $recuperacion = $cuenta->fecha_recuperacion;

        return [
            'perdidos' => $perdidos,
            'ganados' => $ganados,
            'recuperacion' => $recuperacion,
            'dias_recuperacion' => $recuperacion
                ? max(0, Carbon::now('America/Mexico_City')->startOfDay()->diffInDays($recuperacion->copy()->startOfDay(), false))
                : null,
        ];
    }

    private function normalizarCurp(string $curp): string
    {
        return preg_replace('/\s+/', '', mb_strtoupper(trim($curp), 'UTF-8')) ?: '';
    }

    public static function aulaUrlSiDisponible(LicenciaPuntoCursoParticipante $participante): ?string
    {
        $curso = $participante->curso;

        if (!$curso || !$curso->clase_en_vivo || !$curso->bbb_create_time) {
            return null;
        }

        return route('ciudadano.licencias_puntos.cursos.aula', $participante);
    }

    public static function materialUrl(LicenciaPuntoCursoMaterial $material): ?string
    {
        if (!$material->activo) {
            return null;
        }

        if ($material->tipo === LicenciaPuntoCursoMaterial::TIPO_PDF) {
            return $material->archivo_url;
        }

        if ($material->tipo === LicenciaPuntoCursoMaterial::TIPO_LINK) {
            return $material->url;
        }

        return null;
    }
}
