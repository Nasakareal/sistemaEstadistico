<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateSierseCredentials extends Command
{
    protected $signature = 'sierse:generate-credentials {client=galvan} {--days=90 : Vigencia del secreto en dias}';

    protected $description = 'Genera credenciales HMAC para un cliente SIERSE sin modificar el archivo .env';

    public function handle(): int
    {
        $client = strtolower(trim((string) $this->argument('client')));
        if (!preg_match('/^[a-z0-9_-]{1,80}$/', $client)) {
            $this->error('El identificador sólo puede contener letras, números, guion y guion bajo.');
            return 1;
        }

        $secret = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $days = (int) $this->option('days');
        if ($days < 1 || $days > 365) {
            $this->error('La vigencia debe estar entre 1 y 365 dias.');
            return 1;
        }
        $keyId = $client . '-' . now()->format('Ymd') . '-' . bin2hex(random_bytes(4));
        $expiresAt = now()->addDays($days)->utc()->format('Y-m-d\TH:i:s\Z');

        $this->warn('Guarda el secreto en un gestor seguro. El comando no modifica .env.');
        $this->line('SIERSE_API_CLIENT_ID=' . $client);
        $this->line('SIERSE_API_KEY_ID=' . $keyId);
        $this->line('SIERSE_API_CLIENT_SECRET=' . $secret);
        $this->line('SIERSE_API_SECRET_EXPIRES_AT=' . $expiresAt);

        return 0;
    }
}
