<?php

namespace App\Services\WhatsApp;

class WhatsAppMenuService
{
    public function buildRootMenu($user, array $context, ?string $message = null): array
    {
        $text = $message ?: 'Selecciona la unidad que deseas consultar.';

        return [
            'text' => "Hola {$user->name}.\n\n{$text}",
            'interactive' => [
                'type' => 'list',
                'header' => [
                    'type' => 'text',
                    'text' => 'Menú general',
                ],
                'body' => [
                    'text' => 'Elige una unidad',
                ],
                'footer' => [
                    'text' => 'Puedes escribir MENÚ para reiniciar',
                ],
                'action' => [
                    'button' => 'Ver opciones',
                    'sections' => [
                        [
                            'title' => 'Unidades',
                            'rows' => [
                                [
                                    'id' => 'module:siniestros',
                                    'title' => 'Siniestros',
                                    'description' => 'Hechos, placas y folios',
                                ],
                                [
                                    'id' => 'module:carreteras',
                                    'title' => 'Carreteras',
                                    'description' => 'Actividades y dispositivos',
                                ],
                                [
                                    'id' => 'module:vialidades',
                                    'title' => 'Vialidades Urbanas',
                                    'description' => 'Operativos y dispositivos',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function buildModuleMenu($user, array $context, string $module, ?string $message = null): array
    {
        $title = $this->moduleTitle($module);
        $text = $message ?: "Menú de {$title}.";

        $rows = match ($module) {
            'siniestros' => $context['solo_propios']
                ? [
                    ['id' => 'action:mis_hechos_hoy', 'title' => 'Mis hechos de hoy', 'description' => 'Solo los tuyos'],
                    ['id' => 'action:mis_hechos_placas', 'title' => 'Mis hechos por placas', 'description' => 'Buscar por placas'],
                    ['id' => 'action:mi_detalle_folio', 'title' => 'Mi detalle por folio', 'description' => 'Buscar por folio'],
                ]
                : [
                    ['id' => 'action:hechos_hoy', 'title' => 'Hechos de hoy', 'description' => 'Listado de hoy'],
                    ['id' => 'action:hechos_placas', 'title' => 'Hechos por placas', 'description' => 'Buscar por placas'],
                    ['id' => 'action:detalle_folio', 'title' => 'Detalle por folio', 'description' => 'Buscar por folio'],
                ],
            'carreteras' => [
                ['id' => 'action:no_disponible', 'title' => 'Pendiente', 'description' => 'Se habilita después'],
            ],
            'vialidades' => [
                ['id' => 'action:no_disponible', 'title' => 'Pendiente', 'description' => 'Se habilita después'],
            ],
            default => [
                ['id' => 'action:no_disponible', 'title' => 'Pendiente', 'description' => 'Se habilita después'],
            ],
        };

        return [
            'text' => "{$text}\n\nPuedes escribir MENÚ para reiniciar.",
            'interactive' => [
                'type' => 'list',
                'header' => [
                    'type' => 'text',
                    'text' => $title,
                ],
                'body' => [
                    'text' => 'Selecciona una opción',
                ],
                'footer' => [
                    'text' => 'Consultas disponibles',
                ],
                'action' => [
                    'button' => 'Ver opciones',
                    'sections' => [
                        [
                            'title' => $title,
                            'rows' => $rows,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function resolveModuleSelection(array $input, array $context): ?string
    {
        $value = trim((string) ($input['value'] ?? ''));
        $value = mb_strtolower($value, 'UTF-8');

        if (str_starts_with($value, 'module:')) {
            $value = substr($value, 7);
        }

        if (in_array($value, $context['modules'] ?? [], true)) {
            return $value;
        }

        return null;
    }

    public function resolveActionSelection(array $input, string $module, array $context): ?array
    {
        $value = trim((string) ($input['value'] ?? ''));
        $value = mb_strtolower($value, 'UTF-8');

        if (str_starts_with($value, 'action:')) {
            $value = substr($value, 7);
        }

        $map = [
            'hechos_hoy' => ['key' => 'hechos_hoy', 'requires_param' => false],
            'hechos_placas' => ['key' => 'hechos_placas', 'requires_param' => true, 'param_type' => 'placas'],
            'detalle_folio' => ['key' => 'detalle_folio', 'requires_param' => true, 'param_type' => 'folio'],
            'mis_hechos_hoy' => ['key' => 'mis_hechos_hoy', 'requires_param' => false],
            'mis_hechos_placas' => ['key' => 'mis_hechos_placas', 'requires_param' => true, 'param_type' => 'placas'],
            'mi_detalle_folio' => ['key' => 'mi_detalle_folio', 'requires_param' => true, 'param_type' => 'folio'],
            'no_disponible' => ['key' => 'no_disponible', 'requires_param' => false],
        ];

        return $map[$value] ?? null;
    }

    public function buildActionPrompt(string $module, string $action, array $context, ?string $message = null): array
    {
        $text = match ($action) {
            'hechos_placas', 'mis_hechos_placas' => "Escribe las placas.\n\nEjemplo:\nABC123",
            'detalle_folio', 'mi_detalle_folio' => "Escribe el folio o ID del hecho.\n\nEjemplo:\n59564",
            default => 'Escribe el dato solicitado.',
        };

        if ($message) {
            $text = $message . "\n\n" . $text;
        }

        return [
            'text' => $text,
        ];
    }

    protected function moduleTitle(string $module): string
    {
        return match ($module) {
            'siniestros' => 'Siniestros',
            'carreteras' => 'Carreteras',
            'vialidades' => 'Vialidades Urbanas',
            default => 'Consultas',
        };
    }
}
