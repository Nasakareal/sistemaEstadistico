<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EstadoFuerzaDiarioMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $fecha;
    public ?string $archivoPrincipalPath;
    public ?string $parteNovedadesPath;

    public function __construct(string $fecha, ?string $archivoPrincipalPath = null, ?string $parteNovedadesPath = null)
    {
        $this->fecha = $fecha;
        $this->archivoPrincipalPath = $archivoPrincipalPath;
        $this->parteNovedadesPath = $parteNovedadesPath;
    }

    public function build()
    {
        $mail = $this->subject("Estado de Fuerza + Parte de Novedades - {$this->fecha}")
            ->view('mails.estado_fuerza_diario');

        if ($this->archivoPrincipalPath && file_exists($this->archivoPrincipalPath)) {
            $mail->attach($this->archivoPrincipalPath);
        }

        if ($this->parteNovedadesPath && file_exists($this->parteNovedadesPath)) {
            $mail->attach($this->parteNovedadesPath);
        }

        return $mail;
    }
}
