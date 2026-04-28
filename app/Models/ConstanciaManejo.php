<?php

namespace App\Models;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ConstanciaManejo extends Model
{
    protected $table = 'constancias_manejo';

    protected $fillable = [
        'folio',
        'folio_qr',
        'modulo_id',
        'delegacion_id',
        'user_id',
        'perito_activador_id',
        'nombre_solicitante',
        'curp',
        'telefono',
        'tipo_licencia',
        'tipo_examen',
        'estatus',
        'fecha_impresion',
        'fecha_activacion',
        'fecha_expiracion',
        'pdf_path',
        'qr_token',
        'acceso_examen_token',
        'acceso_examen_expira',
    ];

    protected $casts = [
        'fecha_impresion' => 'datetime',
        'fecha_activacion' => 'datetime',
        'fecha_expiracion' => 'datetime',
        'acceso_examen_expira' => 'datetime',
    ];

    public function modulo()
    {
        return $this->belongsTo(ConstanciaModulo::class, 'modulo_id');
    }

    public function folioRegistro()
    {
        return $this->hasOne(ConstanciaFolio::class, 'constancia_id');
    }

    public function examen()
    {
        return $this->hasOne(ConstanciaExamen::class, 'constancia_id');
    }

    public function activaciones()
    {
        return $this->hasMany(ConstanciaActivacion::class, 'constancia_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function peritoActivador()
    {
        return $this->belongsTo(User::class, 'perito_activador_id');
    }

    public function getEstaVigenteAttribute()
    {
        return $this->estatus === 'ACTIVA' && $this->fecha_expiracion && now()->lessThanOrEqualTo($this->fecha_expiracion);
    }

    public function getEstaExpiradaAttribute()
    {
        return $this->fecha_expiracion && now()->greaterThan($this->fecha_expiracion);
    }

    public function qrUrl(): string
    {
        return route('constancias_manejo.validar', $this->qr_token);
    }

    public function qrDataUri(): string
    {
        $qrCode = static::buildQrCode($this->qrUrl());

        return 'data:image/png;base64,' . base64_encode($qrCode->getString());
    }

    protected static function buildQrCode(string $url)
    {
        foreach (static::qrLogoCandidates() as $logoPath) {
            if (!is_file($logoPath)) {
                continue;
            }

            try {
                return static::makeQrBuilder($url)
                    ->logoPath($logoPath)
                    ->logoResizeToWidth(60)
                    ->build();
            } catch (\Throwable $e) {
                Log::warning('No se pudo usar un logo al generar el QR de constancia de manejo.', [
                    'logo_path' => $logoPath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return static::makeQrBuilder($url)->build();
    }

    protected static function makeQrBuilder(string $url): Builder
    {
        return Builder::create()
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(200)
            ->margin(10);
    }

    protected static function qrLogoCandidates(): array
    {
        return array_values(array_unique([
            public_path('img/blanco.png'),
            public_path('img/white.png'),
            public_path('guardiacivil.png'),
        ]));
    }
}
