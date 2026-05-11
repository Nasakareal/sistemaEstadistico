<?php

namespace App\Console\Commands;

use App\Services\ResumenTodasUnidadesWhatsAppService;
use App\Services\WhatsAppCloudService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnviarResumenTodasUnidadesWhatsApp extends Command
{
    protected $signature = 'whatsapp:resumen-todas-unidades {--to=} {--corte=} {--sin-template} {--dry-run}';
    protected $description = 'Envia el resumen de todas las unidades por WhatsApp con corte de 19:00 a 19:00';

    public function handle(WhatsAppCloudService $whatsApp, ResumenTodasUnidadesWhatsAppService $resumenService): int
    {
        $timezone = 'America/Mexico_City';
        $corte = $this->option('corte')
            ? Carbon::parse((string) $this->option('corte'), $timezone)
            : null;

        $resumen = $resumenService->generar($corte);
        $mensaje = (string) $resumen['mensaje'];
        $dailyParams = $resumen['template_params'] ?? [];
        $usarTemplate = !$this->option('sin-template');
        $template = (string) config('services.whatsapp.todas_unidades.template', '');
        $twoPartTemplate1 = (string) config('services.whatsapp.todas_unidades.two_part_template_1', 'reporte_todas_unidades_parte_1');
        $twoPartTemplate2 = (string) config('services.whatsapp.todas_unidades.two_part_template_2', 'reporte_todas_unidades_parte_2');
        $blockTemplate = (string) config('services.whatsapp.todas_unidades.block_template', 'reporte_todas_unidades_bloque');
        $templateLayout = (string) config('services.whatsapp.todas_unidades.template_layout', 'dos_partes');
        $language = (string) config('services.whatsapp.todas_unidades.template_language', 'es_MX');
        $templateBodyMaxChars = (int) config('services.whatsapp.todas_unidades.template_body_max_chars', 1024);
        $templateChunkChars = (int) config('services.whatsapp.todas_unidades.template_chunk_chars', 850);
        $textChunkChars = (int) config('services.whatsapp.todas_unidades.text_chunk_chars', 3900);
        $dailyBodyChars = mb_strlen($this->renderDailyTemplateBody($dailyParams), 'UTF-8');
        $twoPartBodies = $this->renderTwoPartTemplateBodies($dailyParams);
        $selectedTemplateLayout = $this->resolveTemplateLayout($templateLayout, $dailyBodyChars, $templateBodyMaxChars);

        if ($this->option('dry-run')) {
            $this->line('--- RANGO ---');
            $this->line($resumen['inicio']->format('Y-m-d H:i:s') . ' a ' . $resumen['fin']->format('Y-m-d H:i:s') . ' America/Mexico_City');
            $this->line('--- MENSAJE ARMADO ---');
            $this->line($mensaje);
            $this->line('--- CANAL WHATSAPP ---');

            if ($usarTemplate && ($template !== '' || $blockTemplate !== '' || $twoPartTemplate1 !== '' || $twoPartTemplate2 !== '')) {
                $this->line('Modo: plantilla body-only');
                $this->line('Template diario: ' . ($template !== '' ? $template : 'SIN CONFIGURAR') . ' (' . $language . ')');
                $this->line('Template parte 1: ' . ($twoPartTemplate1 !== '' ? $twoPartTemplate1 : 'SIN CONFIGURAR'));
                $this->line('Template parte 2: ' . ($twoPartTemplate2 !== '' ? $twoPartTemplate2 : 'SIN CONFIGURAR'));
                $this->line('Template de respaldo por partes: ' . $blockTemplate);
                $this->line('Layout configurado: ' . $templateLayout);
                $this->line('Layout elegido: ' . $selectedTemplateLayout);
                $this->line('Limite cuerpo plantilla: ' . $templateBodyMaxChars . ' caracteres');
                $this->line('Cuerpo estimado con plantilla diaria: ' . $dailyBodyChars . ' caracteres');

                if ($this->usaTemplateDosPartes($selectedTemplateLayout)) {
                    $this->line('Mensajes: 2');
                    $this->line('Parte 1/2: ' . mb_strlen($twoPartBodies[0], 'UTF-8') . ' caracteres');
                    $this->line('Parte 2/2: ' . mb_strlen($twoPartBodies[1], 'UTF-8') . ' caracteres');
                } elseif ($this->usaTemplateBloque($selectedTemplateLayout)) {
                    $chunks = $resumenService->whatsAppTemplateChunks($mensaje, $templateChunkChars);
                    $this->line('Partes: ' . count($chunks));

                    foreach ($chunks as $chunk) {
                        $this->line('Parte ' . $chunk['part'] . '/' . $chunk['total'] . ': ' . mb_strlen($chunk['body'], 'UTF-8') . ' caracteres');
                    }
                } else {
                    $this->line('Variables: ' . count($dailyParams));
                    $this->line('Texto final aproximado: ' . mb_strlen($mensaje, 'UTF-8') . ' caracteres');

                    foreach ($dailyParams as $index => $param) {
                        $this->line('{{' . ($index + 1) . '}}: ' . mb_strlen((string) $param, 'UTF-8') . ' caracteres');
                    }
                }
            } else {
                $chunks = $resumenService->whatsAppTextChunks($mensaje, $textChunkChars);
                $this->line('Modo: texto libre');
                $this->line('Partes: ' . count($chunks) . ' (solo funciona con ventana de 24 horas abierta)');

                foreach ($chunks as $index => $chunk) {
                    $this->line('Parte ' . ($index + 1) . '/' . count($chunks) . ': ' . mb_strlen($chunk, 'UTF-8') . ' caracteres');
                }
            }

            return self::SUCCESS;
        }

        $to = (string) (
            $this->option('to')
            ?: config('services.whatsapp.todas_unidades.to')
            ?: config('services.whatsapp.default_to')
        );

        $recipients = $this->recipients($to);

        if (empty($recipients)) {
            $this->error('No hay numero destino. Define WHATSAPP_TODAS_UNIDADES_TO o usa --to=');
            return self::FAILURE;
        }

        if ($usarTemplate && $this->usaTemplateDosPartes($selectedTemplateLayout) && ($twoPartTemplate1 === '' || $twoPartTemplate2 === '')) {
            $this->error('No hay plantillas de dos partes configuradas. Define WHATSAPP_TODAS_UNIDADES_TEMPLATE_PARTE_1 y WHATSAPP_TODAS_UNIDADES_TEMPLATE_PARTE_2.');
            return self::FAILURE;
        }

        if ($usarTemplate && !$this->usaTemplateBloque($selectedTemplateLayout) && !$this->usaTemplateDosPartes($selectedTemplateLayout) && $template === '') {
            $this->error('No hay plantilla configurada. Define WHATSAPP_TODAS_UNIDADES_TEMPLATE o usa --sin-template solo si la ventana de 24 horas esta abierta.');
            return self::FAILURE;
        }

        if ($usarTemplate && $this->usaTemplateBloque($selectedTemplateLayout) && $blockTemplate === '') {
            $this->error('No hay plantilla de respaldo configurada. Define WHATSAPP_TODAS_UNIDADES_BLOCK_TEMPLATE.');
            return self::FAILURE;
        }

        $failures = 0;
        $sentRecipients = 0;
        $sentMessages = 0;

        foreach ($recipients as $recipient) {
            try {
                if ($usarTemplate && $this->usaTemplateDosPartes($selectedTemplateLayout)) {
                    $responses = $this->sendTwoPartTemplates($whatsApp, $recipient, $twoPartTemplate1, $twoPartTemplate2, $dailyParams, $language);
                } elseif ($usarTemplate && $this->usaTemplateBloque($selectedTemplateLayout)) {
                    $responses = $this->sendTemplateChunks($whatsApp, $resumenService, $recipient, $mensaje, $blockTemplate, $language, $templateChunkChars);
                } elseif ($usarTemplate) {
                    $responses = $this->sendDailyTemplate($whatsApp, $recipient, $template, $dailyParams, $language);
                } else {
                    $responses = $this->sendTextChunks($whatsApp, $resumenService, $recipient, $mensaje, $textChunkChars);
                }

                $sentMessages += count($responses);
                $sentRecipients++;
            } catch (\Throwable $e) {
                Log::error('Error enviando resumen todas unidades WhatsApp', [
                    'to' => $recipient,
                    'error' => $e->getMessage(),
                ]);

                $this->error('Error enviando a ' . $recipient . ': ' . $e->getMessage());
                $failures++;
            }
        }

        $this->line('--- RANGO ---');
        $this->line($resumen['inicio']->format('Y-m-d H:i:s') . ' a ' . $resumen['fin']->format('Y-m-d H:i:s') . ' America/Mexico_City');
        $this->line('--- MENSAJE ARMADO ---');
        $this->line($mensaje);

        if ($failures > 0) {
            $this->error("Resumen procesado con {$sentRecipients} destinatario(s), {$sentMessages} mensaje(s) aceptado(s) y {$failures} error(es).");
            return self::FAILURE;
        }

        $this->info("Resumen enviado a {$sentRecipients} destinatario(s) en {$sentMessages} mensaje(s).");
        return self::SUCCESS;
    }

    protected function sendDailyTemplate(
        WhatsAppCloudService $whatsApp,
        string $recipient,
        string $template,
        array $params,
        string $language
    ): array {
        $response = $whatsApp->sendTemplate($recipient, $template, $params, $language);

        $messageId = $this->handleMetaResponse(
            $response,
            $recipient,
            'template ' . $template . ' con ' . count($params) . ' variables'
        );

        return [$messageId];
    }

    protected function sendTwoPartTemplates(
        WhatsAppCloudService $whatsApp,
        string $recipient,
        string $template1,
        string $template2,
        array $params,
        string $language
    ): array {
        $accepted = [];

        foreach ($this->twoPartTemplatePayloads($template1, $template2, $params) as $payload) {
            $response = $whatsApp->sendTemplate($recipient, $payload['template'], $payload['params'], $language);

            $messageId = $this->handleMetaResponse(
                $response,
                $recipient,
                'template ' . $payload['template'] . ' parte ' . $payload['part'] . '/2'
            );

            $accepted[] = $messageId;
        }

        return $accepted;
    }

    protected function twoPartTemplatePayloads(string $template1, string $template2, array $params): array
    {
        $p = array_values(array_map('strval', $params));

        for ($i = count($p); $i < 14; $i++) {
            $p[] = '';
        }

        return [
            [
                'part' => 1,
                'template' => $template1,
                'params' => array_slice($p, 0, 7),
            ],
            [
                'part' => 2,
                'template' => $template2,
                'params' => array_slice($p, 7, 7),
            ],
        ];
    }

    protected function sendTemplateChunks(
        WhatsAppCloudService $whatsApp,
        ResumenTodasUnidadesWhatsAppService $resumenService,
        string $recipient,
        string $mensaje,
        string $template,
        string $language,
        int $templateChunkChars
    ): array {
        $accepted = [];
        $chunks = $resumenService->whatsAppTemplateChunks($mensaje, $templateChunkChars);

        foreach ($chunks as $chunk) {
            $response = $whatsApp->sendTemplate($recipient, $template, $chunk['parameters'], $language);

            $messageId = $this->handleMetaResponse(
                $response,
                $recipient,
                'template ' . $template . ' parte ' . $chunk['part'] . '/' . $chunk['total']
            );

            $accepted[] = $messageId;
        }

        return $accepted;
    }

    protected function sendTextChunks(
        WhatsAppCloudService $whatsApp,
        ResumenTodasUnidadesWhatsAppService $resumenService,
        string $recipient,
        string $mensaje,
        int $textChunkChars
    ): array {
        $accepted = [];
        $chunks = $resumenService->whatsAppTextChunks($mensaje, $textChunkChars);

        foreach ($chunks as $index => $chunk) {
            $response = $whatsApp->sendText($recipient, $chunk);

            $messageId = $this->handleMetaResponse(
                $response,
                $recipient,
                'texto libre parte ' . ($index + 1) . '/' . count($chunks)
            );

            $accepted[] = $messageId;
        }

        return $accepted;
    }

    protected function handleMetaResponse(array $response, string $recipient, string $context): string
    {
        Log::info('Respuesta WhatsApp resumen todas unidades', [
            'to' => $recipient,
            'context' => $context,
            'response' => $response,
        ]);

        $this->line('--- RESPUESTA META (' . $recipient . ', ' . $context . ') ---');
        $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!($response['ok'] ?? false)) {
            throw new \RuntimeException('Meta rechazo el envio de ' . $context . '.');
        }

        $body = $response['body'] ?? [];
        $messageId = $body['messages'][0]['id'] ?? null;

        if (!$messageId) {
            throw new \RuntimeException('Meta respondio sin message id para ' . $context . '.');
        }

        $this->info('Mensaje aceptado por Meta para ' . $recipient . ' (' . $context . '). ID: ' . $messageId);

        return (string) $messageId;
    }

    protected function usaTemplateBloque(string $templateLayout): bool
    {
        return mb_strtolower(trim($templateLayout), 'UTF-8') === 'bloque';
    }

    protected function usaTemplateDosPartes(string $templateLayout): bool
    {
        return mb_strtolower(trim($templateLayout), 'UTF-8') === 'dos_partes';
    }

    protected function resolveTemplateLayout(string $templateLayout, int $dailyBodyChars, int $maxChars): string
    {
        $layout = mb_strtolower(trim($templateLayout), 'UTF-8');

        if ($layout === 'bloque' || $layout === 'diario' || $layout === 'dos_partes') {
            return $layout;
        }

        return $dailyBodyChars <= $maxChars ? 'diario' : 'dos_partes';
    }

    protected function renderDailyTemplateBody(array $params): string
    {
        $p = array_values(array_map('strval', $params));

        for ($i = count($p); $i < 14; $i++) {
            $p[] = '';
        }

        return "Agrupamiento de Seguridad Vial\n\n"
            . "FECHA:\n{$p[0]}\n\n"
            . "ASEGURAMIENTOS PUESTOS A DISPOSICIÓN DE LA FISCALÍA GENERAL DEL ESTADO:\n{$p[1]}\n\n"
            . "SINIESTROS DE TRÁNSITO:\n{$p[2]}\n\n"
            . "APOYO A INSTITUCIONES:\n{$p[3]}\n\n"
            . "ATENCIÓN DE REPORTES DE C5i:\n{$p[4]}\n\n"
            . "ABANDERAMIENTOS VIALES:\n{$p[5]}\n\n"
            . "MONITOREOS:\n{$p[6]}\n\n"
            . "AUXILIO VIAL A CONDUCTORES:\n{$p[7]}\n\n"
            . "DISPOSITIVOS DE SEGURIDAD VIAL:\n{$p[8]}\n\n"
            . "ACCIONES DE CONCIENTIZACIÓN VIAL:\n{$p[9]}\n\n"
            . "CAMPAÑAS:\n{$p[10]}\n\n"
            . "PROXIMIDAD SOCIAL:\n{$p[11]}\n\n"
            . "SEGUNDO APARTADO DE TABLA G1\n"
            . "OPERATIVOS:\n{$p[12]}\n\n"
            . "INSPECCIONES:\n{$p[13]}\n\n"
            . "Para conocimiento de la superioridad.";
    }

    protected function renderTwoPartTemplateBodies(array $params): array
    {
        $p = array_values(array_map('strval', $params));

        for ($i = count($p); $i < 14; $i++) {
            $p[] = '';
        }

        return [
            "Agrupamiento de Seguridad Vial\n"
                . "Parte 1 de 2\n\n"
                . "FECHA:\n{$p[0]}\n\n"
                . "ASEGURAMIENTOS PUESTOS A DISPOSICIÓN DE LA FISCALÍA GENERAL DEL ESTADO:\n{$p[1]}\n\n"
                . "SINIESTROS DE TRÁNSITO:\n{$p[2]}\n\n"
                . "APOYO A INSTITUCIONES:\n{$p[3]}\n\n"
                . "ATENCIÓN DE REPORTES DE C5i:\n{$p[4]}\n\n"
                . "ABANDERAMIENTOS VIALES:\n{$p[5]}\n\n"
                . "MONITOREOS:\n{$p[6]}",
            "Agrupamiento de Seguridad Vial\n"
                . "Parte 2 de 2\n\n"
                . "AUXILIO VIAL A CONDUCTORES:\n{$p[7]}\n\n"
                . "DISPOSITIVOS DE SEGURIDAD VIAL:\n{$p[8]}\n\n"
                . "ACCIONES DE CONCIENTIZACIÓN VIAL:\n{$p[9]}\n\n"
                . "CAMPAÑAS:\n{$p[10]}\n\n"
                . "PROXIMIDAD SOCIAL:\n{$p[11]}\n\n"
                . "SEGUNDO APARTADO DE TABLA G1\n"
                . "OPERATIVOS:\n{$p[12]}\n\n"
                . "INSPECCIONES:\n{$p[13]}\n\n"
                . "Para conocimiento de la superioridad.",
        ];
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
