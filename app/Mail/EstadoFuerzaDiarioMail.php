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
    public ?string $bitacoraPath;
    public ?string $miniPartePath;

    public function __construct(
        string $fecha,
        ?string $archivoPrincipalPath = null,
        ?string $parteNovedadesPath = null,
        ?string $bitacoraPath = null,
        ?string $miniPartePath = null
    ) {
        $this->fecha = $fecha;
        $this->archivoPrincipalPath = $archivoPrincipalPath;
        $this->parteNovedadesPath = $parteNovedadesPath;
        $this->bitacoraPath = $bitacoraPath;
        $this->miniPartePath = $miniPartePath;
    }

    public function build()
    {
        $mail = $this->subject("Estado de Fuerza + Parte de Novedades + Bitácora + Mini Parte - {$this->fecha}")
            ->view('emails.estado_fuerza_diario');

        if ($this->archivoPrincipalPath && file_exists($this->archivoPrincipalPath)) {
            $mail->attach($this->archivoPrincipalPath);
        }

        if ($this->parteNovedadesPath && file_exists($this->parteNovedadesPath)) {
            $mail->attach($this->parteNovedadesPath);
        }

        if ($this->bitacoraPath && file_exists($this->bitacoraPath)) {
            $mail->attach($this->bitacoraPath);
        }

        if ($this->miniPartePath && file_exists($this->miniPartePath)) {
            $mail->attach($this->miniPartePath);
        }

        return $mail;
    }
}
