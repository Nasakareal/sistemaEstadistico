<?php

namespace App\Console\Commands;

use App\Services\ResumenTodasUnidadesWhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class VerificarResumenTodasUnidadesWhatsApp extends Command
{
    protected $signature = 'whatsapp:resumen-todas-unidades-verificar
        {--to= : Numeros destino separados por coma, espacio o punto y coma}
        {--corte= : Fecha/hora de corte para simular}
        {--sin-template : Valida el modo texto libre}
        {--mostrar-texto : Muestra el reporte completo armado}';

    protected $description = 'Verifica configuracion, horario y contenido del resumen de todas las unidades sin enviar WhatsApp';

    public function handle(ResumenTodasUnidadesWhatsAppService $resumenService): int
    {
        $timezone = 'America/Mexico_City';
        $corte = $this->option('corte')
            ? Carbon::parse((string) $this->option('corte'), $timezone)
            : null;

        $resumen = $resumenService->generar($corte);
        $mensaje = (string) $resumen['mensaje'];

        $usarTemplate = !$this->option('sin-template');
        $template = (string) config('services.whatsapp.todas_unidades.template', '');
        $templateLayout = (string) config('services.whatsapp.todas_unidades.template_layout', 'diario');
        $language = (string) config('services.whatsapp.todas_unidades.template_language', 'es_MX');
        $templateChunkChars = (int) config('services.whatsapp.todas_unidades.template_chunk_chars', 30000);
        $textChunkChars = (int) config('services.whatsapp.todas_unidades.text_chunk_chars', 3900);

        $to = (string) (
            $this->option('to')
            ?: config('services.whatsapp.todas_unidades.to')
            ?: config('services.whatsapp.default_to')
        );
        $recipients = $this->recipients($to);

        $this->info('Verificacion de WhatsApp: resumen todas unidades');
        $this->line('');
        $this->line('Horario programado: 19:00 America/Mexico_City');
        $this->line('Rango que se enviaria: '
            . $resumen['inicio']->format('Y-m-d H:i:s')
            . ' a '
            . $resumen['fin']->format('Y-m-d H:i:s')
            . ' America/Mexico_City');
        $this->line('Destinatarios: ' . (empty($recipients) ? 'SIN CONFIGURAR' : implode(', ', $recipients)));
        $this->line('');

        if ($usarTemplate) {
            $this->line('Modo: plantilla');
            $this->line('Plantilla configurada: ' . ($template !== '' ? $template : 'SIN CONFIGURAR'));
            $this->line('Layout: ' . $templateLayout);
            $this->line('Idioma: ' . $language);

            if ($template === '') {
                $this->error('Falta WHATSAPP_TODAS_UNIDADES_TEMPLATE.');
                return self::FAILURE;
            }

            if ($this->usaTemplateBloque($templateLayout)) {
                $this->line('Cuerpo esperado en Meta:');
                $this->line('Reporte diario de todas las unidades');
                $this->line('Parte {{1}} de {{2}}');
                $this->line('');
                $this->line('{{3}}');
                $this->line('');
                $this->line('Fin de esta parte.');

                $chunks = $resumenService->whatsAppTemplateChunks($mensaje, $templateChunkChars);
                $this->line('');
                $this->line('Partes a enviar: ' . count($chunks));

                foreach ($chunks as $chunk) {
                    $this->line('Parte ' . $chunk['part'] . '/' . $chunk['total'] . ': ' . mb_strlen($chunk['body'], 'UTF-8') . ' caracteres');
                }
            } else {
                $this->line('Cuerpo esperado en Meta: el de reporte_todas_unidades_diario con variables {{1}} a {{14}}.');
                $this->line('Importante: debe quedar sin encabezado, sin pie y sin botones para que aplique como body-only.');
                $this->line('');
                $this->line('Variables a enviar:');

                foreach (($resumen['template_params'] ?? []) as $index => $param) {
                    $this->line('{{' . ($index + 1) . '}}: ' . mb_strlen((string) $param, 'UTF-8') . ' caracteres');
                }

                $this->line('');
                $this->line('Texto final aproximado: ' . mb_strlen($mensaje, 'UTF-8') . ' caracteres');
            }
        } else {
            $chunks = $resumenService->whatsAppTextChunks($mensaje, $textChunkChars);
            $this->warn('Modo: texto libre. Solo funciona si el destinatario abrio ventana de 24 horas.');
            $this->line('Partes a enviar: ' . count($chunks));

            foreach ($chunks as $index => $chunk) {
                $this->line('Parte ' . ($index + 1) . '/' . count($chunks) . ': ' . mb_strlen($chunk, 'UTF-8') . ' caracteres');
            }
        }

        if (empty($recipients)) {
            $this->error('No hay destinatarios. Define WHATSAPP_TODAS_UNIDADES_TO o usa --to=');
            return self::FAILURE;
        }

        $this->line('');

        if ($this->option('mostrar-texto')) {
            $this->line('--- REPORTE ARMADO ---');
            $this->line($mensaje);
        } else {
            $this->line('Usa --mostrar-texto si quieres ver el reporte completo que se mandaria.');
        }

        $this->info('Verificacion completada sin enviar mensajes.');

        return self::SUCCESS;
    }

    protected function usaTemplateBloque(string $templateLayout): bool
    {
        return mb_strtolower(trim($templateLayout), 'UTF-8') === 'bloque';
    }

    protected function recipients(string $configured): array
    {
        $parts = preg_split('/[\s,;]+/', $configured, -1, PREG_SPLIT_NO_EMPTY);
        $numbers = [];

        foreach ($parts ?: [] as $part) {
            $number = preg_replace('/\D+/', '', (string) $part);

            if ($number !== '') {
                $numbers[] = $number;
            }
        }

        return array_values(array_unique($numbers));
    }
}
