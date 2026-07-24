<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlcoholimetriaMensualMail extends Mailable
{
    use Queueable, SerializesModels;

    public Carbon $mes;
    public string $archivoNombre;
    public string $archivoContenido;
    public array $resumen;

    public function __construct(
        Carbon $mes,
        string $archivoNombre,
        string $archivoContenido,
        array $resumen
    ) {
        $this->mes = $mes;
        $this->archivoNombre = $archivoNombre;
        $this->archivoContenido = $archivoContenido;
        $this->resumen = $resumen;
    }

    public function build()
    {
        $periodo = ucfirst($this->mes->locale('es')->translatedFormat('F Y'));

        return $this->subject('Concentrado mensual de alcoholimetría - ' . $periodo)
            ->view('emails.alcoholimetria_mensual')
            ->attachData($this->archivoContenido, $this->archivoNombre, [
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
    }
}
