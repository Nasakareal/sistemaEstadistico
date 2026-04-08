<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

use App\Models\Hechos;

class Liberacion extends Model
{
    use HasFactory;

    protected const QR_STORAGE_DIR = 'liberaciones/qr';

    protected $table = 'liberaciones';

    protected $fillable = [
        'vehiculo_id',
        'hecho_id',
        'token_unico',
        'fecha_liberacion',
        'personas_autorizadas',
        'pdf_gruas',
        'creado_por',
        'qr_path',
        'folio_anual',
        'autoriza',
        'motivo_liberacion',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function hecho()
    {
        return $this->belongsTo(Hechos::class, 'hecho_id', 'id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    protected static function booted()
    {
        static::created(function ($liberacion) {
            try {
                $liberacion->regenerarQr();
            } catch (\Throwable $e) {
                Log::error('Error al generar el QR para liberación: ' . $e->getMessage());
            }
        });
    }

    public function regenerarQr(): void
    {
        if (empty($this->token_unico)) {
            throw new \RuntimeException('La liberación no tiene token para generar el QR.');
        }

        $url = url('/liberacion/qr/' . $this->token_unico);
        $qrCode = static::buildQrCode($url);
        $fileName = static::QR_STORAGE_DIR . '/QR_' . $this->token_unico . '.png';
        $qrPath = 'storage/' . $fileName;

        Storage::disk('public')->put($fileName, $qrCode->getString());

        $shouldPersistPath = $this->qr_path !== $qrPath;
        $this->qr_path = $qrPath;

        if ($this->exists && $shouldPersistPath) {
            $this->saveQuietly();
        }
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
                Log::warning('No se pudo usar un logo al generar el QR de liberación.', [
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
