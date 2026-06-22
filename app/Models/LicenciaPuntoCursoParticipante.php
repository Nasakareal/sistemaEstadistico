<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenciaPuntoCursoParticipante extends Model
{
    public const ESTADO_INSCRITO = 'inscrito';
    public const ESTADO_ACREDITADO = 'acreditado';
    public const ESTADO_NO_ACREDITADO = 'no_acreditado';
    public const ESTADO_CANCELADO = 'cancelado';

    protected $table = 'licencia_punto_curso_participantes';

    protected $fillable = [
        'curso_id',
        'cuenta_id',
        'conductor_id',
        'movimiento_id',
        'numero_licencia',
        'titular_nombre',
        'curp',
        'telefono',
        'asistencia_horas',
        'calificacion',
        'calificado_at',
        'calificado_by',
        'estado',
        'puntos_acreditados',
        'acreditado_at',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'asistencia_horas' => 'float',
        'calificacion' => 'integer',
        'calificado_at' => 'datetime',
        'puntos_acreditados' => 'integer',
        'acreditado_at' => 'datetime',
    ];

    public function curso()
    {
        return $this->belongsTo(LicenciaPuntoCurso::class, 'curso_id');
    }

    public function cuenta()
    {
        return $this->belongsTo(LicenciaPuntoCuenta::class, 'cuenta_id');
    }

    public function conductor()
    {
        return $this->belongsTo(Conductor::class, 'conductor_id');
    }

    public function movimiento()
    {
        return $this->belongsTo(LicenciaPuntoMovimiento::class, 'movimiento_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function calificador()
    {
        return $this->belongsTo(User::class, 'calificado_by');
    }

    public function getEstadoLabelAttribute(): string
    {
        return [
            self::ESTADO_INSCRITO => 'Inscrito',
            self::ESTADO_ACREDITADO => 'Acreditado',
            self::ESTADO_NO_ACREDITADO => 'No acreditado',
            self::ESTADO_CANCELADO => 'Cancelado',
        ][$this->estado] ?? ucfirst(str_replace('_', ' ', (string) $this->estado));
    }

    public function getCumpleHorasAttribute(): bool
    {
        $horasCurso = $this->curso ? (float) $this->curso->horas_totales : LicenciaPuntoCurso::HORAS_REQUERIDAS;

        return (float) $this->asistencia_horas >= $horasCurso;
    }

    public function getCumpleCalificacionAttribute(): bool
    {
        if (!$this->curso || !$this->curso->requiere_calificacion) {
            return true;
        }

        return !is_null($this->calificacion)
            && (int) $this->calificacion >= (int) $this->curso->calificacion_minima;
    }

    public function getPuedeAcreditarseAttribute(): bool
    {
        return $this->cumple_horas && $this->cumple_calificacion;
    }
}
