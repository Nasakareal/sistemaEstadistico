<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class LocationTrackingEligibilityService
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

        $user->loadMissing(['unidad', 'turno', 'personal.turno', 'roles']);

        $turnoUsuario = $this->turnoEfectivo($user);
        $turnoUsuarioMeta = $this->turnoMeta($turnoUsuario);

        if ((int)($user->compartir_ubicacion ?? 0) !== 1) {
            return [
                'allowed' => false,
                'reason' => 'compartir_ubicacion_off',
                'turno_activo' => null,
                'turno_usuario' => $turnoUsuarioMeta,
                'turno_en_servicio' => $this->turnoMeta($this->turnos->turnoActivoEn($momento)),
                'checked_at' => $momento->toIso8601String(),
            ];
        }

        if ($this->isVialidadesUrbanas($user) && !$user->hasRole('Agente Vial')) {
            return [
                'allowed' => false,
                'reason' => 'rol_no_autorizado_vialidades',
                'turno_activo' => null,
                'turno_usuario' => $turnoUsuarioMeta,
                'turno_en_servicio' => $this->turnoMeta($this->turnos->turnoActivoEn($momento)),
                'checked_at' => $momento->toIso8601String(),
            ];
        }

        if ($this->isSiniestros($user)) {
            if (!$turnoUsuario) {
                return [
                    'allowed' => false,
                    'reason' => 'turno_sin_asignar',
                    'turno_activo' => null,
                    'turno_usuario' => null,
                    'turno_en_servicio' => $this->turnoMeta($this->turnos->turnoActivoEn($momento)),
                    'checked_at' => $momento->toIso8601String(),
                ];
            }

            return $this->statusPorTurno($turnoUsuario, $momento);
        }

        if (!$this->isAgenteVialVialidadesUrbanas($user)) {
            return [
                'allowed' => true,
                'reason' => 'allowed',
                'turno_activo' => null,
                'turno_usuario' => $turnoUsuarioMeta,
                'turno_en_servicio' => null,
                'checked_at' => $momento->toIso8601String(),
            ];
        }

        return $this->statusPorTurno($turnoUsuario, $momento);
    }

    private function statusPorTurno($turno, Carbon $momento): array
    {
        $turnoActivo = $this->turnos->turnoTrabajaEn($turno, $momento);
        $turnoEnServicio = $this->turnoMeta($this->turnos->turnoActivoEn($momento));

        return [
            'allowed' => $turnoActivo,
            'reason' => $turnoActivo ? 'allowed' : 'turno_descanso',
            'turno_activo' => $turnoActivo,
            'turno_usuario' => $this->turnoMeta($turno),
            'turno_en_servicio' => $turnoEnServicio,
            'checked_at' => $momento->toIso8601String(),
        ];
    }

    private function turnoEfectivo(User $user)
    {
        if ($this->isSiniestros($user) && $user->personal && $user->personal->turno) {
            return $user->personal->turno;
        }

        return $user->turno;
    }

    private function isAgenteVialVialidadesUrbanas(User $user): bool
    {
        return $user->hasRole('Agente Vial')
            && $this->isVialidadesUrbanas($user);
    }

    private function isVialidadesUrbanas(User $user): bool
    {
        $unidadSlug = mb_strtolower(trim((string)($user->unidad->slug ?? '')), 'UTF-8');
        $unidadId = (int)($user->unidad_id ?? 0);

        return $unidadId === 5 || $unidadSlug === 'vialidades-urbanas';
    }

    private function isSiniestros(User $user): bool
    {
        $unidadSlug = mb_strtolower(trim((string)($user->unidad->slug ?? '')), 'UTF-8');
        $unidadId = (int)($user->unidad_id ?? 0);

        return $unidadId === 1 || $unidadSlug === 'siniestros';
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
        ];
    }
}
