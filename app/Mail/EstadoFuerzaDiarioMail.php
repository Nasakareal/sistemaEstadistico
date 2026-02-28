<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EstadoFuerzaDiarioMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $fechaTexto;

    protected string $filePath;
    protected string $fileName;

    public function __construct(string $filePath, string $fileName, string $fechaTexto)
    {
        $this->filePath = $filePath;
        $this->fileName = $fileName;
        $this->fechaTexto = $fechaTexto;
    }

    public function build()
    {
        return $this
            ->subject('Estado de Fuerza - ' . $this->fechaTexto)
            ->view('emails.estado_fuerza_diario')
            ->attach($this->filePath, [
                'as' => $this->fileName,
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
