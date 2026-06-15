<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FormatoInegiChoquesMail extends Mailable
{
    use Queueable, SerializesModels;

    public Carbon $fecha;
    public Carbon $desde;
    public Carbon $hasta;
    public string $archivoNombre;
    public string $archivoContenido;
    public int $totalChoques;

    public function __construct(Carbon $desde, string $archivoNombre, string $archivoContenido, int $totalChoques, ?Carbon $hasta = null)
    {
        $this->fecha = $desde;
        $this->desde = $desde;
        $this->hasta = $hasta ?: $desde;
        $this->archivoNombre = $archivoNombre;
        $this->archivoContenido = $archivoContenido;
        $this->totalChoques = $totalChoques;
    }

    public function build()
    {
        $fechaTexto = $this->desde->isSameDay($this->hasta)
            ? $this->desde->format('Y-m-d')
            : $this->desde->format('Y-m-d') . ' a ' . $this->hasta->format('Y-m-d');

        $mail = $this->subject("Formato INEGI Choques - {$fechaTexto}")
            ->view('emails.formato_inegi_choques');

        $mail->attachData($this->archivoContenido, $this->archivoNombre, [
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

        return $mail;
    }
}
