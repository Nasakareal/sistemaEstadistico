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
    public string $archivoNombre;
    public string $archivoContenido;
    public int $totalChoques;

    public function __construct(Carbon $fecha, string $archivoNombre, string $archivoContenido, int $totalChoques)
    {
        $this->fecha = $fecha;
        $this->archivoNombre = $archivoNombre;
        $this->archivoContenido = $archivoContenido;
        $this->totalChoques = $totalChoques;
    }

    public function build()
    {
        $fechaTexto = $this->fecha->format('Y-m-d');

        $mail = $this->subject("Formato INEGI Choques - {$fechaTexto}")
            ->view('emails.formato_inegi_choques');

        $mail->attachData($this->archivoContenido, $this->archivoNombre, [
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

        return $mail;
    }
}
