<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class LicenciaPuntosTurnoAccessService
{
    private TurnoService $turnos;

    public function __construct(TurnoService $turnos)
    {
        $this->turnos = $turnos;
    }

    public function statusForUser(User $user, ?Carbon $momento = null): array
    {
        $momento = ($momento ? $momento->copy() : now('America/Mexico_City'))
            ->timezone('America/Mexico_City');

        $user->loadMissing(['unidad', 'turno', 'roles']);

        $turnoUsuario = $this->turnoMeta($user->turno);
        $turnoEnServicio = $this->turnoMeta($this->turnos->turnoActivoEn($momento));

        if (!$this->isUnidadSiniestros($user)) {
            return $this->status(true, false, 'not_siniestros', null, $turnoUsuario, $turnoEnServicio, $momento);
        }

        if ($user->hasRole('Subdirector') || $this->turnoSiemprePermitido($user->turno)) {
            return $this->status(true, true, 'turno_siempre', true, $turnoUsuario, $turnoEnServicio, $momento);
        }

        if (!$user->turno) {
            return $this->status(false, true, 'sin_turno', false, $turnoUsuario, $turnoEnServicio, $momento);
        }

        if (!$this->turnoResoluble($user->turno)) {
            return $this->status(false, true, 'turno_no_configurado', false, $turnoUsuario, $turnoEnServicio, $momento);
        }

        $trabaja = $this->turnos->turnoTrabajaEn($user->turno, $momento);

        return $this->status(
            $trabaja,
            true,
            $trabaja ? 'allowed' : 'turno_descanso',
            $trabaja,
            $turnoUsuario,
            $turnoEnServicio,
            $momento
        );
    }

    private function status(
        bool $allowed,
        bool $applies,
        string $reason,
        ?bool $turnoActivo,
        ?array $turnoUsuario,
        ?array $turnoEnServicio,
        Carbon $momento
    ): array {
        return [
            'allowed' => $allowed,
            'applies' => $applies,
            'reason' => $reason,
            'message' => $allowed ? null : $this->messageForReason($reason, $turnoUsuario, $turnoEnServicio),
            'turno_activo' => $turnoActivo,
            'turno_usuario' => $turnoUsuario,
            'turno_en_servicio' => $turnoEnServicio,
            'checked_at' => $momento->toIso8601String(),
        ];
    }

    private function messageForReason(string $reason, ?array $turnoUsuario, ?array $turnoEnServicio): string
    {
        $usuario = $turnoUsuario['nombre'] ?? 'tu turno';
        $servicio = $turnoEnServicio['nombre'] ?? null;

        if ($reason === 'sin_turno') {
            return 'Acceso bloqueado por turno. Tu usuario no tiene turno asignado para Siniestros.';
        }

        if ($reason === 'turno_no_configurado') {
            return 'Acceso bloqueado por turno. Tu turno no tiene una configuracion laboral valida.';
        }

        if ($servicio) {
            return "Acceso bloqueado por turno. Hoy esta trabajando el turno {$servicio}; {$usuario} no puede entrar.";
        }

        return "Acceso bloqueado por turno. El backend no confirmo que {$usuario} este trabajando actualmente.";
    }

    private function isUnidadSiniestros(User $user): bool
    {
        $slug = mb_strtolower(trim((string)($user->unidad->slug ?? '')), 'UTF-8');

        return (int)($user->unidad_id ?? 0) === 1 || $slug === 'siniestros';
    }

    private function turnoSiemprePermitido($turno): bool
    {
        if (!$turno) {
            return false;
        }

        $tipo = strtoupper(trim((string)($turno->tipo_rol ?? '')));
        $nombre = strtoupper(trim((string)($turno->nombre ?? '')));
        $slug = strtoupper(trim((string)($turno->slug ?? '')));

        return $tipo === 'SUBDIRECTOR'
            || $tipo === 'SIEMPRE'
            || str_contains($nombre, 'SUBDIRECTOR')
            || str_contains($slug, 'SUBDIRECTOR');
    }

    private function turnoResoluble($turno): bool
    {
        if (!$turno) {
            return false;
        }

        if ($this->turnoSiemprePermitido($turno)) {
            return true;
        }

        $tipo = strtoupper(trim((string)($turno->tipo_rol ?? '')));

        if ($tipo === 'LUN_VIE' || $tipo === 'SAB_DOM') {
            return true;
        }

        if ($tipo !== '24X24') {
            return false;
        }

        return !empty($turno->ciclo_inicio)
            && (int)($turno->trabajo_horas ?? 0) > 0
            && $turno->descanso_horas !== null
            && (int)$turno->descanso_horas >= 0;
    }

    private function turnoMeta($turno): ?array
    {
        if (!$turno) {
            return null;
        }

        return [
            'id' => isset($turno->id) ? (int)$turno->id : null,
            'nombre' => $turno->nombre ?? null,
            'slug' => $turno->slug ?? null,
            'tipo_rol' => $turno->tipo_rol ?? null,
        ];
    }
}
