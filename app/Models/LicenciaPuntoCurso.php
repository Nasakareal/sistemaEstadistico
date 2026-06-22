<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenciaPuntoCurso extends Model
{
    public const HORAS_REQUERIDAS = 15;

    public const ESTADO_PROGRAMADO = 'programado';
    public const ESTADO_EN_CURSO = 'en_curso';
    public const ESTADO_CERRADO = 'cerrado';
    public const ESTADO_CANCELADO = 'cancelado';

    protected $table = 'licencia_punto_cursos';

    protected $fillable = [
        'folio',
        'nombre',
        'descripcion',
        'lugar',
        'instructor_id',
        'unidad_id',
        'fecha_inicio',
        'fecha_fin',
        'horas_totales',
        'puntos_recuperacion',
        'clase_en_vivo',
        'materiales_pdf',
        'examen_habilitado',
        'calificacion_por_instructor',
        'calificacion_minima',
        'cupo',
        'estado',
        'closed_at',
        'observaciones',
        'bbb_meeting_id',
        'bbb_moderator_password',
        'bbb_attendee_password',
        'bbb_create_time',
        'bbb_record',
        'bbb_mute_on_start',
        'bbb_lock_viewers_microphone',
        'bbb_anyone_can_talk',
        'bbb_last_started_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'closed_at' => 'datetime',
        'horas_totales' => 'integer',
        'puntos_recuperacion' => 'integer',
        'clase_en_vivo' => 'boolean',
        'materiales_pdf' => 'boolean',
        'examen_habilitado' => 'boolean',
        'calificacion_por_instructor' => 'boolean',
        'calificacion_minima' => 'integer',
        'cupo' => 'integer',
        'bbb_record' => 'boolean',
        'bbb_mute_on_start' => 'boolean',
        'bbb_lock_viewers_microphone' => 'boolean',
        'bbb_anyone_can_talk' => 'boolean',
        'bbb_last_started_at' => 'datetime',
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function participantes()
    {
        return $this->hasMany(LicenciaPuntoCursoParticipante::class, 'curso_id');
    }

    public function materiales()
    {
        return $this->hasMany(LicenciaPuntoCursoMaterial::class, 'curso_id')->orderBy('orden')->orderBy('id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getEstadoLabelAttribute(): string
    {
        return [
            self::ESTADO_PROGRAMADO => 'Programado',
            self::ESTADO_EN_CURSO => 'En curso',
            self::ESTADO_CERRADO => 'Cerrado',
            self::ESTADO_CANCELADO => 'Cancelado',
        ][$this->estado] ?? ucfirst(str_replace('_', ' ', (string) $this->estado));
    }

    public function getPuedeModificarseAttribute(): bool
    {
        return !in_array($this->estado, [self::ESTADO_CERRADO, self::ESTADO_CANCELADO], true);
    }

    public function getModalidadesAttribute(): array
    {
        $modalidades = [];

        if ($this->clase_en_vivo) {
            $modalidades[] = 'Clase en vivo';
        }

        if ($this->materiales_pdf) {
            $modalidades[] = 'Materiales';
        }

        if ($this->examen_habilitado) {
            $modalidades[] = 'Examen';
        }

        return $modalidades ?: ['Control presencial'];
    }

    public function getRequiereCalificacionAttribute(): bool
    {
        return (bool) ($this->examen_habilitado && $this->calificacion_por_instructor);
    }
}
