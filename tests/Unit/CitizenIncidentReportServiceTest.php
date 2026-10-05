<?php

namespace Tests\Unit;

use App\Models\ReporteCiudadanoSiniestro;
use App\Services\WhatsApp\CitizenIncidentReportService;
use App\Services\WhatsAppCloudService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CitizenIncidentReportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'services.whatsapp.citizen_reports.enabled' => true,
            'services.whatsapp.citizen_reports.to' => '4434765057,4433284672',
            'services.whatsapp.citizen_reports.template' => '',
        ]);
    }

    public function test_primer_mensaje_muestra_menu_y_reporte_confirmado_se_envia_a_dos_numeros(): void
    {
        $cloud = new class extends WhatsAppCloudService {
            public array $texts = [];
            public array $interactives = [];

            public function sendText(string $to, string $body): array
            {
                $this->texts[] = compact('to', 'body');

                return [
                    'ok' => true,
                    'status' => 200,
                    'body' => ['messages' => [['id' => 'wamid.test']]],
                ];
            }

            public function sendInteractive(string $to, array $interactive): array
            {
                $this->interactives[] = compact('to', 'interactive');

                return ['ok' => true, 'status' => 200];
            }
        };

        $service = new class($cloud) extends CitizenIncidentReportService {
            public ?ReporteCiudadanoSiniestro $savedReport = null;

            protected function persist(string $from, array $data): ReporteCiudadanoSiniestro
            {
                $report = new class extends ReporteCiudadanoSiniestro {
                    public function update(array $attributes = [], array $options = [])
                    {
                        $this->fill($attributes);

                        return true;
                    }
                };

                $report->forceFill(array_merge($data, [
                    'folio' => 'RC-20261005-123456',
                    'telefono_reportante' => $from,
                    'estatus' => 'pendiente_validacion',
                    'reportado_at' => now(),
                ]));

                $this->savedReport = $report;

                return $report;
            }
        };

        $from = '5214431112233';

        $service->handle($from, $this->textMessage('m1', 'Hola'), ['type' => 'text', 'value' => 'Hola']);

        $this->assertStringContainsString('canal de reportes ciudadanos', $cloud->texts[0]['body']);
        $this->assertSame('citizen:start', $cloud->interactives[0]['interactive']['action']['buttons'][0]['reply']['id']);

        $service->handle($from, $this->buttonMessage('m2'), ['type' => 'button', 'value' => 'citizen:start']);
        $service->handle($from, [
            'id' => 'm3',
            'from' => $from,
            'type' => 'location',
            'location' => [
                'latitude' => 19.7021,
                'longitude' => -101.1923,
                'name' => 'Avenida Madero',
                'address' => 'Centro, Morelia',
            ],
        ], ['type' => 'location', 'value' => '']);
        $service->handle($from, $this->buttonMessage('m4'), ['type' => 'list', 'value' => 'citizen:type:choque']);
        $service->handle($from, $this->textMessage('m5', 'Una motocicleta y un automóvil'), ['type' => 'text', 'value' => 'Una motocicleta y un automóvil']);
        $service->handle($from, $this->buttonMessage('m6'), ['type' => 'button', 'value' => 'citizen:injured:yes']);
        $service->handle($from, $this->textMessage('m7', 'Carril bloqueado, sin incendio'), ['type' => 'text', 'value' => 'Carril bloqueado, sin incendio']);
        $service->handle($from, $this->buttonMessage('m8'), ['type' => 'button', 'value' => 'citizen:submit']);

        $this->assertNotNull($service->savedReport);
        $this->assertSame('Choque', $service->savedReport->tipo_siniestro);
        $this->assertSame('Una motocicleta y un automóvil', $service->savedReport->vehiculos);
        $this->assertSame('Sí, hay personas lesionadas o atrapadas', $service->savedReport->lesionados);

        $notifications = array_values(array_filter($cloud->texts, static function (array $message): bool {
            return in_array($message['to'], ['5214434765057', '5214433284672'], true);
        }));

        $this->assertCount(2, $notifications);
        $this->assertStringContainsString('REPORTE CIUDADANO NO VERIFICADO', $notifications[0]['body']);
        $this->assertStringContainsString('https://maps.google.com/?q=19.7021,-101.1923', $notifications[0]['body']);

        $confirmation = end($cloud->texts);
        $this->assertSame($from, $confirmation['to']);
        $this->assertStringContainsString('ya fue enviado a las unidades de Seguridad Vial', $confirmation['body']);
    }

    public function test_plantilla_interna_recibe_las_ocho_variables_en_el_orden_documentado(): void
    {
        config(['services.whatsapp.citizen_reports.template' => 'reporte_ciudadano_siniestro_v1']);

        $cloud = new class extends WhatsAppCloudService {
            public array $templates = [];

            public function sendTemplate(
                string $to,
                string $templateName,
                array $bodyParameters = [],
                string $language = 'es_MX'
            ): array {
                $this->templates[] = compact('to', 'templateName', 'bodyParameters', 'language');

                return ['ok' => true, 'status' => 200];
            }
        };

        $service = new class($cloud) extends CitizenIncidentReportService {
            public function notify(ReporteCiudadanoSiniestro $report): array
            {
                return $this->notifyRecipients($report);
            }
        };

        $report = new ReporteCiudadanoSiniestro();
        $report->forceFill([
            'folio' => 'RC-20261005-654321',
            'telefono_reportante' => '5214431112233',
            'ubicacion' => 'Avenida Madero y Morelos, Centro, Morelia',
            'latitud' => 19.7021,
            'longitud' => -101.1923,
            'tipo_siniestro' => 'Choque',
            'vehiculos' => 'Motocicleta negra y automóvil blanco',
            'lesionados' => 'Una persona lesionada',
            'riesgos_observaciones' => 'Carril bloqueado',
            'reportado_at' => '2026-10-05 14:30:00',
        ]);

        $service->notify($report);

        $this->assertCount(2, $cloud->templates);
        $this->assertSame('reporte_ciudadano_siniestro_v1', $cloud->templates[0]['templateName']);
        $this->assertSame('es_MX', $cloud->templates[0]['language']);
        $this->assertSame([
            'RC-20261005-654321',
            '05/10/2026 14:30',
            '+5214431112233',
            'Avenida Madero y Morelos, Centro, Morelia | Mapa: https://maps.google.com/?q=19.7021,-101.1923',
            'Choque',
            'Motocicleta negra y automóvil blanco',
            'Una persona lesionada',
            'Carril bloqueado',
        ], $cloud->templates[0]['bodyParameters']);
    }

    private function textMessage(string $id, string $body): array
    {
        return [
            'id' => $id,
            'type' => 'text',
            'text' => ['body' => $body],
        ];
    }

    private function buttonMessage(string $id): array
    {
        return [
            'id' => $id,
            'type' => 'interactive',
        ];
    }
}
