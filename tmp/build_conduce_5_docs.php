<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ConduceLegalidadTotalesDocxService;
use Carbon\Carbon;

$payload = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$operations = collect($payload['operations'] ?? [])->keyBy(fn (array $row) => (int) $row['id']);
$vehicles = collect($payload['vehicles'] ?? []);

$groups = [
    [
        'ids' => [79, 91],
        'slug' => '01_CALZADA_LA_HUERTA',
        'location' => 'Calzada La Huerta',
    ],
    [
        'ids' => [80, 81],
        'slug' => '02_PASO_SUPERIOR_FERROVIARIO_INDEPENDENCIA',
        'location' => 'Paso Superior Ferroviario Independencia',
    ],
    [
        'ids' => [82],
        'slug' => '03_PUENTE_VILLAS_DEL_PEDREGAL',
        'location' => 'Puente Villas del Pedregal',
    ],
    [
        'ids' => [83],
        'slug' => '04_PERIFERICO_PASEO_DE_LA_REPUBLICA',
        'location' => 'Periférico Paseo de la República',
    ],
    [
        'ids' => [84],
        'slug' => '05_XANGARI_LA_HUERTA',
        'location' => 'Xangari La Huerta',
    ],
];

$outputDir = base_path('exports_conduce_2026-11-11');
if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
    throw new RuntimeException('No se pudo crear el directorio de salida.');
}

$service = $app->make(ConduceLegalidadTotalesDocxService::class);
$manifest = [];

foreach ($groups as $group) {
    $groupOperations = collect($group['ids'])->map(fn (int $id) => $operations->get($id))->filter()->values();
    if ($groupOperations->count() !== count($group['ids'])) {
        throw new RuntimeException('Falta uno de los operativos del grupo ' . $group['slug']);
    }

    $groupVehicles = $vehicles
        ->filter(fn (array $row) => in_array((int) $row['operativo_id'], $group['ids'], true))
        ->sortByDesc(fn (array $row) => sprintf(
            '%s %s %010d',
            $row['fecha'] ?: '0000-00-00',
            substr((string) ($row['hora'] ?? ''), 0, 5),
            (int) $row['captura_id']
        ))
        ->values();

    $date = Carbon::parse($groupOperations->first()['fecha']);
    $start = $groupOperations
        ->pluck('hora_inicio')
        ->filter()
        ->map(fn ($value) => substr((string) $value, 0, 5))
        ->sort()
        ->first() ?? '';
    $finish = $groupVehicles
        ->pluck('hora')
        ->filter()
        ->map(fn ($value) => substr((string) $value, 0, 5))
        ->sortDesc()
        ->first() ?? $start;

    $records = $groupVehicles->map(function (array $row): array {
        $vehicleParts = collect([$row['marca'], $row['linea'], $row['modelo']])
            ->map(fn ($value) => trim((string) ($value ?? '')))
            ->filter()
            ->unique(fn ($value) => mb_strtoupper($value, 'UTF-8'));
        $insurance = trim((string) ($row['aseguradora'] ?? ''));
        $created = $row['captura_created_at']
            ? Carbon::parse($row['captura_created_at'])->format('Y-m-d H:i:s')
            : trim(($row['fecha'] ?? '') . ' ' . ($row['hora'] ?? ''));

        return [
            'placas' => $row['placas'],
            'vehiculo' => $vehicleParts->implode(' '),
            'tipo' => $row['tipo_general'] ?: $row['tipo'],
            'color' => $row['color'],
            'seguro' => $insurance !== '' ? 'Sí - ' . $insurance : 'No',
            'folio' => 'CL-' . (int) $row['operativo_id'] . '-' . (int) $row['captura_id'],
            'inventario' => $row['numero_inventario'],
            'corralon' => $row['corralon'],
            'fecha' => $created,
        ];
    })->all();

    $filename = sprintf('TOTALES_CONDUCE_LEGALIDAD_%s_%s.docx', $date->format('Y-m-d'), $group['slug']);
    $destination = $outputDir . DIRECTORY_SEPARATOR . $filename;
    $result = $service->generarConDatos([
        'dia' => $date->format('d'),
        'mesletra' => 'Noviembre',
        'year' => $date->format('Y'),
        'mesnumero' => $date->format('m'),
        'hora' => $start,
        'lugar' => $group['location'],
        'totalvehiculo' => (string) count($records),
        'horatermino' => $finish,
    ], $records, $destination);

    $manifest[] = [
        'path' => $result['path'],
        'name' => $result['name'],
        'vehicles' => count($records),
        'operations' => $group['ids'],
        'location' => $group['location'],
        'start' => $start,
        'finish' => $finish,
        'sha256' => $result['sha256'],
    ];
}

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
