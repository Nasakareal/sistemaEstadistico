<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

use App\Models\Hechos; // ✅ Importar el modelo con el nombre correcto

class Liberacion extends Model
{
    use HasFactory;

    protected $table = 'liberaciones';

    protected $fillable = [
        'vehiculo_id',
        'hecho_id', // ✅ ya lo estás guardando correctamente
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
                $token = $liberacion->token_unico;

                $url = url('/liberacion/qr/' . $token);

                $qrCode = Builder::create()
                    ->data($url)
                    ->encoding(new Encoding('UTF-8'))
                    ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
                    ->size(300)
                    ->margin(10)
                    ->logoPath(public_path('logofondo.png'))
                    ->logoResizeToWidth(100)
                    ->build();

                $fileName = 'liberaciones/qr/QR_' . $token . '.png';
                Storage::disk('public')->put($fileName, $qrCode->getString());

                $liberacion->qr_path = 'storage/' . $fileName;
                $liberacion->save();
            } catch (\Exception $e) {
                \Log::error('Error al generar el QR para liberación: ' . $e->getMessage());
            }
        });
    }
}
