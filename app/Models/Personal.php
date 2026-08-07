<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Personal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'personals';

    protected $fillable = [
        'unidad_id',
        'destacamento_id',
        'turno_id',
        'patrulla_id',
        'user_id',
        'numero_empleado',
        'numero_placa',
        'nombre',
        'ap_paterno',
        'ap_materno',
        'fecha_nacimiento',
        'tipo_sangre',
        'curp',
        'rfc',
        'numero_seguro_social',
        'correo_electronico',
        'cuip',
        'cup',
        'grado',
        'puesto',
        'adscripcion',
        'area',
        'ultimo_grado_estudios',
        'alergias_estado',
        'alergias',
        'categoria',
        'foto',
        'estatus',
        'fecha_ingreso',
        'fecha_ingreso_unidad',
        'fecha_baja',
    ];

    protected $casts = [
        'unidad_id' => 'integer',
        'destacamento_id' => 'integer',
        'turno_id' => 'integer',
        'patrulla_id' => 'integer',
        'user_id' => 'integer',
        'fecha_nacimiento' => 'date',
        'fecha_ingreso' => 'date',
        'fecha_ingreso_unidad' => 'date',
        'fecha_baja' => 'date',
    ];

    public const TIPOS_SANGRE = [
        'A_POSITIVO' => 'A+',
        'A_NEGATIVO' => 'A-',
        'B_POSITIVO' => 'B+',
        'B_NEGATIVO' => 'B-',
        'AB_POSITIVO' => 'AB+',
        'AB_NEGATIVO' => 'AB-',
        'O_POSITIVO' => 'O+',
        'O_NEGATIVO' => 'O-',
        'DESCONOCIDO' => 'Desconocido',
    ];

    public const GRADOS_ESTUDIO = [
        'SIN_ESTUDIOS' => 'Sin estudios',
        'PRIMARIA' => 'Primaria',
        'SECUNDARIA' => 'Secundaria',
        'BACHILLERATO' => 'Bachillerato / preparatoria',
        'CARRERA_TECNICA' => 'Carrera técnica',
        'LICENCIATURA' => 'Licenciatura',
        'ESPECIALIDAD' => 'Especialidad',
        'MAESTRIA' => 'Maestría',
        'DOCTORADO' => 'Doctorado',
    ];

    public const ESTADOS_ALERGIAS = [
        'NINGUNA' => 'Ninguna conocida',
        'SI' => 'Sí presenta alergias',
        'DESCONOCIDAS' => 'Se desconoce',
    ];

    public function tipoSangreLabel(): ?string
    {
        return self::TIPOS_SANGRE[$this->tipo_sangre] ?? $this->tipo_sangre;
    }

    public function ultimoGradoEstudiosLabel(): ?string
    {
        return self::GRADOS_ESTUDIO[$this->ultimo_grado_estudios] ?? $this->ultimo_grado_estudios;
    }

    public function alergiasEstadoLabel(): ?string
    {
        return self::ESTADOS_ALERGIAS[$this->alergias_estado] ?? $this->alergias_estado;
    }

    protected static function booted(): void
    {
        static::saved(function (self $personal) {
            if (!$personal->user_id || !$personal->wasChanged(['unidad_id', 'user_id'])) {
                return;
            }

            User::query()
                ->whereKey($personal->user_id)
                ->where(function ($query) use ($personal) {
                    $query->whereNull('unidad_id')
                        ->orWhere('unidad_id', '!=', $personal->unidad_id);
                })
                ->update(['unidad_id' => $personal->unidad_id]);
        });
    }

    public static function formarNombreCompleto($nombre, $apPaterno, $apMaterno): string
    {
        return trim(implode(' ', array_filter([
            $apPaterno,
            $apMaterno,
            $nombre,
        ], fn ($value) => trim((string) $value) !== '')));
    }

    public function nombreCompleto(): string
    {
        return self::formarNombreCompleto(
            $this->nombre,
            $this->ap_paterno,
            $this->ap_materno
        );
    }

    public function nombreCompletoConGrado(): string
    {
        return trim(implode(' ', array_filter([
            $this->grado,
            $this->nombreCompleto(),
        ], fn ($value) => trim((string) $value) !== '')));
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->nombreCompleto();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class, 'turno_id');
    }

    public function patrulla()
    {
        return $this->belongsTo(Patrulla::class, 'patrulla_id');
    }

    public function rolesServicio()
    {
        return $this->hasMany(PersonalRol::class, 'personal_id');
    }

    public function incidencias()
    {
        return $this->hasMany(PersonalIncidencia::class, 'personal_id');
    }

    public function documentos()
    {
        return $this->hasMany(PersonalDocumento::class, 'personal_id');
    }

    public function licencias()
    {
        return $this->hasMany(PersonalLicencia::class, 'personal_id');
    }

    public function asignaciones()
    {
        return $this->hasMany(PersonalAsignacion::class, 'personal_id');
    }

    public function contactos()
    {
        return $this->hasMany(PersonalContacto::class, 'personal_id');
    }

    public function destacamento()
    {
        return $this->belongsTo(Destacamento::class, 'destacamento_id');
    }

    public function emergencias()
    {
        return $this->hasMany(PersonalEmergencia::class, 'personal_id');
    }

    public function domicilios()
    {
        return $this->hasMany(PersonalDomicilio::class, 'personal_id');
    }

    public function fotos()
    {
        return $this->hasMany(PersonalFoto::class, 'personal_id');
    }

    public function domicilioActual()
    {
        return $this->hasOne(PersonalDomicilio::class, 'personal_id')->where('es_actual', true);
    }

    public function contactoPrincipal()
    {
        return $this->hasOne(PersonalContacto::class, 'personal_id')->where('es_principal', true);
    }

    public function fotoPrincipal()
    {
        return $this->hasOne(PersonalFoto::class, 'personal_id')->latestOfMany();
    }
}
