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
            $this->line('Plantilla parte 1: ' . ($twoPartTemplate1 !== '' ? $twoPartTemplate1 : 'SIN CONFIGURAR'));
            $this->line('Plantilla parte 2: ' . ($twoPartTemplate2 !== '' ? $twoPartTemplate2 : 'SIN CONFIGURAR'));
            $this->line('Plantilla respaldo por partes: ' . ($blockTemplate !== '' ? $blockTemplate : 'SIN CONFIGURAR'));
            $this->line('Layout configurado: ' . $templateLayout);
            $this->line('Layout elegido: ' . $selectedTemplateLayout);
            $this->line('Idioma: ' . $language);
            $this->line('Limite cuerpo plantilla: ' . $templateBodyMaxChars . ' caracteres');
            $this->line('Cuerpo estimado con plantilla diaria: ' . $dailyBodyChars . ' caracteres');

            if ($this->usaTemplateDosPartes($selectedTemplateLayout) && ($twoPartTemplate1 === '' || $twoPartTemplate2 === '')) {
                $this->error('Faltan WHATSAPP_TODAS_UNIDADES_TEMPLATE_PARTE_1 o WHATSAPP_TODAS_UNIDADES_TEMPLATE_PARTE_2.');
                return self::FAILURE;
            }

            if ($template === '' && !$this->usaTemplateBloque($selectedTemplateLayout) && !$this->usaTemplateDosPartes($selectedTemplateLayout)) {
                $this->error('Falta WHATSAPP_TODAS_UNIDADES_TEMPLATE.');
                return self::FAILURE;
            }

            if ($blockTemplate === '' && $this->usaTemplateBloque($selectedTemplateLayout)) {
                $this->error('Falta WHATSAPP_TODAS_UNIDADES_BLOCK_TEMPLATE.');
                return self::FAILURE;
            }

            if ($this->usaTemplateDosPartes($selectedTemplateLayout)) {
                $this->line('Cuerpos esperados en Meta:');
                $this->line('');
                $this->line('--- ' . $twoPartTemplate1 . ' ---');
                $this->line($this->twoPartTemplateBody1());
                $this->line('');
                $this->line('--- ' . $twoPartTemplate2 . ' ---');
                $this->line($this->twoPartTemplateBody2());
                $this->line('');
                $this->line('Mensajes a enviar: 2');
                $this->line('Parte 1/2: ' . mb_strlen($twoPartBodies[0], 'UTF-8') . ' caracteres');
                $this->line('Parte 2/2: ' . mb_strlen($twoPartBodies[1], 'UTF-8') . ' caracteres');
            } elseif ($this->usaTemplateBloque($selectedTemplateLayout)) {
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

                foreach ($dailyParams as $index => $param) {
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
            str_replace(
                ['{{1}}', '{{2}}', '{{3}}', '{{4}}', '{{5}}', '{{6}}', '{{7}}'],
                array_slice($p, 0, 7),
                $this->twoPartTemplateBody1()
            ),
            str_replace(
                ['{{1}}', '{{2}}', '{{3}}', '{{4}}', '{{5}}', '{{6}}', '{{7}}'],
                array_slice($p, 7, 7),
                $this->twoPartTemplateBody2()
            ),
        ];
    }

    protected function twoPartTemplateBody1(): string
    {
        return "Agrupamiento de Seguridad Vial\n"
            . "Parte 1 de 2\n\n"
            . "FECHA:\n{{1}}\n\n"
            . "ASEGURAMIENTOS PUESTOS A DISPOSICIÓN DE LA FISCALÍA GENERAL DEL ESTADO:\n{{2}}\n\n"
            . "SINIESTROS DE TRÁNSITO:\n{{3}}\n\n"
            . "APOYO A INSTITUCIONES:\n{{4}}\n\n"
            . "ATENCIÓN DE REPORTES DE C5i:\n{{5}}\n\n"
            . "ABANDERAMIENTOS VIALES:\n{{6}}\n\n"
            . "MONITOREOS:\n{{7}}";
    }

    protected function twoPartTemplateBody2(): string
    {
        return "Agrupamiento de Seguridad Vial\n"
            . "Parte 2 de 2\n\n"
            . "AUXILIO VIAL A CONDUCTORES:\n{{1}}\n\n"
            . "DISPOSITIVOS DE SEGURIDAD VIAL:\n{{2}}\n\n"
            . "ACCIONES DE CONCIENTIZACIÓN VIAL:\n{{3}}\n\n"
            . "CAMPAÑAS:\n{{4}}\n\n"
            . "PROXIMIDAD SOCIAL:\n{{5}}\n\n"
            . "SEGUNDO APARTADO DE TABLA G1\n"
            . "OPERATIVOS:\n{{6}}\n\n"
            . "INSPECCIONES:\n{{7}}\n\n"
            . "Para conocimiento de la superioridad.";
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
